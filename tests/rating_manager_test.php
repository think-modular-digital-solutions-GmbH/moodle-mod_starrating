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

namespace mod_starrating;

use context_module;
use mod_starrating\local\rating_manager;

/**
 * Tests for the rating manager.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \mod_starrating\local\rating_manager
 */
final class rating_manager_test extends \advanced_testcase {

    /**
     * Create a course with one star rating activity.
     *
     * @return array The course, the instance and its context.
     */
    protected function create_activity(): array {
        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module('starrating', [
            'course' => $course->id,
            'name' => 'Teaching quality',
        ]);
        $context = context_module::instance($instance->cmid);

        return [$course, $instance, $context];
    }

    /**
     * A rating is stored once per user and activity.
     *
     * @return void
     */
    public function test_set_rating_stores_one_row_per_user(): void {
        global $DB;

        $this->resetAfterTest();

        [$course, $instance, $context] = $this->create_activity();
        $user = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($user);

        rating_manager::set_rating($context, $instance->id, 4);
        $this->assertSame(4, rating_manager::get_user_rating($instance->id));

        // Rating again replaces the earlier value instead of adding a row.
        rating_manager::set_rating($context, $instance->id, 2);
        $this->assertSame(2, rating_manager::get_user_rating($instance->id));
        $this->assertSame(1, $DB->count_records(rating_manager::TABLE, ['userid' => $user->id]));
    }

    /**
     * Rating with zero stars withdraws the rating.
     *
     * @return void
     */
    public function test_set_rating_zero_withdraws(): void {
        global $DB;

        $this->resetAfterTest();

        [$course, $instance, $context] = $this->create_activity();
        $user = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($user);

        rating_manager::set_rating($context, $instance->id, 5);
        rating_manager::set_rating($context, $instance->id, 0);

        $this->assertSame(0, rating_manager::get_user_rating($instance->id));
        $this->assertSame(0, $DB->count_records(rating_manager::TABLE, ['userid' => $user->id]));
    }

    /**
     * Out of range ratings are rejected.
     *
     * @return void
     */
    public function test_set_rating_rejects_out_of_range(): void {
        $this->resetAfterTest();

        [$course, $instance, $context] = $this->create_activity();
        $user = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($user);

        $this->expectException(\moodle_exception::class);
        rating_manager::set_rating($context, $instance->id, 6);
    }

    /**
     * The average is calculated over all users of one activity.
     *
     * @return void
     */
    public function test_get_stats(): void {
        $this->resetAfterTest();

        [$course, $instance, $context] = $this->create_activity();

        $other = $this->getDataGenerator()->create_module('starrating', [
            'course' => $course->id,
            'name' => 'Materials',
        ]);
        $othercontext = context_module::instance($other->cmid);

        foreach ([4, 5, 3] as $rating) {
            $user = $this->getDataGenerator()->create_and_enrol($course, 'student');
            $this->setUser($user);
            rating_manager::set_rating($context, $instance->id, $rating);

            // A second activity in the same course must not leak into the average.
            rating_manager::set_rating($othercontext, $other->id, 1);
        }

        $stats = rating_manager::get_stats($instance->id);
        $this->assertSame(3, $stats->count);
        $this->assertSame(4.0, $stats->average);

        $this->assertSame(3, rating_manager::get_stats($other->id)->count);
        $this->assertSame(1.0, rating_manager::get_stats($other->id)->average);
    }

    /**
     * An activity nobody has rated reports nothing.
     *
     * @return void
     */
    public function test_get_stats_without_ratings(): void {
        $this->resetAfterTest();

        [, $instance] = $this->create_activity();

        $stats = rating_manager::get_stats($instance->id);
        $this->assertSame(0, $stats->count);
        $this->assertSame(0.0, $stats->average);
    }

    /**
     * Guests and users without the capability may not rate.
     *
     * @return void
     */
    public function test_can_rate(): void {
        $this->resetAfterTest();

        [$course, , $context] = $this->create_activity();

        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        $this->assertTrue(rating_manager::can_rate($context));

        $this->setGuestUser();
        $this->assertFalse(rating_manager::can_rate($context));
    }

    /**
     * Deleting the activity removes its ratings.
     *
     * @return void
     */
    public function test_delete_instance_removes_ratings(): void {
        global $DB;

        $this->resetAfterTest();

        [$course, $instance, $context] = $this->create_activity();
        $user = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($user);
        rating_manager::set_rating($context, $instance->id, 3);

        $this->setAdminUser();
        course_delete_module($instance->cmid);

        $this->assertSame(0, $DB->count_records(rating_manager::TABLE, ['starratingid' => $instance->id]));
    }
}
