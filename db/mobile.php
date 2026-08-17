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
 * Moodle app support for the star rating filter.
 *
 * The app has no site plugin delegate for text filters, so the stars inside
 * formatted text cannot be made interactive there. What the app does support is
 * a course tab, and inside a tab a rating can be submitted without a line of
 * JavaScript: the template uses the app's core-site-plugins-call-ws directive,
 * which calls filter_rating_set_rating on tap and refreshes the tab.
 *
 * The init method restricts the tab to the courses that actually offer a
 * criterion, so it does not appear everywhere.
 *
 * @package    filter_rating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

$addons = [
    'filter_rating' => [
        'handlers' => [
            'courseratings' => [
                'delegate' => 'CoreCourseOptionsDelegate',
                'method' => 'mobile_course_ratings',
                'init' => 'mobile_init',
                'displaydata' => [
                    'title' => 'pluginname',
                    'class' => 'filter-rating-course-option',
                    'icon' => 'fas-star',
                ],
                'styles' => [
                    'url' => $CFG->wwwroot . '/filter/rating/styles.css',
                    'version' => (int) get_config('filter_rating', 'version'),
                ],
                'ptrenabled' => true,
            ],
        ],
        'lang' => [
            ['pluginname', 'filter_rating'],
            ['nocriteriaincourse', 'filter_rating'],
            ['ratingsaved', 'filter_rating'],
        ],
    ],
];
