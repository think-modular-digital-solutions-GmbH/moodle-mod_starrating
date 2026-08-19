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

namespace mod_starrating\local;

use context;
use mod_starrating\event\rating_submitted;
use stdClass;

/**
 * Reads and writes star ratings.
 *
 * One rating per activity and user. The criterion is the name of the activity,
 * so nothing has to be parsed or matched.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rating_manager {
    /** @var int Shortest rating scale an activity can use. A scale needs at least two values. */
    public const MIN_MAX_STARS = 2;

    /** @var int Longest rating scale an activity can use. */
    public const ABSOLUTE_MAX_STARS = 10;

    /** @var int Scale an activity uses when nothing else is configured. */
    public const DEFAULT_MAX_STARS = 5;

    /** @var string Table holding the ratings. */
    public const TABLE = 'starrating_rating';

    /** @var array<string, int> Request level cache of user ratings, keyed by activity and user. */
    protected static array $userratings = [];

    /** @var array<int, stdClass> Request level cache of statistics, keyed by activity. */
    protected static array $stats = [];

    /** @var array<int, int> Request level cache of scale lengths, keyed by activity. */
    protected static array $maxstars = [];

    /**
     * The scale lengths an activity may be configured with.
     *
     * @return array<int, string> Number of stars to its own label, for a select.
     */
    public static function get_max_stars_options(): array {
        $options = [];
        for ($value = self::MIN_MAX_STARS; $value <= self::ABSOLUTE_MAX_STARS; $value++) {
            $options[$value] = (string) $value;
        }

        return $options;
    }

    /**
     * Force a scale length coming from a form, a backup or a web service into range.
     *
     * @param mixed $value The value to check.
     * @return int A scale length this plugin supports.
     */
    public static function normalise_max_stars($value): int {
        $value = (int) $value;

        if ($value < self::MIN_MAX_STARS || $value > self::ABSOLUTE_MAX_STARS) {
            return self::DEFAULT_MAX_STARS;
        }

        return $value;
    }

    /**
     * The scale length new activities start out with, from the site setting.
     *
     * @return int
     */
    public static function get_default_max_stars(): int {
        return self::normalise_max_stars(get_config('mod_starrating', 'maxstars') ?: self::DEFAULT_MAX_STARS);
    }

    /**
     * The scale length one activity is rated on.
     *
     * @param int $starratingid Activity instance id.
     * @return int
     */
    public static function get_max_stars(int $starratingid): int {
        global $DB;

        if (!array_key_exists($starratingid, self::$maxstars)) {
            $stored = $DB->get_field('starrating', 'maxstars', ['id' => $starratingid]);
            self::$maxstars[$starratingid] = self::normalise_max_stars($stored);
        }

        return self::$maxstars[$starratingid];
    }

    /**
     * The highest rating anybody gave to an activity.
     *
     * Shortening the scale of an activity that is already rated would leave
     * ratings above the new maximum, so the form checks this first.
     *
     * @param int $starratingid Activity instance id.
     * @return int Number of stars, 0 when the activity has no ratings.
     */
    public static function get_highest_rating(int $starratingid): int {
        global $DB;

        $sql = 'SELECT MAX(rating) FROM {' . self::TABLE . '} WHERE starratingid = :starratingid';

        return (int) $DB->get_field_sql($sql, ['starratingid' => $starratingid]);
    }

    /**
     * Whether the current user may submit a rating in this context.
     *
     * @param context $context Context of the activity.
     * @return bool
     */
    public static function can_rate(context $context): bool {
        return isloggedin() && !isguestuser() && has_capability('mod/starrating:rate', $context);
    }

    /**
     * Throw if the current user may not submit a rating in this context.
     *
     * @param context $context Context of the activity.
     * @return void
     */
    public static function require_can_rate(context $context): void {
        if (!isloggedin() || isguestuser()) {
            throw new \moodle_exception('guestcannotrate', 'mod_starrating');
        }
        require_capability('mod/starrating:rate', $context);
    }

    /**
     * The rating a user gave to an activity.
     *
     * @param int $starratingid Activity instance id.
     * @param int|null $userid Defaults to the current user.
     * @return int Number of stars, 0 when the user has not rated yet.
     */
    public static function get_user_rating(int $starratingid, ?int $userid = null): int {
        global $DB, $USER;

        $userid = $userid ?? (int) $USER->id;
        if (empty($userid) || isguestuser($userid)) {
            return 0;
        }

        $key = $starratingid . ':' . $userid;
        if (!array_key_exists($key, self::$userratings)) {
            self::$userratings[$key] = (int) $DB->get_field(self::TABLE, 'rating', [
                'starratingid' => $starratingid,
                'userid' => $userid,
            ]);
        }

        return self::$userratings[$key];
    }

    /**
     * Number of ratings and average for an activity.
     *
     * @param int $starratingid Activity instance id.
     * @return stdClass Object with the int property count and the float property average.
     */
    public static function get_stats(int $starratingid): stdClass {
        global $DB;

        if (!array_key_exists($starratingid, self::$stats)) {
            $sql = 'SELECT COUNT(id) AS numratings, AVG(rating) AS avgrating
                      FROM {' . self::TABLE . '}
                     WHERE starratingid = :starratingid AND rating > 0';
            $record = $DB->get_record_sql($sql, ['starratingid' => $starratingid]);

            $stats = new stdClass();
            $stats->count = (int) ($record->numratings ?? 0);
            $stats->average = $stats->count ? round((float) $record->avgrating, 2) : 0.0;

            self::$stats[$starratingid] = $stats;
        }

        return self::$stats[$starratingid];
    }

    /**
     * Store the rating of a user, replacing any earlier rating.
     *
     * @param context $context Context of the activity.
     * @param int $starratingid Activity instance id.
     * @param int $rating Number of stars, 1 to the scale length of the activity, or 0 to withdraw the rating.
     * @param int|null $userid Defaults to the current user.
     * @return int The stored rating.
     */
    public static function set_rating(context $context, int $starratingid, int $rating, ?int $userid = null): int {
        global $DB, $USER;

        $userid = $userid ?? (int) $USER->id;
        $maxstars = self::get_max_stars($starratingid);

        if ($rating < 0 || $rating > $maxstars) {
            throw new \moodle_exception('invalidrating', 'mod_starrating', '', $maxstars);
        }

        $conditions = ['starratingid' => $starratingid, 'userid' => $userid];
        $existing = $DB->get_record(self::TABLE, $conditions);
        $now = time();

        if ($rating === 0) {
            if ($existing) {
                $DB->delete_records(self::TABLE, ['id' => $existing->id]);
            }
        } else if ($existing) {
            $existing->rating = $rating;
            $existing->timemodified = $now;
            $DB->update_record(self::TABLE, $existing);
        } else {
            $record = (object) $conditions;
            $record->rating = $rating;
            $record->timecreated = $now;
            $record->timemodified = $now;
            $DB->insert_record(self::TABLE, $record);
        }

        self::reset_caches();

        $event = rating_submitted::create([
            'context' => $context,
            'objectid' => $starratingid,
            'relateduserid' => $userid === (int) $USER->id ? null : $userid,
            'other' => ['rating' => $rating],
        ]);
        $event->trigger();

        return $rating;
    }

    /**
     * The rating of a user in words, so that it is never carried by the stars alone.
     *
     * @param string $name Name of the activity, which is the criterion.
     * @param int $userrating Number of stars the user gave.
     * @param int $maxstars Length of the rating scale.
     * @return string
     */
    public static function get_status_text(string $name, int $userrating, int $maxstars): string {
        if ($userrating > 0) {
            return get_string('yourrating', 'mod_starrating', (object) [
                'stars' => $userrating,
                'max' => $maxstars,
                'criterion' => $name,
            ]);
        }

        return get_string('notratedyet', 'mod_starrating', $name);
    }

    /**
     * The average of all ratings in words.
     *
     * @param stdClass $stats Statistics as returned by get_stats().
     * @param int $maxstars Length of the rating scale.
     * @return string
     */
    public static function get_average_text(stdClass $stats, int $maxstars): string {
        if ($stats->count === 0) {
            return get_string('noratingsyet', 'mod_starrating');
        }

        return get_string('averagetext', 'mod_starrating', (object) [
            'average' => format_float($stats->average, 1),
            'max' => $maxstars,
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
        self::$maxstars = [];
    }
}
