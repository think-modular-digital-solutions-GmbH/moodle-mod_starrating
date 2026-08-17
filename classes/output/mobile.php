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

/**
 * Moodle app entry point for the star rating filter.
 *
 * @package    filter_rating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {

    /**
     * JavaScript the app runs once when it loads the site plugins.
     *
     * It makes the stars that the filter rendered on the server tappable inside
     * the app. There is nothing to render here, so no template is returned.
     *
     * @param array $args Arguments passed by the app, unused.
     * @return array Content for the app.
     */
    public static function mobile_init(array $args): array {
        global $CFG;

        return [
            'templates' => [],
            'javascript' => file_get_contents($CFG->dirroot . '/filter/rating/mobile/init.js'),
            'otherdata' => '',
        ];
    }
}
