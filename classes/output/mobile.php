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

namespace mod_starrating\output;

use context_module;
use mod_starrating\local\rating_manager;
use moodle_url;

/**
 * Moodle app entry points for the star rating activity.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {

    /**
     * JavaScript the app runs once when it loads the site plugins.
     *
     * It is what makes a star on the course page tappable. Everything else in
     * this plugin is declarative; the app simply offers no other way to react to
     * a tap on course page content.
     *
     * @param array $args Arguments passed by the app, unused.
     * @return array Content for the app.
     */
    public static function mobile_init(array $args): array {
        global $CFG;

        $path = $CFG->dirroot . '/mod/starrating/mobile/init.js';

        // An unreadable file would hand the app an empty script and silently
        // leave the course page stars dead, so say so instead.
        if (!is_readable($path)) {
            debugging("mod_starrating: {$path} is missing, the stars on the course page will not work in the app.",
                DEBUG_DEVELOPER);

            return ['templates' => [], 'javascript' => '', 'otherdata' => ''];
        }

        return [
            'templates' => [],
            'javascript' => file_get_contents($path),
            'otherdata' => '',
        ];
    }

    /**
     * The activity page in the app, where the rating is actually given.
     *
     * This is a site plugin template, so the stars can carry the app's
     * core-site-plugins-call-ws directive and submit without any JavaScript of
     * ours and without leaving the app.
     *
     * @param array $args Arguments passed by the app, expects cmid.
     * @return array Content for the app.
     */
    public static function mobile_view_activity(array $args): array {
        global $OUTPUT, $DB;

        $cm = get_coursemodule_from_id('starrating', (int) ($args['cmid'] ?? 0), 0, false, MUST_EXIST);
        $course = get_course($cm->course);
        require_login($course, false, $cm);

        $context = context_module::instance($cm->id);
        $instance = $DB->get_record('starrating', ['id' => $cm->instance], '*', MUST_EXIST);
        $name = format_string($cm->name, true, ['context' => $context]);

        $userrating = rating_manager::get_user_rating((int) $cm->instance);
        $stats = rating_manager::get_stats((int) $cm->instance);
        $canrate = rating_manager::can_rate($context);

        $stars = [];
        for ($value = 1; $value <= rating_manager::MAX_STARS; $value++) {
            $stars[] = [
                'on' => $value <= $userrating,
                'params' => self::build_params((int) $cm->id, $value),
                'label' => get_string('ratestars', 'mod_starrating', (object) [
                    'stars' => $value,
                    'max' => rating_manager::MAX_STARS,
                    'criterion' => $name,
                ]),
            ];
        }

        $html = $OUTPUT->render_from_template('mod_starrating/mobile_view_activity', [
            'name' => $name,
            'intro' => format_module_intro('starrating', $instance, $cm->id),
            'hasintro' => (bool) trim(strip_tags($instance->intro ?? '')),
            'stars' => $stars,
            'canrate' => $canrate,
            'statustext' => rating_manager::get_status_text($name, $userrating),
            'showaverage' => !empty($instance->showaverage),
            'averagetext' => rating_manager::get_average_text($stats),
            'canclear' => $canrate && !empty($instance->allowclear) && $userrating > 0,
            'clearparams' => self::build_params((int) $cm->id, 0),
            'cleartext' => get_string('clearrating', 'mod_starrating'),
            'clearlabel' => get_string('clearratingaria', 'mod_starrating', $name),
            'successmessage' => get_string('ratingsaved', 'mod_starrating'),
        ]);

        return [
            'templates' => [['id' => 'main', 'html' => $html]],
            'javascript' => '',
            'otherdata' => '',
        ];
    }

    /**
     * The stars as they appear inline on the course page in the app.
     *
     * The app renders this through core-format-text, which cannot run the
     * call-ws directive, so these stars are plain markup and mobile/init.js
     * gives them their behaviour: one tap saves and repaints, in place.
     *
     * @param array $args Arguments passed by the app, expects cmid.
     * @return array Content for the app.
     */
    public static function mobile_course_page(array $args): array {
        global $OUTPUT;

        $cm = get_coursemodule_from_id('starrating', (int) ($args['cmid'] ?? 0), 0, false, MUST_EXIST);
        $course = get_course($cm->course);
        require_login($course, false, $cm);

        $context = context_module::instance($cm->id);
        $name = format_string($cm->name, true, ['context' => $context]);

        $userrating = rating_manager::get_user_rating((int) $cm->instance);
        $stats = rating_manager::get_stats((int) $cm->instance);
        $customdata = (array) get_fast_modinfo($course)->get_cm($cm->id)->customdata;
        $canrate = rating_manager::can_rate($context);

        $stars = [];
        for ($value = 1; $value <= rating_manager::MAX_STARS; $value++) {
            $stars[] = [
                'value' => $value,
                'on' => $value <= $userrating,
                'label' => get_string('ratestars', 'mod_starrating', (object) [
                    'stars' => $value,
                    'max' => rating_manager::MAX_STARS,
                    'criterion' => $name,
                ]),
            ];
        }

        $html = $OUTPUT->render_from_template('mod_starrating/mobile_course_page', [
            'name' => $name,
            'cmid' => $cm->id,
            'courseid' => $course->id,
            'stars' => $stars,
            'canrate' => $canrate,
            'statustext' => rating_manager::get_status_text($name, $userrating),
            'showaverage' => !empty($customdata['showaverage']),
            'averagetext' => rating_manager::get_average_text($stats),
        ]);

        return [
            'templates' => [['id' => 'main', 'html' => $html]],
            'javascript' => '',
            'otherdata' => '',
        ];
    }

    /**
     * The parameter object the app passes to mod_starrating_set_rating.
     *
     * The app reads this as an Angular expression, and the template escapes the
     * result again for the HTML attribute.
     *
     * @param int $cmid Course module id.
     * @param int $rating Number of stars, 0 to withdraw.
     * @return string
     */
    protected static function build_params(int $cmid, int $rating): string {
        return sprintf('{cmid: %d, rating: %d}', $cmid, $rating);
    }
}
