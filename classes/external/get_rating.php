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

namespace mod_starrating\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_starrating\local\rating_manager;

/**
 * Read the star rating of the current user and the average for an activity.
 *
 * @package    mod_starrating
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
            'cmid' => new external_value(PARAM_INT, 'Course module id of the activity'),
        ]);
    }

    /**
     * Read the rating.
     *
     * @param int $cmid Course module id.
     * @return array Result as described by execute_returns().
     */
    public static function execute(int $cmid): array {
        $params = self::validate_parameters(self::execute_parameters(), ['cmid' => $cmid]);

        // A plain lookup first: the context has to be validated before anything
        // builds course module info for the user.
        $cm = get_coursemodule_from_id('starrating', $params['cmid'], 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        $stats = rating_manager::get_stats((int) $cm->instance);
        $userrating = rating_manager::get_user_rating((int) $cm->instance);
        $name = format_string($cm->name, true, ['context' => $context]);

        return [
            'userrating' => $userrating,
            'average' => $stats->average,
            'count' => $stats->count,
            'statustext' => rating_manager::get_status_text($name, $userrating),
            'averagetext' => rating_manager::get_average_text($stats),
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
            'average' => new external_value(PARAM_FLOAT, 'Average of all ratings for the activity'),
            'count' => new external_value(PARAM_INT, 'Number of ratings the average is based on'),
            'statustext' => new external_value(PARAM_TEXT, 'The rating of the user in words'),
            'averagetext' => new external_value(PARAM_TEXT, 'The average in words'),
            'canrate' => new external_value(PARAM_BOOL, 'Whether the current user may rate'),
        ]);
    }
}
