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

namespace filter_rating\output;

use context_course;
use filter_rating\local\rating_manager;
use stdClass;

/**
 * Moodle app entry points for the star rating filter.
 *
 * @package    filter_rating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {

    /**
     * Decide in which courses the app shows the ratings tab.
     *
     * Called once when the app loads the site plugins, so the tab does not
     * appear in courses that offer nothing to rate.
     *
     * @param array $args Arguments passed by the app, unused.
     * @return array Content for the app.
     */
    public static function mobile_init(array $args): array {
        global $USER;

        $usercourses = array_keys(enrol_get_users_courses($USER->id, true, 'id'));
        $courses = $usercourses ? rating_manager::get_courses_with_criteria($usercourses) : [];

        return [
            'templates' => [],
            'javascript' => '',
            'restrict' => ['courses' => $courses],
        ];
    }

    /**
     * The ratings tab of one course.
     *
     * Every star is an element carrying core-site-plugins-call-ws, so the app
     * submits the rating itself and reloads this tab afterwards. No JavaScript
     * is shipped to the app.
     *
     * @param array $args Arguments passed by the app, expects courseid.
     * @return array Content for the app.
     */
    public static function mobile_course_ratings(array $args): array {
        global $OUTPUT;

        $courseid = (int) ($args['courseid'] ?? 0);
        $course = get_course($courseid);
        require_login($course, false);

        $context = context_course::instance($courseid);
        $canrate = rating_manager::can_rate($context);
        $items = [];

        foreach (rating_manager::get_course_criteria($courseid) as $entry) {
            $stats = new stdClass();
            $stats->count = $entry->count;
            $stats->average = $entry->average;

            $stars = [];
            for ($value = 1; $value <= rating_manager::MAX_STARS; $value++) {
                $stars[] = [
                    'on' => $value <= $entry->userrating,
                    'params' => self::build_params($context->id, $entry->criterion, $value),
                    'label' => get_string('ratestars', 'filter_rating', (object) [
                        'stars' => $value,
                        'max' => rating_manager::MAX_STARS,
                        'criterion' => $entry->criterion,
                    ]),
                ];
            }

            $items[] = [
                'criterion' => $entry->criterion,
                'statustext' => rating_manager::get_status_text($entry->criterion, $entry->userrating),
                'averagetext' => rating_manager::get_average_text($stats),
                'stars' => $stars,
                'canclear' => $canrate && $entry->userrating > 0,
                'clearparams' => self::build_params($context->id, $entry->criterion, 0),
                'clearlabel' => get_string('clearratingaria', 'filter_rating', $entry->criterion),
            ];
        }

        $html = $OUTPUT->render_from_template('filter_rating/mobile_course_ratings', [
            'items' => $items,
            'hasitems' => !empty($items),
            'canrate' => $canrate,
            'emptytext' => get_string('nocriteriaincourse', 'filter_rating'),
            'cleartext' => get_string('clearrating', 'filter_rating'),
            'successmessage' => get_string('ratingsaved', 'filter_rating'),
        ]);

        return [
            'templates' => [['id' => 'main', 'html' => $html]],
            'javascript' => '',
            'otherdata' => '',
        ];
    }

    /**
     * The parameter object the app passes to filter_rating_set_rating.
     *
     * The app reads this as an Angular expression, so the criterion is quoted
     * and escaped for a single quoted string. The template escapes the result
     * again for the HTML attribute.
     *
     * @param int $contextid Course context the rating is submitted in.
     * @param string $criterion Normalised criterion.
     * @param int $rating Number of stars, 0 to withdraw.
     * @return string
     */
    protected static function build_params(int $contextid, string $criterion, int $rating): string {
        $escaped = str_replace(['\\', "'"], ['\\\\', "\\'"], $criterion);

        return sprintf("{contextid: %d, criterion: '%s', rating: %d}", $contextid, $escaped, $rating);
    }
}
