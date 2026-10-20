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

use mod_starrating\local\rating_manager;
use mod_starrating\local\report;

/**
 * Tests for the aggregated figures behind the reports.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \mod_starrating\local\report
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\mod_starrating\local\report::class)]
final class report_test extends \advanced_testcase {
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
     * Create one activity and have a fresh student rate it.
     *
     * @param \stdClass $course The course to put the activity in.
     * @param string $name Name of the activity, which is the criterion.
     * @param int $maxstars Length of the rating scale.
     * @param int[] $ratings One rating per student to enrol.
     * @return \stdClass The instance.
     */
    protected function create_rated_activity(\stdClass $course, string $name, int $maxstars, array $ratings) {
        $instance = $this->getDataGenerator()->create_module('starrating', [
            'course' => $course->id,
            'name' => $name,
            'maxstars' => $maxstars,
        ]);
        $context = \context_module::instance($instance->cmid);

        foreach ($ratings as $rating) {
            $user = $this->getDataGenerator()->create_and_enrol($course, 'student');
            $this->setUser($user);
            rating_manager::set_rating($context, $instance->id, $rating);
        }

        return $instance;
    }

    /**
     * On one scale the average is the plain average, in stars.
     *
     * @return void
     */
    public function test_get_summary_on_one_scale(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $this->create_rated_activity($course, 'Teaching quality', 5, [4, 5, 3]);

        $rows = report::get_summary(0, '', report::GROUP_COURSE_CRITERION);

        $this->assertCount(1, $rows);
        $this->assertSame(3, $rows[0]->numratings);
        $this->assertSame(5, $rows[0]->maxstars);
        $this->assertSame(4.0, $rows[0]->avgrating);
    }

    /**
     * Grouping several scales together reports a share of the scale, not stars.
     *
     * Four of five and eight of ten are the same judgement, so both come out at
     * the same share, and the group refuses to name a scale it does not have.
     *
     * @return void
     */
    public function test_get_summary_over_mixed_scales(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $this->create_rated_activity($course, 'Teaching quality', 5, [4]);
        $this->create_rated_activity($course, 'Materials', 10, [8]);

        // Grouped per activity, each row still has a scale of its own.
        $rows = report::get_summary(0, '', report::GROUP_COURSE_CRITERION);
        $this->assertCount(2, $rows);
        $byname = [];
        foreach ($rows as $row) {
            $byname[$row->criterion] = $row;
        }
        $this->assertSame(5, $byname['Teaching quality']->maxstars);
        $this->assertSame(4.0, $byname['Teaching quality']->avgrating);
        $this->assertSame(10, $byname['Materials']->maxstars);
        $this->assertSame(8.0, $byname['Materials']->avgrating);

        // Grouped per course, the two scales meet and no scale can be named.
        $rows = report::get_summary(0, '', report::GROUP_COURSE);
        $this->assertCount(1, $rows);
        $this->assertSame(2, $rows[0]->numratings);
        $this->assertSame(0, $rows[0]->maxstars);
        $this->assertSame(0.8, $rows[0]->avgfraction);

        $overall = report::get_overall(0, '');
        $this->assertSame(2, $overall->count);
        $this->assertSame(0, $overall->maxstars);
        $this->assertSame(0.8, $overall->fraction);
    }

    /**
     * Nothing rated means nothing to average.
     *
     * @return void
     */
    public function test_get_overall_without_ratings(): void {
        $this->resetAfterTest();

        $overall = report::get_overall(0, '');

        $this->assertSame(0, $overall->count);
        $this->assertSame(0, $overall->maxstars);
        $this->assertSame(0.0, $overall->average);
    }

    /**
     * The course report lists every activity of the course, rated or not.
     *
     * @return void
     */
    public function test_get_course_summary(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $other = $this->getDataGenerator()->create_course();

        $this->create_rated_activity($course, 'Teaching quality', 10, [6, 8]);
        $this->create_rated_activity($course, 'Materials', 5, []);
        $this->create_rated_activity($other, 'Teaching quality', 5, [1]);

        $rows = report::get_course_summary($course->id);

        $this->assertCount(2, $rows);

        $byname = [];
        foreach ($rows as $row) {
            $byname[$row->name] = $row;
        }

        $this->assertSame(2, $byname['Teaching quality']->numratings);
        $this->assertSame(10, $byname['Teaching quality']->maxstars);
        $this->assertSame(7.0, $byname['Teaching quality']->avgrating);
        $this->assertNotEmpty($byname['Teaching quality']->cmid);

        // An activity waiting for its first rating is a result of its own.
        $this->assertSame(0, $byname['Materials']->numratings);
        $this->assertSame(5, $byname['Materials']->maxstars);
        $this->assertSame(0.0, $byname['Materials']->avgrating);
    }

    /**
     * The activity filter of the course report only offers that course.
     *
     * @return void
     */
    public function test_get_activity_options_for_one_course(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $other = $this->getDataGenerator()->create_course();

        $this->create_rated_activity($course, 'Teaching quality', 5, []);
        $this->create_rated_activity($other, 'Materials', 5, []);

        $options = report::get_activity_options($course->id);

        $this->assertArrayHasKey('Teaching quality', $options);
        $this->assertArrayNotHasKey('Materials', $options);
        $this->assertArrayHasKey('', $options);

        // Site wide, both are on offer.
        $this->assertArrayHasKey('Materials', report::get_activity_options());
    }
}
