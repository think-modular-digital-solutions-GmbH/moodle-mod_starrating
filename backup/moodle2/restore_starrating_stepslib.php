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
 * Restore steps for the star rating activity.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_starrating\local\rating_manager;

/**
 * Restore one star rating activity from a backup.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_starrating_activity_structure_step extends restore_activity_structure_step {
    /**
     * Define the paths to restore.
     *
     * @return array
     */
    protected function define_structure() {
        $paths = [];
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('starrating', '/activity/starrating');

        if ($userinfo) {
            $paths[] = new restore_path_element('starrating_rating', '/activity/starrating/ratings/rating');
        }

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restore the activity itself.
     *
     * @param array $data Data from the backup file.
     * @return void
     */
    protected function process_starrating($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // A backup taken before the scale was configurable carries no maxstars.
        $data->maxstars = rating_manager::normalise_max_stars($data->maxstars ?? 0);

        $newitemid = $DB->insert_record('starrating', $data);
        $this->apply_activity_instance($newitemid);

        $this->set_mapping('starrating', $oldid, $newitemid);
    }

    /**
     * Restore one rating.
     *
     * @param array $data Data from the backup file.
     * @return void
     */
    protected function process_starrating_rating($data) {
        global $DB;

        $data = (object) $data;
        $data->starratingid = $this->get_new_parentid('starrating');
        $data->userid = $this->get_mappingid('user', $data->userid);

        if (empty($data->userid)) {
            return;
        }

        $DB->insert_record('starrating_rating', $data);
    }

    /**
     * Restore the files of the description.
     *
     * @return void
     */
    protected function after_execute() {
        $this->add_related_files('mod_starrating', 'intro', null);
    }
}
