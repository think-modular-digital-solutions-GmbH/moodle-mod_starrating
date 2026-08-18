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
 * The page of one star rating activity.
 *
 * The stars also appear on the course page itself, so this page mainly serves
 * direct links and the Moodle app, which opens it natively.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use mod_starrating\output\rating_widget;

$id = required_param('id', PARAM_INT);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'starrating');
require_login($course, true, $cm);

$context = context_module::instance($cm->id);
$instance = $DB->get_record('starrating', ['id' => $cm->instance], '*', MUST_EXIST);

$PAGE->set_url(new moodle_url('/mod/starrating/view.php', ['id' => $cm->id]));
$PAGE->set_context($context);
$PAGE->set_title(format_string($instance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_activity_record($instance);

$event = \mod_starrating\event\course_module_viewed::create([
    'objectid' => $instance->id,
    'context' => $context,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('starrating', $instance);
$event->trigger();

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($instance->name), 2);

if (trim(strip_tags($instance->intro))) {
    echo $OUTPUT->box(format_module_intro('starrating', $instance, $cm->id), 'generalbox', 'intro');
}

// The stars link back here, so a rating on this page returns to this page.
$widget = new rating_widget($cm, $PAGE->url, false);
echo $PAGE->get_renderer('mod_starrating')->render($widget);

echo $OUTPUT->footer();
