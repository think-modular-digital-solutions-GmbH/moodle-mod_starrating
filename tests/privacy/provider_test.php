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

use context_module;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use mod_starrating\local\rating_manager;

/**
 * Tests for the privacy provider.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \mod_starrating\privacy\provider
 */
final class provider_test extends \core_privacy\tests\provider_testcase {

    /** @var \stdClass The activity instance. */
    protected $instance;

    /** @var \context_module The activity context. */
    protected $context;

    /** @var \stdClass The user whose data is requested. */
    protected $user;

    /** @var \stdClass Another user who rated the same activity. */
    protected $otheruser;

    /**
     * Two enrolled users rate the same activity.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $this->instance = $this->getDataGenerator()->create_module('starrating', [
            'course' => $course->id,
            'name' => 'Teaching quality',
        ]);
        $this->context = context_module::instance($this->instance->cmid);

        $this->user = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->otheruser = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($this->user);
        rating_manager::set_rating($this->context, $this->instance->id, 4);

        $this->setUser($this->otheruser);
        rating_manager::set_rating($this->context, $this->instance->id, 5);
    }

    /**
     * The activity context is reported for a user who rated in it.
     *
     * @return void
     */
    public function test_get_contexts_for_userid(): void {
        $contextlist = provider::get_contexts_for_userid((int) $this->user->id);

        $this->assertCount(1, $contextlist);
        $this->assertEquals($this->context->id, $contextlist->get_contextids()[0]);
    }

    /**
     * Both raters are reported for the activity context.
     *
     * @return void
     */
    public function test_get_users_in_context(): void {
        $userlist = new userlist($this->context, 'mod_starrating');
        provider::get_users_in_context($userlist);

        $userids = $userlist->get_userids();
        sort($userids);
        $expected = [(int) $this->user->id, (int) $this->otheruser->id];
        sort($expected);

        $this->assertSame($expected, $userids);
    }

    /**
     * Only the rating of the requesting user is exported.
     *
     * @return void
     */
    public function test_export_user_data(): void {
        $contextlist = new approved_contextlist($this->user, 'mod_starrating', [$this->context->id]);
        provider::export_user_data($contextlist);

        $writer = writer::with_context($this->context);
        $this->assertTrue($writer->has_any_data());

        $data = $writer->get_data([]);
        $this->assertSame(4, (int) $data->rating);
    }

    /**
     * Deleting a context removes every rating in it.
     *
     * @return void
     */
    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;

        provider::delete_data_for_all_users_in_context($this->context);

        $this->assertSame(0, $DB->count_records(rating_manager::TABLE, ['starratingid' => $this->instance->id]));
    }

    /**
     * Deleting one user leaves the ratings of everyone else in place.
     *
     * @return void
     */
    public function test_delete_data_for_user(): void {
        global $DB;

        $contextlist = new approved_contextlist($this->user, 'mod_starrating', [$this->context->id]);
        provider::delete_data_for_user($contextlist);

        $this->assertSame(0, $DB->count_records(rating_manager::TABLE, ['userid' => $this->user->id]));
        $this->assertSame(1, $DB->count_records(rating_manager::TABLE, ['userid' => $this->otheruser->id]));
    }

    /**
     * Deleting a list of users only touches those users.
     *
     * @return void
     */
    public function test_delete_data_for_users(): void {
        global $DB;

        $userlist = new approved_userlist($this->context, 'mod_starrating', [(int) $this->otheruser->id]);
        provider::delete_data_for_users($userlist);

        $this->assertSame(0, $DB->count_records(rating_manager::TABLE, ['userid' => $this->otheruser->id]));
        $this->assertSame(1, $DB->count_records(rating_manager::TABLE, ['userid' => $this->user->id]));
    }
}
