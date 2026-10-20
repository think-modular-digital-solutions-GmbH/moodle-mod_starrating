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
     * Drop the request level caches of the manager.
     *
     * They are keyed on instance id, and PHPUnit resets the sequences between
     * tests, so without this a later test can read the figures of an earlier one.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        rating_manager::reset_caches();
    }

    /**
     * Create a course with one star rating activity.
     *
     * @param int|null $maxstars Length of the rating scale, null for the default.
     * @return array The course, the instance and its context.
     */
    protected function create_activity(?int $maxstars = null): array {
        $course = $this->getDataGenerator()->create_course();
        $record = ['course' => $course->id, 'name' => 'Teaching quality'];

        if ($maxstars !== null) {
            $record['maxstars'] = $maxstars;
        }

        $instance = $this->getDataGenerator()->create_module('starrating', $record);
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
     * What counts as out of range follows the scale of the activity.
     *
     * @return void
     */
    public function test_set_rating_follows_the_scale_of_the_activity(): void {
        $this->resetAfterTest();

        [$course, $instance, $context] = $this->create_activity(10);
        $user = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($user);

        // Nine stars are out of range on a scale of five and fine on a scale of ten.
        rating_manager::set_rating($context, $instance->id, 9);
        $this->assertSame(9, rating_manager::get_user_rating($instance->id));

        $this->expectException(\moodle_exception::class);
        rating_manager::set_rating($context, $instance->id, 11);
    }

    /**
     * The scale of an activity is read back as it was configured.
     *
     * @return void
     */
    public function test_get_max_stars(): void {
        $this->resetAfterTest();

        [, $five] = $this->create_activity();
        [, $seven] = $this->create_activity(7);

        $this->assertSame(rating_manager::DEFAULT_MAX_STARS, rating_manager::get_max_stars($five->id));
        $this->assertSame(7, rating_manager::get_max_stars($seven->id));
    }

    /**
     * A scale length that this plugin does not support falls back to the default.
     *
     * @return void
     */
    public function test_normalise_max_stars(): void {
        $this->assertSame(2, rating_manager::normalise_max_stars(2));
        $this->assertSame(10, rating_manager::normalise_max_stars(10));
        $this->assertSame(rating_manager::DEFAULT_MAX_STARS, rating_manager::normalise_max_stars(1));
        $this->assertSame(rating_manager::DEFAULT_MAX_STARS, rating_manager::normalise_max_stars(11));
        $this->assertSame(rating_manager::DEFAULT_MAX_STARS, rating_manager::normalise_max_stars(0));
        $this->assertSame(rating_manager::DEFAULT_MAX_STARS, rating_manager::normalise_max_stars(null));
    }

    /**
     * The highest rating given is what limits shortening the scale.
     *
     * @return void
     */
    public function test_get_highest_rating(): void {
        $this->resetAfterTest();

        [$course, $instance, $context] = $this->create_activity(10);
        $this->assertSame(0, rating_manager::get_highest_rating($instance->id));

        foreach ([3, 8, 5] as $rating) {
            $user = $this->getDataGenerator()->create_and_enrol($course, 'student');
            $this->setUser($user);
            rating_manager::set_rating($context, $instance->id, $rating);
        }

        $this->assertSame(8, rating_manager::get_highest_rating($instance->id));
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
     * The text feedback is stored with the rating, kept, replaced and removed with it.
     *
     * @return void
     */
    public function test_set_rating_stores_feedback(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module('starrating', [
            'course' => $course->id,
            'allowfeedback' => 1,
        ]);
        $context = context_module::instance($instance->cmid);
        $user = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($user);

        $this->assertTrue(rating_manager::allows_feedback($instance->id));
        $this->assertSame('', rating_manager::get_user_feedback($instance->id));

        rating_manager::set_rating($context, $instance->id, 4, null, 'Clear and well paced');
        $this->assertSame('Clear and well paced', rating_manager::get_user_feedback($instance->id));

        // Leaving the feedback out, as a click on a course page star in the app does, keeps it.
        rating_manager::set_rating($context, $instance->id, 2);
        $this->assertSame(2, rating_manager::get_user_rating($instance->id));
        $this->assertSame('Clear and well paced', rating_manager::get_user_feedback($instance->id));

        // An empty feedback removes the text but keeps the stars.
        rating_manager::set_rating($context, $instance->id, 2, null, '   ');
        $this->assertSame(2, rating_manager::get_user_rating($instance->id));
        $this->assertNull($DB->get_field(rating_manager::TABLE, 'feedback', ['userid' => $user->id]));

        // Withdrawing the rating takes the feedback with it.
        rating_manager::set_rating($context, $instance->id, 3, null, 'Second thoughts');
        rating_manager::set_rating($context, $instance->id, 0, null, 'Ignored');
        $this->assertSame('', rating_manager::get_user_feedback($instance->id));
        $this->assertSame(0, $DB->count_records(rating_manager::TABLE, ['userid' => $user->id]));
    }

    /**
     * An activity that does not ask for feedback stores none, whatever is sent.
     *
     * @return void
     */
    public function test_set_rating_ignores_feedback_when_not_asked_for(): void {
        global $DB;

        $this->resetAfterTest();

        [$course, $instance, $context] = $this->create_activity();
        $user = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($user);

        $this->assertFalse(rating_manager::allows_feedback($instance->id));

        rating_manager::set_rating($context, $instance->id, 4, null, 'Nobody asked');

        $this->assertSame(4, rating_manager::get_user_rating($instance->id));
        $this->assertNull($DB->get_field(rating_manager::TABLE, 'feedback', ['userid' => $user->id]));
    }

    /**
     * Feedback is folded into one clean line and cut to length.
     *
     * @return void
     */
    public function test_normalise_feedback(): void {
        $this->assertSame('', rating_manager::normalise_feedback(null));
        $this->assertSame('', rating_manager::normalise_feedback("  \n\t "));
        $this->assertSame('Two lines', rating_manager::normalise_feedback("Two\r\n  lines"));
        $this->assertSame('bold', rating_manager::normalise_feedback('<b>bold</b>'));

        $long = str_repeat('ä', rating_manager::MAX_FEEDBACK_LENGTH + 20);
        $this->assertSame(
            rating_manager::MAX_FEEDBACK_LENGTH,
            \core_text::strlen(rating_manager::normalise_feedback($long))
        );
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
