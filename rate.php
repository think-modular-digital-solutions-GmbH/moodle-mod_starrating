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
 * Stores a star rating and returns to the page the rating was given on.
 *
 * Takes a click on a star link, or the feedback form of an activity that asks
 * for text feedback, which sends the stars and the text together.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use core\output\notification;
use mod_starrating\local\rating_manager;
use mod_starrating\local\rating_token;

$id = required_param('id', PARAM_INT);
$rating = required_param('rating', PARAM_INT);
$token = optional_param('token', '', PARAM_ALPHANUM);
$returnparam = optional_param('returnurl', '', PARAM_LOCALURL);
// Null when the request carries no feedback field, which keeps the stored text.
$feedback = optional_param('feedback', null, PARAM_TEXT);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'starrating');
require_login($course, false, $cm);

$context = context_module::instance($cm->id);

$maxstars = rating_manager::get_max_stars((int) $cm->instance);

if ($rating < 0 || $rating > $maxstars) {
    throw new moodle_exception('invalidrating', 'mod_starrating', '', $maxstars);
}

$urlparams = ['id' => $cm->id, 'rating' => $rating];
if ($returnparam !== '') {
    $urlparams['returnurl'] = $returnparam;
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/starrating/rate.php', $urlparams));
$PAGE->set_title(format_string($cm->name));
$PAGE->set_heading(format_string($course->fullname));

rating_manager::require_can_rate($context);

// Work out where to send the user afterwards. Anything that is not a local URL
// has already been dropped by PARAM_LOCALURL.
$returntarget = $returnparam !== '' ? $returnparam : get_local_referer(false);
if (empty($returntarget)) {
    $returntarget = new moodle_url('/course/view.php', ['id' => $course->id]);
}
$returnurl = new moodle_url($returntarget);

// The link itself carries the proof that the user meant it. Should the token be
// missing or stale, for example because the course page was cached, ask for
// confirmation rather than fail, and protect that with the session key.
$signed = rating_token::validate($token, (int) $USER->id, (int) $cm->id, $rating)
    || ($feedback !== null && rating_token::validate_for_form($token, (int) $USER->id, (int) $cm->id));

if (!$signed && !confirm_sesskey()) {
    // The feedback travels with the confirmation, which posts it, but stays out
    // of the page URL, where it could end up in logs.
    $confirmparams = $urlparams + ['sesskey' => sesskey()];
    if ($feedback !== null) {
        $confirmparams['feedback'] = $feedback;
    }
    $confirmurl = new moodle_url('/mod/starrating/rate.php', $confirmparams);

    if ($rating > 0) {
        $message = get_string('confirmrating', 'mod_starrating', (object) [
            'stars' => $rating,
            'max' => $maxstars,
            'criterion' => format_string($cm->name),
        ]);
    } else {
        $message = get_string('confirmclearrating', 'mod_starrating', format_string($cm->name));
    }

    echo $OUTPUT->header();
    echo $OUTPUT->confirm($message, $confirmurl, $returnurl);
    echo $OUTPUT->footer();
    exit;
}

rating_manager::set_rating($context, (int) $cm->instance, $rating, null, $feedback);

$message = $rating > 0
    ? get_string('ratingsaved', 'mod_starrating')
    : get_string('ratingcleared', 'mod_starrating');

redirect($returnurl, $message, null, notification::NOTIFY_SUCCESS);
