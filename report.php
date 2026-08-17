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
 * Site wide report over all star ratings.
 *
 * Shows averages per course and per criterion, plus the individual ratings.
 * The individual ratings name the users who rated, which is why the report
 * needs filter/rating:viewreports.
 *
 * @package    filter_rating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

use filter_rating\local\rating_manager;
use filter_rating\local\report;
use filter_rating\output\ratings_table;

$courseid = optional_param('courseid', 0, PARAM_INT);
$criterion = optional_param('criterion', '', PARAM_TEXT);
$groupby = optional_param('groupby', report::GROUP_COURSE_CRITERION, PARAM_ALPHA);
$download = optional_param('download', '', PARAM_ALPHA);
$perpage = optional_param('perpage', 50, PARAM_INT);

$groupby = report::validate_groupby($groupby);
$criterion = rating_manager::normalise_criterion($criterion);
$perpage = min(max($perpage, 5), 500);

$filterparams = ['courseid' => $courseid, 'criterion' => $criterion, 'groupby' => $groupby];

admin_externalpage_setup('filterratingreport', '', $filterparams);

$baseurl = new moodle_url('/filter/rating/report.php', $filterparams);

$table = new ratings_table('filter_rating_report', $baseurl, $courseid, $criterion);
$table->is_downloading($download, 'ratings', get_string('details', 'filter_rating'));

if (!$table->is_downloading()) {
    /** @var \filter_rating\output\renderer $renderer */
    $renderer = $PAGE->get_renderer('filter_rating');

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('report', 'filter_rating'), 2);

    echo $renderer->report_filter(
        $baseurl,
        report::get_course_options(),
        report::get_criterion_options(),
        $courseid,
        $criterion,
        $groupby
    );

    echo $OUTPUT->heading(get_string('averages', 'filter_rating'), 3);
    echo $renderer->summary_table(
        report::get_summary($courseid, $criterion, $groupby),
        $groupby,
        report::get_overall($courseid, $criterion)
    );

    echo $OUTPUT->heading(get_string('details', 'filter_rating'), 3);
}

$table->out($perpage, true);

if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}
