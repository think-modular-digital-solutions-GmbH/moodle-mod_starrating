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

use filter_rating\local\token_processor;

/**
 * Star rating filter for Moodle 4.5 and later.
 *
 * On older releases the identical filter in filter.php is used instead.
 *
 * @package    filter_rating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class text_filter extends \core_filters\text_filter {

    /**
     * Replace every rating token with a star rating widget.
     *
     * @param string $text Text being filtered.
     * @param array $options Filter options, unused.
     * @return string Filtered text.
     */
    public function filter($text, array $options = []) {
        return token_processor::process((string) $text, $this->context);
    }
}
