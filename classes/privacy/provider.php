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

namespace filter_rating\privacy;

use context;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use filter_rating\local\rating_manager;

/**
 * Privacy provider for the star rating filter.
 *
 * The plugin stores one row per user, course and criterion. Nothing is sent
 * anywhere outside this Moodle site.
 *
 * @package    filter_rating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {

    /**
     * Describe the personal data the plugin holds.
     *
     * @param collection $collection Collection to add to.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(rating_manager::TABLE, [
            'userid' => 'privacy:metadata:filter_rating:userid',
            'courseid' => 'privacy:metadata:filter_rating:courseid',
            'contextid' => 'privacy:metadata:filter_rating:contextid',
            'criterion' => 'privacy:metadata:filter_rating:criterion',
            'rating' => 'privacy:metadata:filter_rating:rating',
            'timecreated' => 'privacy:metadata:filter_rating:timecreated',
            'timemodified' => 'privacy:metadata:filter_rating:timemodified',
        ], 'privacy:metadata:filter_rating');

        return $collection;
    }

    /**
     * Contexts a user has left ratings in.
     *
     * @param int $userid User to look up.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $contextlist->add_from_sql(
            'SELECT DISTINCT contextid FROM {' . rating_manager::TABLE . '} WHERE userid = :userid',
            ['userid' => $userid]
        );

        return $contextlist;
    }

    /**
     * Users who have left ratings in a context.
     *
     * @param userlist $userlist List to add to.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        $userlist->add_from_sql(
            'userid',
            'SELECT userid FROM {' . rating_manager::TABLE . '} WHERE contextid = :contextid',
            ['contextid' => $userlist->get_context()->id]
        );
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
            $records = $DB->get_records(rating_manager::TABLE, [
                'contextid' => $context->id,
                'userid' => $userid,
            ], 'criterion ASC');

            if (empty($records)) {
                continue;
            }

            $ratings = [];
            foreach ($records as $record) {
                $ratings[] = (object) [
                    'criterion' => $record->criterion,
                    'rating' => $record->rating,
                    'timecreated' => transform::datetime($record->timecreated),
                    'timemodified' => transform::datetime($record->timemodified),
                ];
            }

            writer::with_context($context)->export_data(
                [get_string('privacy:path', 'filter_rating')],
                (object) ['ratings' => $ratings]
            );
        }
    }

    /**
     * Delete every rating stored in a context.
     *
     * @param context $context Context to purge.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        global $DB;

        $DB->delete_records(rating_manager::TABLE, ['contextid' => $context->id]);
    }

    /**
     * Delete the ratings of one user in the approved contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $contextids = $contextlist->get_contextids();
        if (empty($contextids)) {
            return;
        }

        [$insql, $params] = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED, 'ctx');
        $params['userid'] = $contextlist->get_user()->id;

        $DB->delete_records_select(rating_manager::TABLE, "userid = :userid AND contextid {$insql}", $params);
    }

    /**
     * Delete the ratings of the approved users in one context.
     *
     * @param approved_userlist $userlist Approved users.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'usr');
        $params['contextid'] = $userlist->get_context()->id;

        $DB->delete_records_select(rating_manager::TABLE, "contextid = :contextid AND userid {$insql}", $params);
    }
}
