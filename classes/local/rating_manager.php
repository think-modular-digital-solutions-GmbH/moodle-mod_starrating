<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace filter_rating\local;

use context;
use filter_rating\event\rating_submitted;
use stdClass;

/**
 * Reads and writes star ratings.
 *
 * A rating is identified by course, criterion and user. The same criterion used
 * in several places of one course is therefore one rating, which is what makes
 * the course and criterion averages in the report meaningful.
 *
 * @package    filter_rating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rating_manager {

    /** @var int Number of stars a criterion is rated with. */
    public const MAX_STARS = 5;

    /** @var string Table holding the ratings. */
    public const TABLE = 'filter_rating';

    /** @var int Maximum length of a criterion identifier. */
    public const CRITERION_MAXLENGTH = 100;

    /** @var array<string, int> Request level cache of user ratings, keyed by course, user and criterion. */
    protected static array $userratings = [];

    /** @var array<string, stdClass> Request level cache of statistics, keyed by course and criterion. */
    protected static array $stats = [];

    /**
     * Bring a criterion from a filter token or a request into its canonical form.
     *
     * @param string $criterion Raw criterion.
     * @return string Normalised criterion, empty if nothing usable is left.
     */
    public static function normalise_criterion(string $criterion): string {
        $criterion = clean_param($criterion, PARAM_TEXT);
        $criterion = trim(preg_replace('/\s+/u', ' ', $criterion));

        return \core_text::substr($criterion, 0, self::CRITERION_MAXLENGTH);
    }

    /**
     * Whether the current user may submit a rating in this context.
     *
     * @param context $context Context the rating is displayed in.
     * @return bool
     */
    public static function can_rate(context $context): bool {
        return isloggedin() && !isguestuser() && has_capability('filter/rating:rate', $context);
    }

    /**
     * Throw if the current user may not submit a rating in this context.
     *
     * @param context $context Context the rating is submitted from.
     * @return void
     */
    public static function require_can_rate(context $context): void {
        if (!isloggedin() || isguestuser()) {
            throw new \moodle_exception('guestcannotrate', 'filter_rating');
        }
        require_capability('filter/rating:rate', $context);
    }

    /**
     * The rating a user gave for a criterion.
     *
     * @param int $courseid Course the criterion belongs to.
     * @param string $criterion Normalised criterion.
     * @param int|null $userid Defaults to the current user.
     * @return int Number of stars, 0 when the user has not rated yet.
     */
    public static function get_user_rating(int $courseid, string $criterion, ?int $userid = null): int {
        global $DB, $USER;

        $userid = $userid ?? (int) $USER->id;
        if (empty($userid) || isguestuser($userid)) {
            return 0;
        }

        $key = $courseid . ':' . $userid . ':' . $criterion;
        if (!array_key_exists($key, self::$userratings)) {
            self::$userratings[$key] = (int) $DB->get_field(self::TABLE, 'rating', [
                'courseid' => $courseid,
                'criterion' => $criterion,
                'userid' => $userid,
            ]);
        }

        return self::$userratings[$key];
    }

    /**
     * Number of ratings and average for a criterion.
     *
     * @param int $courseid Course the criterion belongs to.
     * @param string $criterion Normalised criterion.
     * @return stdClass Object with the int property count and the float property average.
     */
    public static function get_stats(int $courseid, string $criterion): stdClass {
        global $DB;

        $key = $courseid . ':' . $criterion;
        if (!array_key_exists($key, self::$stats)) {
            $sql = 'SELECT COUNT(id) AS numratings, AVG(rating) AS avgrating
                      FROM {' . self::TABLE . '}
                     WHERE courseid = :courseid AND criterion = :criterion AND rating > 0';
            $record = $DB->get_record_sql($sql, ['courseid' => $courseid, 'criterion' => $criterion]);

            $stats = new stdClass();
            $stats->count = (int) ($record->numratings ?? 0);
            $stats->average = $stats->count ? round((float) $record->avgrating, 2) : 0.0;

            self::$stats[$key] = $stats;
        }

        return self::$stats[$key];
    }

    /**
     * Store the rating of a user for a criterion, replacing any earlier rating.
     *
     * @param context $context Context the rating was submitted from.
     * @param int $courseid Course the criterion belongs to.
     * @param string $criterion Criterion, normalised by this method.
     * @param int $rating Number of stars, 1 to 5, or 0 to withdraw the rating.
     * @param int|null $userid Defaults to the current user.
     * @return int The stored rating.
     */
    public static function set_rating(context $context, int $courseid, string $criterion, int $rating,
            ?int $userid = null): int {
        global $DB, $USER;

        $userid = $userid ?? (int) $USER->id;
        $criterion = self::normalise_criterion($criterion);

        if ($criterion === '') {
            throw new \moodle_exception('invalidcriterion', 'filter_rating');
        }
        if ($rating < 0 || $rating > self::MAX_STARS) {
            throw new \moodle_exception('invalidrating', 'filter_rating');
        }

        $conditions = ['courseid' => $courseid, 'criterion' => $criterion, 'userid' => $userid];
        $existing = $DB->get_record(self::TABLE, $conditions);
        $now = time();

        if ($rating === 0) {
            if ($existing) {
                $DB->delete_records(self::TABLE, ['id' => $existing->id]);
            }
        } else if ($existing) {
            $existing->rating = $rating;
            $existing->contextid = $context->id;
            $existing->timemodified = $now;
            $DB->update_record(self::TABLE, $existing);
        } else {
            $record = (object) $conditions;
            $record->contextid = $context->id;
            $record->rating = $rating;
            $record->timecreated = $now;
            $record->timemodified = $now;
            $DB->insert_record(self::TABLE, $record);
        }

        self::reset_caches();

        $event = rating_submitted::create([
            'context' => $context,
            'courseid' => $courseid,
            'relateduserid' => $userid === (int) $USER->id ? null : $userid,
            'other' => ['criterion' => $criterion, 'rating' => $rating],
        ]);
        $event->trigger();

        return $rating;
    }

    /**
     * The rating of a user in words, so that it is never carried by the stars alone.
     *
     * @param string $criterion Normalised criterion.
     * @param int $userrating Number of stars the user gave.
     * @return string
     */
    public static function get_status_text(string $criterion, int $userrating): string {
        if ($userrating > 0) {
            return get_string('yourrating', 'filter_rating', (object) [
                'stars' => $userrating,
                'max' => self::MAX_STARS,
                'criterion' => $criterion,
            ]);
        }

        return get_string('notratedyet', 'filter_rating', $criterion);
    }

    /**
     * The average of all ratings in words.
     *
     * @param stdClass $stats Statistics as returned by get_stats().
     * @return string
     */
    public static function get_average_text(stdClass $stats): string {
        if ($stats->count === 0) {
            return get_string('noratingsyet', 'filter_rating');
        }

        return get_string('averagetext', 'filter_rating', (object) [
            'average' => format_float($stats->average, 1),
            'max' => self::MAX_STARS,
            'count' => $stats->count,
        ]);
    }

    /**
     * Drop the request level caches after a write.
     *
     * @return void
     */
    public static function reset_caches(): void {
        self::$userratings = [];
        self::$stats = [];
    }

    /**
     * The course a context belongs to, or the site course outside any course.
     *
     * @param context $context Context to resolve.
     * @return int Course id.
     */
    public static function get_courseid_from_context(context $context): int {
        $coursecontext = $context->get_course_context(false);

        return $coursecontext ? (int) $coursecontext->instanceid : (int) SITEID;
    }
}
