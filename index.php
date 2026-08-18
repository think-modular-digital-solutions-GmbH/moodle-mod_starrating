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
 * All star rating activities of one course.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use mod_starrating\local\rating_manager;

$id = required_param('id', PARAM_INT);

$course = get_course($id);
require_login($course);

$context = context_course::instance($course->id);

$PAGE->set_url(new moodle_url('/mod/starrating/index.php', ['id' => $course->id]));
$PAGE->set_context($context);
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'mod_starrating'), 2);

$instances = get_all_instances_in_course('starrating', $course);

if (empty($instances)) {
    echo $OUTPUT->notification(get_string('noresults', 'mod_starrating'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$renderer = $PAGE->get_renderer('mod_starrating');
$modinfo = get_fast_modinfo($course);

$table = new html_table();
$table->head = [
    get_string('colcriterion', 'mod_starrating'),
    get_string('colratings', 'mod_starrating'),
    get_string('colaverage', 'mod_starrating'),
];
$table->attributes['class'] = 'generaltable';

foreach ($instances as $instance) {
    $cm = $modinfo->get_cm($instance->coursemodule);
    if (!$cm->uservisible) {
        continue;
    }

    $stats = rating_manager::get_stats((int) $instance->id);

    $table->data[] = [
        html_writer::link(
            new moodle_url('/mod/starrating/view.php', ['id' => $cm->id]),
            format_string($instance->name)
        ),
        $stats->count,
        $renderer->static_stars($stats->average, $stats->count),
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
