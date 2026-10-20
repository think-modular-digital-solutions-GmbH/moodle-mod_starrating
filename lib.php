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
 * Callbacks for the star rating activity.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_starrating\local\rating_manager;

/**
 * Report which features the activity supports.
 *
 * @param string $feature One of the FEATURE_* constants.
 * @return mixed
 */
function starrating_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_BACKUP_MOODLE2:
            return true;

        // The stars are rendered on the course page, but the activity keeps its
        // own page: it is the only place the Moodle app can rate from, and
        // without a view link neither Moodle nor the app will open it at all.
        case FEATURE_NO_VIEW_LINK:
            return false;

        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_GRADE_OUTCOMES:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_GROUPS:
        case FEATURE_GROUPINGS:
            return false;

        default:
            if (defined('FEATURE_MOD_PURPOSE') && $feature === FEATURE_MOD_PURPOSE) {
                return MOD_PURPOSE_COLLABORATION;
            }

            return null;
    }
}

/**
 * Create a new activity instance.
 *
 * @param stdClass $data Data from the module form.
 * @param mod_starrating_mod_form|null $mform The form, unused.
 * @return int The new instance id.
 */
function starrating_add_instance($data, $mform = null) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    $data->maxstars = rating_manager::normalise_max_stars($data->maxstars ?? 0);

    return $DB->insert_record('starrating', $data);
}

/**
 * Update an activity instance.
 *
 * @param stdClass $data Data from the module form.
 * @param mod_starrating_mod_form|null $mform The form, unused.
 * @return bool
 */
function starrating_update_instance($data, $mform = null) {
    global $DB;

    $data->id = $data->instance;
    $data->timemodified = time();
    $data->maxstars = rating_manager::normalise_max_stars($data->maxstars ?? 0);

    return $DB->update_record('starrating', $data);
}

/**
 * Delete an activity instance and every rating given to it.
 *
 * @param int $id Instance id.
 * @return bool
 */
function starrating_delete_instance($id) {
    global $DB;

    if (!$DB->record_exists('starrating', ['id' => $id])) {
        return false;
    }

    $DB->delete_records('starrating_rating', ['starratingid' => $id]);
    $DB->delete_records('starrating', ['id' => $id]);

    return true;
}

/**
 * Information about the activity that can be cached for the whole course.
 *
 * Nothing user specific may be put here, the course cache is shared. The stars
 * are added per user in starrating_cm_info_view() instead.
 *
 * @param stdClass $coursemodule The course module record.
 * @return cached_cm_info|bool
 */
function starrating_get_coursemodule_info($coursemodule) {
    global $DB;

    $fields = 'id, name, intro, introformat, maxstars, showaverage, allowclear, allowfeedback';
    $instance = $DB->get_record('starrating', ['id' => $coursemodule->instance], $fields);
    if (!$instance) {
        return false;
    }

    $info = new cached_cm_info();
    $info->name = $instance->name;

    if ($coursemodule->showdescription) {
        $info->content = format_module_intro('starrating', $instance, $coursemodule->id, false);
    }

    $info->customdata = [
        'maxstars' => rating_manager::normalise_max_stars($instance->maxstars),
        'showaverage' => (int) $instance->showaverage,
        'allowclear' => (int) $instance->allowclear,
        'allowfeedback' => (int) $instance->allowfeedback,
    ];

    return $info;
}

/**
 * Add the star rating to the course page, the way a label adds its text.
 *
 * This runs per request rather than from the course cache, because the stars
 * show the rating of the user looking at them.
 *
 * @param cm_info $cm The course module.
 * @return void
 */
function starrating_cm_info_view(cm_info $cm) {
    global $PAGE;

    if (!$cm->uservisible) {
        return;
    }

    // Moodle already prints the activity name above this, so the widget does not.
    $widget = new \mod_starrating\output\rating_widget($cm, $PAGE->has_set_url() ? $PAGE->url : null, false);
    $stars = $PAGE->get_renderer('mod_starrating')->render($widget);

    $cm->set_content($cm->content . $stars, true);
}

/**
 * Remove every rating of a user when their data is reset.
 *
 * @param stdClass $data Reset form data.
 * @return array
 */
function starrating_reset_userdata($data) {
    global $DB;

    $status = [];

    if (!empty($data->reset_starrating_ratings)) {
        $sql = 'SELECT id FROM {starrating} WHERE course = :courseid';
        $instances = $DB->get_fieldset_sql($sql, ['courseid' => $data->courseid]);

        if ($instances) {
            [$insql, $params] = $DB->get_in_or_equal($instances, SQL_PARAMS_NAMED, 'inst');
            $DB->delete_records_select('starrating_rating', 'starratingid ' . $insql, $params);
        }

        $status[] = [
            'component' => get_string('modulenameplural', 'mod_starrating'),
            'item' => get_string('resetratings', 'mod_starrating'),
            'error' => false,
        ];
    }

    return $status;
}

/**
 * Add the reset option to the course reset form.
 *
 * @param MoodleQuickForm $mform The form.
 * @return void
 */
function starrating_reset_course_form_definition($mform) {
    $mform->addElement('header', 'starratingheader', get_string('modulenameplural', 'mod_starrating'));
    $mform->addElement('advcheckbox', 'reset_starrating_ratings', get_string('resetratings', 'mod_starrating'));
}

/**
 * Whether the activity has been rated by a user, used by course reports.
 *
 * @param stdClass $course The course.
 * @param stdClass $user The user.
 * @param stdClass $mod The course module.
 * @param stdClass $instance The activity instance.
 * @return stdClass|null
 */
function starrating_user_outline($course, $user, $mod, $instance) {
    $rating = rating_manager::get_user_rating($instance->id, $user->id);
    if (!$rating) {
        return null;
    }

    $result = new stdClass();
    $result->info = get_string('starsofmax', 'mod_starrating', (object) [
        'stars' => $rating,
        'max' => rating_manager::normalise_max_stars($instance->maxstars ?? 0),
    ]);

    return $result;
}

/**
 * Add the course rating report to the course navigation.
 *
 * The site wide report is for administrators. Teachers get the same figures for
 * their own course, which is what this link leads to.
 *
 * @param navigation_node $navigation The course node.
 * @param stdClass $course The course.
 * @param context_course $context The course context.
 * @return void
 */
function starrating_extend_navigation_course($navigation, $course, $context) {
    if (!has_capability('mod/starrating:viewcoursereport', $context)) {
        return;
    }

    // Nothing to report on in a course without a single star rating.
    if (!get_fast_modinfo($course)->get_instances_of('starrating')) {
        return;
    }

    $navigation->add(
        get_string('coursereport', 'mod_starrating'),
        new moodle_url('/mod/starrating/coursereport.php', ['id' => $course->id]),
        navigation_node::TYPE_SETTING,
        null,
        'starratingcoursereport',
        new pix_icon('i/report', '')
    );
}

/**
 * Add the course rating report to the menu of one activity.
 *
 * @param settings_navigation $settings The settings navigation.
 * @param navigation_node $node The activity node.
 * @return void
 */
function starrating_extend_settings_navigation($settings, $node) {
    global $PAGE;

    $cm = $PAGE->cm;
    if (!$cm || !has_capability('mod/starrating:viewcoursereport', context_course::instance($cm->course))) {
        return;
    }

    // Prefiltered to this activity, since that is what the teacher is looking at.
    $node->add_node(navigation_node::create(
        get_string('coursereport', 'mod_starrating'),
        new moodle_url('/mod/starrating/coursereport.php', ['id' => $cm->course, 'activity' => $cm->name]),
        navigation_node::TYPE_SETTING,
        null,
        'starratingcoursereport',
        new pix_icon('i/report', '')
    ));
}
