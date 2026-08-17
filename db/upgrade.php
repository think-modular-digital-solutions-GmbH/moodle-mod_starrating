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
 * Upgrade steps for the star rating filter.
 *
 * @package    filter_rating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the plugin.
 *
 * @param int $oldversion The version currently installed.
 * @return bool
 */
function xmldb_filter_rating_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026081704) {

        // The criterion registry lets the Moodle app list what a course offers,
        // where no filter token is rendered to read the criteria from.
        $table = new xmldb_table('filter_rating_criterion');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('criterion', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        $table->add_index('courseid-criterion', XMLDB_INDEX_UNIQUE, ['courseid', 'criterion']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Seed it with everything that has already been rated, so that existing
        // sites do not have to wait for the tokens to be rendered again.
        $sql = 'SELECT courseid, criterion, MIN(timecreated) AS firstseen
                  FROM {filter_rating}
              GROUP BY courseid, criterion';
        $recordset = $DB->get_recordset_sql($sql);
        foreach ($recordset as $record) {
            $conditions = ['courseid' => $record->courseid, 'criterion' => $record->criterion];
            if (!$DB->record_exists('filter_rating_criterion', $conditions)) {
                $DB->insert_record('filter_rating_criterion', (object) ($conditions + [
                    'timecreated' => $record->firstseen,
                ]));
            }
        }
        $recordset->close();

        upgrade_plugin_savepoint(true, 2026081704, 'filter', 'rating');
    }

    return true;
}
