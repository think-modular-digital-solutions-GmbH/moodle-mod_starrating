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
 * Settings and report registration for the star rating activity.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use mod_starrating\local\rating_manager;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configselect(
        'mod_starrating/maxstars',
        get_string('maxstars', 'mod_starrating'),
        get_string('maxstars_desc', 'mod_starrating'),
        rating_manager::DEFAULT_MAX_STARS,
        rating_manager::get_max_stars_options()
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_starrating/showaverage',
        get_string('showaverage', 'mod_starrating'),
        get_string('showaverage_desc', 'mod_starrating'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_starrating/allowclear',
        get_string('allowclear', 'mod_starrating'),
        get_string('allowclear_desc', 'mod_starrating'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_starrating/allowfeedback',
        get_string('allowfeedback', 'mod_starrating'),
        get_string('allowfeedback_desc', 'mod_starrating'),
        0
    ));
}

$ADMIN->add('reports', new admin_externalpage(
    'starratingreport',
    get_string('reportaverages', 'mod_starrating'),
    new moodle_url('/mod/starrating/report.php'),
    'mod/starrating:viewreports'
));

$ADMIN->add('reports', new admin_externalpage(
    'starratingdetails',
    get_string('reportdetails', 'mod_starrating'),
    new moodle_url('/mod/starrating/details.php'),
    'mod/starrating:viewreports'
));
