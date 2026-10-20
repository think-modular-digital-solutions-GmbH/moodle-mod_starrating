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

namespace mod_starrating\output;

use context_module;
use mod_starrating\local\rating_manager;

/**
 * Tests for what the Moodle app is given to render.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \mod_starrating\output\mobile
 */
final class mobile_test extends \advanced_testcase {
    /**
     * Create an activity that asks for feedback, rated by a student.
     *
     * @param string $feedback Feedback the student gave.
     * @return \stdClass The activity instance, with cmid.
     */
    protected function create_rated_activity(string $feedback): \stdClass {
        rating_manager::reset_caches();

        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module('starrating', [
            'course' => $course->id,
            'allowfeedback' => 1,
        ]);
        $user = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($user);

        rating_manager::set_rating(context_module::instance($instance->cmid), $instance->id, 3, null, $feedback);
        rating_manager::reset_caches();

        return $instance;
    }

    /**
     * The course page carries the feedback field, escaped, and the button that saves it.
     *
     * @return void
     */
    public function test_course_page_has_feedback_field(): void {
        $this->resetAfterTest();

        $instance = $this->create_rated_activity('<b>Bold</b> & {{ 1 + 1 }}');
        $html = mobile::mobile_course_page(['cmid' => $instance->cmid])['templates'][0]['html'];

        $this->assertStringContainsString('<textarea', $html);
        $this->assertStringContainsString('name="feedback"', $html);
        $this->assertStringContainsString('Bold &amp; {{ 1 + 1 }}</textarea>', $html);
        $this->assertStringNotContainsString('<b>', $html);
        $this->assertStringContainsString('data-action="savefeedback"', $html);
        $this->assertStringContainsString('data-userrating="3"', $html);
    }

    /**
     * The activity page keeps the feedback out of the template, which Angular compiles.
     *
     * @return void
     */
    public function test_activity_page_binds_feedback_from_otherdata(): void {
        $this->resetAfterTest();

        $instance = $this->create_rated_activity('{{ 1 + 1 }}');
        $result = mobile::mobile_view_activity(['cmid' => $instance->cmid]);

        $this->assertStringNotContainsString('{{ 1 + 1 }}', $result['templates'][0]['html']);
        $this->assertStringContainsString('<ion-textarea', $result['templates'][0]['html']);
        $this->assertSame(['text' => '{{ 1 + 1 }}'], json_decode($result['otherdata']['feedback'], true));
    }
}
