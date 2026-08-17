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

namespace filter_rating\local;

/**
 * Signs the rating links so that they cannot be forged.
 *
 * A star is a link, and that link has to work in two different sessions: the
 * browser session, and the separate session the Moodle app creates when it
 * opens the link with auto login. A session key cannot cover both, so each link
 * carries a token that is bound to the user and to exactly one rating instead.
 *
 * Compared to a session key this is the narrower credential: even if a token
 * leaks it allows nothing but setting that one rating of that one user, and no
 * session key ever ends up inside filtered text.
 *
 * @package    filter_rating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rating_token {

    /**
     * Sign one rating link.
     *
     * @param int $userid User the link belongs to.
     * @param int $contextid Context the rating is submitted from.
     * @param string $criterion Normalised criterion.
     * @param int $rating Number of stars the link submits.
     * @return string The token.
     */
    public static function generate(int $userid, int $contextid, string $criterion, int $rating): string {
        $data = implode('|', [$userid, $contextid, $criterion, $rating]);

        return hash_hmac('sha256', $data, self::get_secret());
    }

    /**
     * Check a token coming from a request.
     *
     * @param string $token Token to check.
     * @param int $userid User the link should belong to.
     * @param int $contextid Context the rating is submitted from.
     * @param string $criterion Normalised criterion.
     * @param int $rating Number of stars.
     * @return bool
     */
    public static function validate(string $token, int $userid, int $contextid, string $criterion, int $rating): bool {
        if ($token === '') {
            return false;
        }

        return hash_equals(self::generate($userid, $contextid, $criterion, $rating), $token);
    }

    /**
     * The signing secret, created on first use.
     *
     * @return string
     */
    protected static function get_secret(): string {
        $secret = get_config('filter_rating', 'tokensecret');

        if (empty($secret)) {
            $secret = random_string(40);
            set_config('tokensecret', $secret, 'filter_rating');
        }

        return $secret;
    }
}
