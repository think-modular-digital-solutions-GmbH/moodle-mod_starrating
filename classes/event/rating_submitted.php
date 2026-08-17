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

namespace filter_rating\event;

/**
 * Fired whenever a user submits, changes or withdraws a star rating.
 *
 * @package    filter_rating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rating_submitted extends \core\event\base {

    /**
     * Set the basic event properties.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Human readable event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventratingsubmitted', 'filter_rating');
    }

    /**
     * Description of what happened, for the log report.
     *
     * @return string
     */
    public function get_description() {
        $rating = $this->other['rating'] ?? 0;
        $criterion = $this->other['criterion'] ?? '';

        if ($rating > 0) {
            return "The user with id '{$this->userid}' rated the criterion '{$criterion}' with {$rating} stars.";
        }

        return "The user with id '{$this->userid}' withdrew their rating of the criterion '{$criterion}'.";
    }

    /**
     * Make sure the event carries everything the description needs.
     *
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['criterion'])) {
            throw new \coding_exception('The criterion must be set in other.');
        }
        if (!isset($this->other['rating'])) {
            throw new \coding_exception('The rating must be set in other.');
        }
    }
}
