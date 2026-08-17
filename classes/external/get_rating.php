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
 * Read the star rating of the current user and the average for a criterion.
 *
 * @package    filter_rating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_rating extends external_api {

    /**
     * Parameter definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'Context the rating is displayed in'),
            'criterion' => new external_value(PARAM_TEXT, 'The criterion being rated'),
        ]);
    }

    /**
     * Read the rating.
     *
     * @param int $contextid Context the rating is displayed in.
     * @param string $criterion The criterion being rated.
     * @return array Result as described by execute_returns().
     */
    public static function execute(int $contextid, string $criterion): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'contextid' => $contextid,
            'criterion' => $criterion,
        ]);

        $context = \context::instance_by_id($params['contextid']);
        self::validate_context($context);

        $criterion = rating_manager::normalise_criterion($params['criterion']);
        $courseid = rating_manager::get_courseid_from_context($context);
        $stats = rating_manager::get_stats($courseid, $criterion);

        return [
            'userrating' => rating_manager::get_user_rating($courseid, $criterion),
            'average' => $stats->average,
            'count' => $stats->count,
            'canrate' => rating_manager::can_rate($context),
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
            'canrate' => new external_value(PARAM_BOOL, 'Whether the current user may rate'),
        ]);
    }
}
