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
 * The filter runs on the server, so the app receives the finished stars inside
 * any formatted text. What it does not have is a way to save a rating, because
 * the app offers no site plugin delegate for text filters.
 *
 * The init handler below closes that gap: the app runs its JavaScript when it
 * loads the site plugins, and that code intercepts the taps on the stars and
 * saves the rating through the filter_rating_set_rating web service. Ratings
 * are therefore given inside the app, without opening a browser and without
 * logging in a second time.
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
            'rating' => [
                // No delegate: this handler contributes behaviour, not a screen.
                'init' => 'mobile_init',
                'styles' => [
                    'url' => $CFG->wwwroot . '/filter/rating/styles.css',
                    'version' => (int) get_config('filter_rating', 'version'),
                ],
            ],
        ],
    ],
];
