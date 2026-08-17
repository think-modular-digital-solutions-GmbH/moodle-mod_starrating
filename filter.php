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

/**
 * Star rating filter for Moodle 4.4 and earlier.
 *
 * Moodle 4.5 introduced classes/text_filter.php and loads this file only when
 * that class does not exist, so both can live side by side.
 *
 * @package    filter_rating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Legacy star rating filter.
 *
 * @package    filter_rating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_rating extends moodle_text_filter {

    /**
     * Replace every rating token with a star rating widget.
     *
     * @param string $text Text being filtered.
     * @param array $options Filter options, unused.
     * @return string Filtered text.
     */
    public function filter($text, array $options = []) {
        return \filter_rating\local\token_processor::process((string) $text, $this->context);
    }
}
