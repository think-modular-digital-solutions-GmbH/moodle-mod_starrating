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
 * Settings form for the star rating activity.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

use mod_starrating\local\rating_manager;

/**
 * The form shown when adding or editing a star rating.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_starrating_mod_form extends moodleform_mod {
    /**
     * Define the form.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        // The name of the activity is the criterion that people rate.
        $mform->addElement('text', 'name', get_string('activityname', 'mod_starrating'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'activityname', 'mod_starrating');

        $this->standard_intro_elements();

        $mform->addElement('header', 'starratingdisplay', get_string('displaysettings', 'mod_starrating'));

        $mform->addElement(
            'select',
            'maxstars',
            get_string('maxstars', 'mod_starrating'),
            rating_manager::get_max_stars_options()
        );
        $mform->setType('maxstars', PARAM_INT);
        $mform->addHelpButton('maxstars', 'maxstars', 'mod_starrating');
        $mform->setDefault('maxstars', rating_manager::get_default_max_stars());

        $mform->addElement('advcheckbox', 'showaverage', get_string('showaverage', 'mod_starrating'));
        $mform->addHelpButton('showaverage', 'showaverage', 'mod_starrating');
        $mform->setDefault('showaverage', get_config('mod_starrating', 'showaverage'));

        $mform->addElement('advcheckbox', 'allowclear', get_string('allowclear', 'mod_starrating'));
        $mform->addHelpButton('allowclear', 'allowclear', 'mod_starrating');
        $mform->setDefault('allowclear', get_config('mod_starrating', 'allowclear'));

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Check the submitted data.
     *
     * Shortening the scale of an activity people have already rated would leave
     * ratings above the new maximum, which no display could show honestly, so
     * the scale can only be shortened down to the highest rating given.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array Errors, keyed by element name.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $instanceid = (int) ($this->_instance ?? 0);
        $maxstars = rating_manager::normalise_max_stars($data['maxstars'] ?? 0);

        if ($instanceid) {
            $highest = rating_manager::get_highest_rating($instanceid);

            if ($highest > $maxstars) {
                $errors['maxstars'] = get_string('maxstarstoolow', 'mod_starrating', $highest);
            }
        }

        return $errors;
    }
}
