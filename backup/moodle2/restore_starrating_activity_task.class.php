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
 * Restore task for the star rating activity.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/starrating/backup/moodle2/restore_starrating_stepslib.php');

/**
 * The restore task, which adds the activity structure step.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_starrating_activity_task extends restore_activity_task {
    /**
     * No particular settings for this activity.
     *
     * @return void
     */
    protected function define_my_settings() {
    }

    /**
     * Add the structure step.
     *
     * @return void
     */
    protected function define_my_steps() {
        $this->add_step(new restore_starrating_activity_structure_step('starrating_structure', 'starrating.xml'));
    }

    /**
     * File areas the restore has to look at.
     *
     * @return array
     */
    public static function define_decode_contents() {
        return [new restore_decode_content('starrating', ['intro'], 'starrating')];
    }

    /**
     * Turn the backup placeholders back into links.
     *
     * @return array
     */
    public static function define_decode_rules() {
        return [
            new restore_decode_rule('STARRATINGVIEWBYID', '/mod/starrating/view.php?id=$1', 'course_module'),
            new restore_decode_rule('STARRATINGINDEX', '/mod/starrating/index.php?id=$1', 'course'),
        ];
    }

    /**
     * Log entries this activity restores.
     *
     * @return array
     */
    public static function define_restore_log_rules() {
        return [];
    }
}
