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

use filter_rating\local\rating_token;

/**
 * Tests for the signed rating links.
 *
 * @package    filter_rating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \filter_rating\local\rating_token
 */
final class rating_token_test extends \advanced_testcase {

    /**
     * A token is accepted only for exactly the link it was generated for.
     *
     * @return void
     */
    public function test_token_is_bound_to_every_parameter(): void {
        $this->resetAfterTest();

        $token = rating_token::generate(7, 42, 'teaching quality', 4);

        $this->assertTrue(rating_token::validate($token, 7, 42, 'teaching quality', 4));

        // Another user, context, criterion or number of stars must not pass.
        $this->assertFalse(rating_token::validate($token, 8, 42, 'teaching quality', 4));
        $this->assertFalse(rating_token::validate($token, 7, 43, 'teaching quality', 4));
        $this->assertFalse(rating_token::validate($token, 7, 42, 'materials', 4));
        $this->assertFalse(rating_token::validate($token, 7, 42, 'teaching quality', 5));
    }

    /**
     * An empty or wrong token is rejected.
     *
     * @return void
     */
    public function test_invalid_tokens_are_rejected(): void {
        $this->resetAfterTest();

        $this->assertFalse(rating_token::validate('', 7, 42, 'materials', 1));
        $this->assertFalse(rating_token::validate('not a token', 7, 42, 'materials', 1));
    }

    /**
     * The signing secret is created once and then reused.
     *
     * @return void
     */
    public function test_secret_is_stable(): void {
        $this->resetAfterTest();

        $first = rating_token::generate(7, 42, 'materials', 1);
        $secret = get_config('filter_rating', 'tokensecret');

        $this->assertNotEmpty($secret);
        $this->assertSame($first, rating_token::generate(7, 42, 'materials', 1));
        $this->assertSame($secret, get_config('filter_rating', 'tokensecret'));
    }
}
