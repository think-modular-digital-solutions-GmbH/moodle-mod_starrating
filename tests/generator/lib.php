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
 * Test data generator for the star rating activity.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Creates star rating activities for tests.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_starrating_generator extends testing_module_generator {
    /**
     * Create an activity instance with sensible defaults.
     *
     * @param array|stdClass|null $record Instance data.
     * @param array|null $options Generator options.
     * @return stdClass The created instance.
     */
    public function create_instance($record = null, ?array $options = null) {
        $record = (object) (array) $record;

        $defaults = [
            'name' => 'Star rating',
            'intro' => '',
            'introformat' => FORMAT_HTML,
            'maxstars' => \mod_starrating\local\rating_manager::DEFAULT_MAX_STARS,
            'showaverage' => 1,
            'allowclear' => 1,
        ];

        foreach ($defaults as $field => $value) {
            if (!isset($record->{$field})) {
                $record->{$field} = $value;
            }
        }

        return parent::create_instance($record, (array) $options);
    }

    /**
     * Store a rating a user gave to an activity.
     *
     * Goes through the manager rather than the database, so that a seeded rating
     * is indistinguishable from one somebody clicked.
     *
     * @param array|stdClass $record Needs starratingid, userid and rating.
     * @return stdClass The stored rating record.
     */
    public function create_rating($record): stdClass {
        global $DB;

        $record = (object) (array) $record;

        foreach (['starratingid', 'userid', 'rating'] as $field) {
            if (!isset($record->{$field})) {
                throw new coding_exception('A star rating needs a ' . $field . '.');
            }
        }

        $cm = get_coursemodule_from_instance('starrating', $record->starratingid, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);

        \mod_starrating\local\rating_manager::set_rating(
            $context,
            (int) $record->starratingid,
            (int) $record->rating,
            (int) $record->userid
        );

        return $DB->get_record(\mod_starrating\local\rating_manager::TABLE, [
            'starratingid' => $record->starratingid,
            'userid' => $record->userid,
        ]);
    }
}
