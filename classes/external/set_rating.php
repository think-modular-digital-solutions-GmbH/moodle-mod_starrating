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

namespace filter_rating\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use filter_rating\local\rating_manager;

/**
 * Save the star rating of the current user, used by the Moodle app.
 *
 * @package    filter_rating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_rating extends external_api {

    /**
     * Parameter definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'Context the rating is submitted from'),
            'criterion' => new external_value(PARAM_TEXT, 'The criterion being rated'),
            'rating' => new external_value(PARAM_INT, 'Number of stars, 1 to 5, or 0 to withdraw the rating'),
        ]);
    }

    /**
     * Save the rating.
     *
     * @param int $contextid Context the rating is submitted from.
     * @param string $criterion The criterion being rated.
     * @param int $rating Number of stars, 0 to withdraw.
     * @return array Result as described by execute_returns().
     */
    public static function execute(int $contextid, string $criterion, int $rating): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'contextid' => $contextid,
            'criterion' => $criterion,
            'rating' => $rating,
        ]);

        $context = \context::instance_by_id($params['contextid']);
        self::validate_context($context);
        rating_manager::require_can_rate($context);

        $criterion = rating_manager::normalise_criterion($params['criterion']);
        $courseid = rating_manager::get_courseid_from_context($context);

        rating_manager::set_rating($context, $courseid, $criterion, $params['rating']);

        $stats = rating_manager::get_stats($courseid, $criterion);
        $userrating = rating_manager::get_user_rating($courseid, $criterion);

        return [
            'userrating' => $userrating,
            'average' => $stats->average,
            'count' => $stats->count,
            'statustext' => rating_manager::get_status_text($criterion, $userrating),
            'averagetext' => rating_manager::get_average_text($stats),
            'message' => $params['rating'] > 0
                ? get_string('ratingsaved', 'filter_rating')
                : get_string('ratingcleared', 'filter_rating'),
        ];
    }

    /**
     * Return value definition.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'userrating' => new external_value(PARAM_INT, 'Stars the current user gave, 0 when not rated'),
            'average' => new external_value(PARAM_FLOAT, 'Average of all ratings for the criterion'),
            'count' => new external_value(PARAM_INT, 'Number of ratings the average is based on'),
            'statustext' => new external_value(PARAM_TEXT, 'The rating of the user in words'),
            'averagetext' => new external_value(PARAM_TEXT, 'The average in words'),
            'message' => new external_value(PARAM_TEXT, 'Confirmation message for the user'),
        ]);
    }
}
