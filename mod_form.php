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

        $mform->addElement('advcheckbox', 'showaverage', get_string('showaverage', 'mod_starrating'));
        $mform->addHelpButton('showaverage', 'showaverage', 'mod_starrating');
        $mform->setDefault('showaverage', get_config('mod_starrating', 'showaverage'));

        $mform->addElement('advcheckbox', 'allowclear', get_string('allowclear', 'mod_starrating'));
        $mform->addHelpButton('allowclear', 'allowclear', 'mod_starrating');
        $mform->setDefault('allowclear', get_config('mod_starrating', 'allowclear'));

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}
