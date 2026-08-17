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
 * This is the browser path. The Moodle app never reaches it: there the tap on a
 * star is caught by mobile/init.js and saved through the filter_rating_set_rating
 * web service, without leaving the app.
 *
 * @package    filter_rating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use core\output\notification;
use filter_rating\local\rating_manager;
use filter_rating\local\rating_token;

$contextid = required_param('contextid', PARAM_INT);
$criterion = required_param('criterion', PARAM_TEXT);
$rating = required_param('rating', PARAM_INT);
$token = optional_param('token', '', PARAM_ALPHANUM);
$returnparam = optional_param('returnurl', '', PARAM_LOCALURL);

[$context, $course, $cm] = get_context_info_array($contextid);

if ($cm) {
    require_login($course, false, $cm);
} else if ($course && (int) $course->id !== (int) SITEID) {
    require_login($course, false);
} else {
    require_login();
}

$criterion = rating_manager::normalise_criterion($criterion);
if ($criterion === '') {
    throw new moodle_exception('invalidcriterion', 'filter_rating');
}
if ($rating < 0 || $rating > rating_manager::MAX_STARS) {
    throw new moodle_exception('invalidrating', 'filter_rating');
}

$urlparams = ['contextid' => $contextid, 'criterion' => $criterion, 'rating' => $rating];
if ($returnparam !== '') {
    $urlparams['returnurl'] = $returnparam;
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/filter/rating/rate.php', $urlparams));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'filter_rating'));
$PAGE->set_heading($course ? format_string($course->fullname) : format_string($SITE->fullname));

rating_manager::require_can_rate($context);

// Work out where to send the user afterwards. Anything that is not a local URL
// has already been dropped by PARAM_LOCALURL.
$returntarget = $returnparam !== '' ? $returnparam : get_local_referer(false);
if (empty($returntarget)) {
    $returntarget = ($course && (int) $course->id !== (int) SITEID)
        ? new moodle_url('/course/view.php', ['id' => $course->id])
        : new moodle_url('/');
}
$returnurl = new moodle_url($returntarget);

// A rating is written on a link click, so the link itself carries the proof that
// the user meant it. Should the token be missing or stale, for example because
// the rendered stars were cached, ask for confirmation rather than fail: losing
// the click is worse, and the confirmation is protected by the session key.
$signed = rating_token::validate($token, (int) $USER->id, (int) $context->id, $criterion, $rating);

if (!$signed && !confirm_sesskey()) {
    $confirmurl = new moodle_url('/filter/rating/rate.php', $urlparams + ['sesskey' => sesskey()]);

    if ($rating > 0) {
        $message = get_string('confirmrating', 'filter_rating', (object) [
            'stars' => $rating,
            'max' => rating_manager::MAX_STARS,
            'criterion' => $criterion,
        ]);
    } else {
        $message = get_string('confirmclearrating', 'filter_rating', $criterion);
    }

    echo $OUTPUT->header();
    echo $OUTPUT->confirm($message, $confirmurl, $returnurl);
    echo $OUTPUT->footer();
    exit;
}

rating_manager::set_rating($context, rating_manager::get_courseid_from_context($context), $criterion, $rating);

$message = $rating > 0
    ? get_string('ratingsaved', 'filter_rating')
    : get_string('ratingcleared', 'filter_rating');

redirect($returnurl, $message, null, notification::NOTIFY_SUCCESS);
