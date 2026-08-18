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
 * Moodle app support for the star rating activity.
 *
 * Being an activity is what makes the app work here. The app registers a
 * content link handler for every site plugin module, so a link to
 * /mod/starrating/view.php opens the activity natively in the app instead of a
 * browser, and the activity page is a site plugin template, where a rating can
 * be submitted declaratively through core-site-plugins-call-ws.
 *
 * coursepagemethod renders the stars inline on the course page, the way they
 * appear on the web. The app pipes that content through core-format-text, where
 * its own directives cannot run, so the init handler ships the one thing that
 * cannot be declared: the tap on a course page star, which saves the rating and
 * repaints the stars without opening anything.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

$addons = [
    'mod_starrating' => [
        'handlers' => [
            'starrating' => [
                'delegate' => 'CoreCourseModuleDelegate',
                'method' => 'mobile_view_activity',
                'coursepagemethod' => 'mobile_course_page',
                'init' => 'mobile_init',
                'displaydata' => [
                    'icon' => $CFG->wwwroot . '/mod/starrating/pix/monologo.svg',
                    'class' => '',
                ],
                'styles' => [
                    'url' => $CFG->wwwroot . '/mod/starrating/styles.css',
                    'version' => (int) get_config('mod_starrating', 'version'),
                ],
                'ptrenabled' => true,
            ],
        ],
        'lang' => [
            ['pluginname', 'mod_starrating'],
            ['modulename', 'mod_starrating'],
            ['ratingsaved', 'mod_starrating'],
            ['ratingcleared', 'mod_starrating'],
        ],
    ],
];
