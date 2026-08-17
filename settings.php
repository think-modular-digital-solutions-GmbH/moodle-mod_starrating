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
 * Settings and report registration for the star rating filter.
 *
 * @package    filter_rating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configcheckbox(
        'filter_rating/showaverage',
        get_string('showaverage', 'filter_rating'),
        get_string('showaverage_desc', 'filter_rating'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'filter_rating/allowclear',
        get_string('allowclear', 'filter_rating'),
        get_string('allowclear_desc', 'filter_rating'),
        1
    ));
}

$ADMIN->add('reports', new admin_externalpage(
    'filterratingreport',
    get_string('report', 'filter_rating'),
    new moodle_url('/filter/rating/report.php'),
    'filter/rating:viewreports'
));
