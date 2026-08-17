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
use filter_rating\local\token_processor;

/**
 * Tests for the token replacement.
 *
 * @package    filter_rating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \filter_rating\local\token_processor
 */
final class token_processor_test extends \advanced_testcase {

    /**
     * Set up a course, a student and a page context for rendering.
     *
     * @return \stdClass The course.
     */
    protected function setup_course(): \stdClass {
        global $PAGE;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($user);

        $PAGE->set_context(context_course::instance($course->id));
        $PAGE->set_url('/course/view.php', ['id' => $course->id]);

        return $course;
    }

    /**
     * Text without a token is returned untouched.
     *
     * @return void
     */
    public function test_text_without_token_is_untouched(): void {
        $this->resetAfterTest();

        $text = '<p>Nothing to see here, not even a {{ rating token.</p>';
        $this->assertSame($text, token_processor::process($text, \context_system::instance()));
    }

    /**
     * The token is replaced by the star widget.
     *
     * @return void
     */
    public function test_token_is_replaced(): void {
        $course = $this->setup_course();
        $context = context_course::instance($course->id);

        $result = token_processor::process('<p>Rate this: {{ rating:teaching quality }}</p>', $context);

        $this->assertStringNotContainsString('{{', $result);
        $this->assertStringContainsString('filter-rating', $result);
        $this->assertStringContainsString('fa-star', $result);
        $this->assertStringContainsString('data-criterion="teaching quality"', $result);
        $this->assertStringContainsString('/filter/rating/rate.php', $result);
    }

    /**
     * Several tokens in one text are all replaced, and the token is case insensitive.
     *
     * @return void
     */
    public function test_several_tokens_are_replaced(): void {
        $course = $this->setup_course();
        $context = context_course::instance($course->id);

        $result = token_processor::process('{{rating:one}} and {{ Rating : two }}', $context);

        $this->assertStringContainsString('data-criterion="one"', $result);
        $this->assertStringContainsString('data-criterion="two"', $result);
    }

    /**
     * The stars show the rating the current user gave.
     *
     * @return void
     */
    public function test_widget_shows_the_current_user_rating(): void {
        $course = $this->setup_course();
        $context = context_course::instance($course->id);

        rating_manager::set_rating($context, $course->id, 'materials', 3);

        $result = token_processor::process('{{ rating:materials }}', $context);

        $this->assertStringContainsString('data-userrating="3"', $result);
        $this->assertSame(3, substr_count($result, 'filter-rating-on'));
    }

    /**
     * A token without a usable criterion is left alone.
     *
     * @return void
     */
    public function test_empty_criterion_is_left_alone(): void {
        $course = $this->setup_course();
        $context = context_course::instance($course->id);

        $text = 'Empty: {{ rating: }}';
        $this->assertSame($text, token_processor::process($text, $context));
    }

    /**
     * The cheap pre-check does not produce false negatives for real tokens.
     *
     * @return void
     */
    public function test_contains_token(): void {
        $this->assertTrue(token_processor::contains_token('{{ rating:x }}'));
        $this->assertTrue(token_processor::contains_token('{{RATING:x}}'));
        $this->assertFalse(token_processor::contains_token('rating without braces'));
        $this->assertFalse(token_processor::contains_token('{{ something else }}'));
    }
}
