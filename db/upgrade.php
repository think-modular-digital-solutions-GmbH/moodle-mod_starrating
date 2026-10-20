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
 * Upgrade steps for the star rating activity.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Bring the database up to date with db/install.xml.
 *
 * @param int $oldversion Version currently installed.
 * @return bool
 */
function xmldb_starrating_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026081804) {
        // The length of the rating scale became a per activity setting. Existing
        // activities keep the five stars they were rated with.
        $table = new xmldb_table('starrating');
        $field = new xmldb_field('maxstars', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '5', 'introformat');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026081804, 'starrating');
    }

    if ($oldversion < 2026102000) {
        // Activities can ask for a short text feedback along with the stars.
        // Existing activities keep asking for stars only.
        $table = new xmldb_table('starrating');
        $field = new xmldb_field('allowfeedback', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'allowclear');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('starrating_rating');
        $field = new xmldb_field('feedback', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'rating');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026102000, 'starrating');
    }

    return true;
}
