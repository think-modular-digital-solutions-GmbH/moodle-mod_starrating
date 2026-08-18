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
 * Every individual star rating, with the person who gave it.
 *
 * Separate from report.php because this list names users, which is what makes
 * mod/starrating:viewreports carry RISK_PERSONAL.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

use mod_starrating\local\report;
use mod_starrating\output\ratings_table;

$courseid = optional_param('courseid', 0, PARAM_INT);
$activity = optional_param('activity', '', PARAM_TEXT);
$download = optional_param('download', '', PARAM_ALPHA);
$perpage = optional_param('perpage', 50, PARAM_INT);

$perpage = min(max($perpage, 5), 500);
$filterparams = ['courseid' => $courseid, 'activity' => $activity];

admin_externalpage_setup('starratingdetails', '', $filterparams);

$baseurl = new moodle_url('/mod/starrating/details.php', $filterparams);

$table = new ratings_table('mod_starrating_details', $baseurl, $courseid, $activity);
$table->is_downloading($download, 'ratings', get_string('details', 'mod_starrating'));

if (!$table->is_downloading()) {
    /** @var \mod_starrating\output\renderer $renderer */
    $renderer = $PAGE->get_renderer('mod_starrating');

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('reportdetails', 'mod_starrating'), 2);

    echo $renderer->report_filter(
        $baseurl,
        report::get_course_options(),
        report::get_activity_options(),
        $courseid,
        $activity
    );
}

$table->out($perpage, true);

if (!$table->is_downloading()) {
    echo html_writer::div(
        html_writer::link(
            new moodle_url('/mod/starrating/report.php', $filterparams),
            get_string('viewaverages', 'mod_starrating')
        ),
        'mt-3'
    );

    echo $OUTPUT->footer();
}
