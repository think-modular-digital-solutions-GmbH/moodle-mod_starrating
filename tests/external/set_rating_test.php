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

namespace mod_starrating\external;

use core_external\external_api;
use mod_starrating\local\rating_manager;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * Tests for the web service the Moodle app saves ratings with.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \mod_starrating\external\set_rating
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\mod_starrating\external\set_rating::class)]
final class set_rating_test extends \externallib_advanced_testcase {
    /**
     * The feedback travels with the stars, and leaving it out keeps what is stored.
     *
     * @return void
     */
    public function test_execute_with_feedback(): void {
        $this->resetAfterTest();
        rating_manager::reset_caches();

        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module('starrating', [
            'course' => $course->id,
            'allowfeedback' => 1,
        ]);
        $user = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($user);

        $result = set_rating::execute($instance->cmid, 4, 'Clear and well paced');
        $result = external_api::clean_returnvalue(set_rating::execute_returns(), $result);

        $this->assertSame(4, $result['userrating']);
        $this->assertTrue($result['allowfeedback']);
        $this->assertSame('Clear and well paced', $result['feedback']);

        // The course page in the app sends stars only.
        $result = set_rating::execute($instance->cmid, 5);
        $result = external_api::clean_returnvalue(set_rating::execute_returns(), $result);

        $this->assertSame(5, $result['userrating']);
        $this->assertSame('Clear and well paced', $result['feedback']);
    }
}
