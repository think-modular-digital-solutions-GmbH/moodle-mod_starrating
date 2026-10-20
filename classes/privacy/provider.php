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

namespace mod_starrating\privacy;

use context;
use context_module;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use mod_starrating\local\rating_manager;

/**
 * Privacy provider for the star rating activity.
 *
 * One row per user and activity. Nothing is sent anywhere outside this site.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Describe the personal data the plugin holds.
     *
     * @param collection $collection Collection to add to.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(rating_manager::TABLE, [
            'starratingid' => 'privacy:metadata:starrating_rating:starratingid',
            'userid' => 'privacy:metadata:starrating_rating:userid',
            'rating' => 'privacy:metadata:starrating_rating:rating',
            'feedback' => 'privacy:metadata:starrating_rating:feedback',
            'timecreated' => 'privacy:metadata:starrating_rating:timecreated',
            'timemodified' => 'privacy:metadata:starrating_rating:timemodified',
        ], 'privacy:metadata:starrating_rating');

        return $collection;
    }

    /**
     * Activity contexts the user has rated in.
     *
     * @param int $userid User to look up.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = 'SELECT ctx.id
                  FROM {' . rating_manager::TABLE . '} r
                  JOIN {starrating} s ON s.id = r.starratingid
                  JOIN {modules} m ON m.name = :modname
                  JOIN {course_modules} cm ON cm.instance = s.id AND cm.module = m.id
                  JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel
                 WHERE r.userid = :userid';

        $contextlist->add_from_sql($sql, [
            'modname' => 'starrating',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ]);

        return $contextlist;
    }

    /**
     * Users who have rated in an activity.
     *
     * @param userlist $userlist List to add to.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof context_module) {
            return;
        }

        $sql = 'SELECT r.userid
                  FROM {' . rating_manager::TABLE . '} r
                  JOIN {starrating} s ON s.id = r.starratingid
                  JOIN {modules} m ON m.name = :modname
                  JOIN {course_modules} cm ON cm.instance = s.id AND cm.module = m.id
                 WHERE cm.id = :cmid';

        $userlist->add_from_sql('userid', $sql, ['modname' => 'starrating', 'cmid' => $context->instanceid]);
    }

    /**
     * Export the ratings of the requesting user.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof context_module) {
                continue;
            }

            $instanceid = self::get_instanceid($context);
            if (!$instanceid) {
                continue;
            }

            $record = $DB->get_record(rating_manager::TABLE, [
                'starratingid' => $instanceid,
                'userid' => $userid,
            ]);

            if (!$record) {
                continue;
            }

            $data = helper::get_context_data($context, $contextlist->get_user());
            $data->rating = $record->rating;
            if ((string) $record->feedback !== '') {
                $data->feedback = $record->feedback;
            }
            $data->timecreated = transform::datetime($record->timecreated);
            $data->timemodified = transform::datetime($record->timemodified);

            writer::with_context($context)->export_data([], $data);
            helper::export_context_files($context, $contextlist->get_user());
        }
    }

    /**
     * Delete every rating stored in an activity.
     *
     * @param context $context Context to purge.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        global $DB;

        if (!$context instanceof context_module) {
            return;
        }

        if ($instanceid = self::get_instanceid($context)) {
            $DB->delete_records(rating_manager::TABLE, ['starratingid' => $instanceid]);
        }
    }

    /**
     * Delete the ratings of one user in the approved activities.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof context_module) {
                continue;
            }

            if ($instanceid = self::get_instanceid($context)) {
                $DB->delete_records(rating_manager::TABLE, ['starratingid' => $instanceid, 'userid' => $userid]);
            }
        }
    }

    /**
     * Delete the ratings of the approved users in one activity.
     *
     * @param approved_userlist $userlist Approved users.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof context_module) {
            return;
        }

        $instanceid = self::get_instanceid($context);
        $userids = $userlist->get_userids();
        if (!$instanceid || empty($userids)) {
            return;
        }

        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'usr');
        $params['starratingid'] = $instanceid;

        $DB->delete_records_select(rating_manager::TABLE, "starratingid = :starratingid AND userid {$insql}", $params);
    }

    /**
     * The activity instance behind a module context.
     *
     * @param context_module $context The module context.
     * @return int Instance id, 0 when the context is not a star rating.
     */
    protected static function get_instanceid(context_module $context): int {
        $cm = get_coursemodule_from_id('starrating', $context->instanceid, 0, false, IGNORE_MISSING);

        return $cm ? (int) $cm->instance : 0;
    }
}
