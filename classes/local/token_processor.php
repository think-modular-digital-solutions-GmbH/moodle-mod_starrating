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

use context;
use filter_rating\output\rating_widget;

/**
 * Turns {{ rating:criterion }} tokens into star rating widgets.
 *
 * Kept separate from the filter classes so that the same code serves the
 * Moodle 4.5 and later filter API and the legacy one, and so that it can be
 * unit tested on its own.
 *
 * @package    filter_rating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class token_processor {

    /**
     * Matches {{ rating:criterion }} with any amount of surrounding whitespace.
     *
     * The criterion may contain spaces but neither braces nor line breaks, and
     * is capped at the length of the database column.
     */
    public const TOKEN_REGEX = '/\{\{\s*rating\s*:\s*([^{}\r\n]{1,100}?)\s*\}\}/iu';

    /**
     * Cheap check whether the text can possibly contain a token.
     *
     * @param string $text Text to inspect.
     * @return bool
     */
    public static function contains_token(string $text): bool {
        return strpos($text, '{{') !== false && stripos($text, 'rating') !== false;
    }

    /**
     * Replace every rating token in the text with a rendered star widget.
     *
     * @param string $text Text being filtered.
     * @param context $context Context the text is displayed in.
     * @return string Filtered text.
     */
    public static function process(string $text, context $context): string {
        if (!self::contains_token($text)) {
            return $text;
        }

        $replaced = preg_replace_callback(self::TOKEN_REGEX, static function (array $matches) use ($context): string {
            return self::render_token($matches[1], $context) ?? $matches[0];
        }, $text);

        // A failing regex (for example on invalid UTF-8) must never eat the text.
        return $replaced ?? $text;
    }

    /**
     * Render the widget for a single token.
     *
     * @param string $rawcriterion Criterion as written in the token.
     * @param context $context Context the text is displayed in.
     * @return string|null Rendered widget, or null to leave the token untouched.
     */
    protected static function render_token(string $rawcriterion, context $context): ?string {
        global $PAGE;

        $criterion = rating_manager::normalise_criterion($rawcriterion);
        if ($criterion === '') {
            return null;
        }

        // Only a real page can be returned to. During a web service call the app
        // handles the click itself, so no return URL is needed.
        $returnurl = $PAGE->has_set_url() ? $PAGE->url : null;

        $widget = new rating_widget($context, rating_manager::get_courseid_from_context($context), $criterion, $returnurl);

        return $PAGE->get_renderer('filter_rating')->render($widget);
    }
}
