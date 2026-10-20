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
 * Backup steps for the star rating activity.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define the structure of one star rating activity for backup.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_starrating_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define the structure.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $starrating = new backup_nested_element('starrating', ['id'], [
            'name', 'intro', 'introformat', 'maxstars', 'showaverage', 'allowclear', 'allowfeedback',
            'timecreated', 'timemodified',
        ]);

        $ratings = new backup_nested_element('ratings');
        $rating = new backup_nested_element('rating', ['id'], [
            'userid', 'rating', 'feedback', 'timecreated', 'timemodified',
        ]);

        $starrating->add_child($ratings);
        $ratings->add_child($rating);

        $starrating->set_source_table('starrating', ['id' => backup::VAR_ACTIVITYID]);

        if ($userinfo) {
            $rating->set_source_table('starrating_rating', ['starratingid' => backup::VAR_PARENTID]);
        }

        $rating->annotate_ids('user', 'userid');
        $starrating->annotate_files('mod_starrating', 'intro', null);

        return $this->prepare_activity_structure($starrating);
    }
}
