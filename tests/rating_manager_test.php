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

namespace filter_rating;

use context_course;
use filter_rating\local\rating_manager;

/**
 * Tests for the rating manager.
 *
 * @package    filter_rating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \filter_rating\local\rating_manager
 */
final class rating_manager_test extends \advanced_testcase {

    /**
     * Criteria are trimmed, stripped of markup and capped in length.
     *
     * @return void
     */
    public function test_normalise_criterion(): void {
        $this->assertSame('teaching quality', rating_manager::normalise_criterion('  teaching   quality '));
        $this->assertSame('teaching quality', rating_manager::normalise_criterion("teaching\tquality"));
        $this->assertSame('alert(1)', rating_manager::normalise_criterion('<b>alert(1)</b>'));
        $this->assertSame('', rating_manager::normalise_criterion('   '));
        $this->assertSame(
            rating_manager::CRITERION_MAXLENGTH,
            \core_text::strlen(rating_manager::normalise_criterion(str_repeat('a', 200)))
        );
    }

    /**
     * A rating is stored once per user, course and criterion.
     *
     * @return void
     */
    public function test_set_rating_stores_one_row_per_user(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);
        $user = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($user);

        rating_manager::set_rating($context, $course->id, 'teaching quality', 4);
        $this->assertSame(4, rating_manager::get_user_rating($course->id, 'teaching quality'));

        // Rating again replaces the earlier value instead of adding a row.
        rating_manager::set_rating($context, $course->id, 'teaching quality', 2);
        $this->assertSame(2, rating_manager::get_user_rating($course->id, 'teaching quality'));
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

        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);
        $user = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($user);

        rating_manager::set_rating($context, $course->id, 'materials', 5);
        rating_manager::set_rating($context, $course->id, 'materials', 0);

        $this->assertSame(0, rating_manager::get_user_rating($course->id, 'materials'));
        $this->assertSame(0, $DB->count_records(rating_manager::TABLE, ['userid' => $user->id]));
    }

    /**
     * Out of range ratings are rejected.
     *
     * @return void
     */
    public function test_set_rating_rejects_out_of_range(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);
        $user = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($user);

        $this->expectException(\moodle_exception::class);
        rating_manager::set_rating($context, $course->id, 'materials', 6);
    }

    /**
     * The average is calculated over all users of one course and criterion.
     *
     * @return void
     */
    public function test_get_stats(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $othercourse = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);
        $othercontext = context_course::instance($othercourse->id);

        foreach ([4, 5, 3] as $index => $rating) {
            $user = $this->getDataGenerator()->create_and_enrol($course, 'student');
            $this->setUser($user);
            rating_manager::set_rating($context, $course->id, 'materials', $rating);

            // The same criterion in another course must not leak into the average.
            rating_manager::set_rating($othercontext, $othercourse->id, 'materials', 1);
        }

        $stats = rating_manager::get_stats($course->id, 'materials');
        $this->assertSame(3, $stats->count);
        $this->assertSame(4.0, $stats->average);

        $emptystats = rating_manager::get_stats($course->id, 'never rated');
        $this->assertSame(0, $emptystats->count);
        $this->assertSame(0.0, $emptystats->average);
    }

    /**
     * Ratings outside a course belong to the site course.
     *
     * @return void
     */
    public function test_get_courseid_from_context(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        $this->assertSame((int) $course->id, rating_manager::get_courseid_from_context(context_course::instance($course->id)));
        $this->assertSame((int) SITEID, rating_manager::get_courseid_from_context(\context_system::instance()));
    }

    /**
     * Guests and users without the capability may not rate.
     *
     * @return void
     */
    public function test_can_rate(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);

        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        $this->assertTrue(rating_manager::can_rate($context));

        $this->setGuestUser();
        $this->assertFalse(rating_manager::can_rate($context));
    }
}
