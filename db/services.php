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

/**
 * Web services for the star rating activity.
 *
 * Both functions are part of the official Moodle Mobile service, which is what
 * lets the app store a rating without leaving the app.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [

    'mod_starrating_set_rating' => [
        'classname' => 'mod_starrating\external\set_rating',
        'methodname' => 'execute',
        'description' => 'Save the star rating of the current user for an activity.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/starrating:rate',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'mod_starrating_get_rating' => [
        'classname' => 'mod_starrating\external\get_rating',
        'methodname' => 'execute',
        'description' => 'Get the star rating of the current user and the average for an activity.',
        'type' => 'read',
        'ajax' => true,
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
];
