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
 * The star ratings of one course, for its teachers.
 *
 * The same figures as the site wide report in Site administration, restricted to
 * a single course and reachable without administrator rights. Both halves are on
 * one page, because a course holds few enough activities for that to stay
 * readable: the average per activity first, then who gave which rating.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/tablelib.php');

use mod_starrating\local\report;
use mod_starrating\output\ratings_table;

$id = required_param('id', PARAM_INT);
$activity = optional_param('activity', '', PARAM_TEXT);
$download = optional_param('download', '', PARAM_ALPHA);
$perpage = optional_param('perpage', 50, PARAM_INT);

$perpage = min(max($perpage, 5), 500);

$course = get_course($id);
require_login($course);

$context = context_course::instance($course->id);
require_capability('mod/starrating:viewcoursereport', $context);

$baseurl = new moodle_url('/mod/starrating/coursereport.php', ['id' => $course->id, 'activity' => $activity]);

$PAGE->set_url($baseurl);
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('coursereport', 'mod_starrating'));
$PAGE->set_heading(format_string($course->fullname));

// The course column would repeat the course this page is already about.
$table = new ratings_table('mod_starrating_coursereport', $baseurl, $course->id, $activity, false);
$table->is_downloading(
    $download,
    clean_filename('ratings-' . $course->shortname),
    get_string('coursereport', 'mod_starrating')
);

if ($table->is_downloading()) {
    $table->out($perpage, true);
    exit;
}

/** @var \mod_starrating\output\renderer $renderer */
$renderer = $PAGE->get_renderer('mod_starrating');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('coursereport', 'mod_starrating'), 2);

echo $renderer->course_report_filter(
    $baseurl,
    report::get_activity_options($course->id),
    $course->id,
    $activity
);

echo $OUTPUT->heading(get_string('averages', 'mod_starrating'), 3);
echo $renderer->course_summary_table(report::get_course_summary($course->id, $activity));

echo $OUTPUT->heading(get_string('details', 'mod_starrating'), 3);
$table->out($perpage, true);

echo $OUTPUT->footer();
