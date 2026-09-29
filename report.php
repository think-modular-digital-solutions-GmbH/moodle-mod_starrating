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
 * Averages over all star rating activities.
 *
 * It opens on one row per course, and every course links to the course report,
 * which breaks that course down by activity and by person. The individual
 * ratings of the whole site live on details.php, because they name the people
 * who rated and are a much longer list.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use mod_starrating\local\report;

$courseid = optional_param('courseid', 0, PARAM_INT);
$activity = optional_param('activity', '', PARAM_TEXT);
$groupby = optional_param('groupby', report::GROUP_COURSE, PARAM_ALPHA);

$groupby = report::validate_groupby($groupby);
$filterparams = ['courseid' => $courseid, 'activity' => $activity, 'groupby' => $groupby];

admin_externalpage_setup('starratingreport', '', $filterparams);

$baseurl = new moodle_url('/mod/starrating/report.php', $filterparams);

/** @var \mod_starrating\output\renderer $renderer */
$renderer = $PAGE->get_renderer('mod_starrating');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('reportaverages', 'mod_starrating'), 2);

echo $renderer->report_filter(
    $baseurl,
    report::get_course_options(),
    report::get_activity_options(),
    $courseid,
    $activity,
    $groupby
);

echo $renderer->summary_table(
    report::get_summary($courseid, $activity, $groupby),
    $groupby,
    report::get_overall($courseid, $activity)
);

// Carry the filter over, so the same selection can be looked at rating by rating.
echo html_writer::div(
    html_writer::link(
        new moodle_url('/mod/starrating/details.php', ['courseid' => $courseid, 'activity' => $activity]),
        get_string('viewdetails', 'mod_starrating')
    ),
    'mt-3'
);

echo $OUTPUT->footer();
