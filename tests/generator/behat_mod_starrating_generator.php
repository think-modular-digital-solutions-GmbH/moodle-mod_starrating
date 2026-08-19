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
 * Behat data generator for the star rating activity.
 *
 * @package    mod_starrating
 * @category   test
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Lets a feature seed ratings that were given before the scenario starts.
 *
 * @package    mod_starrating
 * @category   test
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_starrating_generator extends behat_generator_base {
    /**
     * The entities this generator can create.
     *
     * @return array Entity name to its generator configuration.
     */
    protected function get_creatable_entities(): array {
        return [
            'ratings' => [
                'singular' => 'rating',
                'datagenerator' => 'rating',
                'required' => ['activity', 'user', 'rating'],
                'switchids' => ['user' => 'userid'],
            ],
        ];
    }

    /**
     * Turn the name of the rated activity into the instance id of the activity.
     *
     * The criterion is the name of the activity, so that is what a feature file
     * names, rather than an id nobody can read.
     *
     * @param array $data Row from the step, with an activity name.
     * @return array Row with the activity replaced by starratingid.
     */
    protected function preprocess_rating(array $data): array {
        $cm = $this->get_cm_by_activity_name('starrating', $data['activity']);

        unset($data['activity']);
        $data['starratingid'] = $cm->instance;

        return $data;
    }
}
