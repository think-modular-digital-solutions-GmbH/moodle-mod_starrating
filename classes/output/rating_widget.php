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

namespace filter_rating\output;

use context;
use filter_rating\local\rating_manager;
use filter_rating\local\rating_token;
use moodle_url;
use renderable;
use renderer_base;
use templatable;

/**
 * The five stars rendered in place of a rating token.
 *
 * Each star is a plain link to rate.php, which works without JavaScript in the
 * browser. For the Moodle app every star carries a data-app-url as well, which
 * the app opens with auto login and, once closed, refreshes the content it came
 * from so that the new rating shows.
 *
 * @package    filter_rating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rating_widget implements renderable, templatable {

    /**
     * Constructor.
     *
     * @param context $context Context the widget is displayed in.
     * @param int $courseid Course the criterion belongs to.
     * @param string $criterion Normalised criterion.
     * @param moodle_url|null $returnurl Page to return to after rating, null during a web service call.
     */
    public function __construct(
        protected context $context,
        protected int $courseid,
        protected string $criterion,
        protected ?moodle_url $returnurl = null
    ) {
    }

    /**
     * Build the template context.
     *
     * @param renderer_base $output Renderer.
     * @return array Template context.
     */
    public function export_for_template(renderer_base $output): array {
        $userrating = rating_manager::get_user_rating($this->courseid, $this->criterion);
        $stats = rating_manager::get_stats($this->courseid, $this->criterion);
        $canrate = rating_manager::can_rate($this->context);
        $showaverage = (bool) get_config('filter_rating', 'showaverage');
        $allowclear = (bool) get_config('filter_rating', 'allowclear');

        $stars = [];
        for ($value = 1; $value <= rating_manager::MAX_STARS; $value++) {
            $stars[] = [
                'value' => $value,
                'on' => $value <= $userrating,
                'url' => $canrate ? $this->build_rate_url($value)->out(false) : null,
                'appurl' => $canrate ? $this->build_rate_url($value, true)->out_as_local_url(false) : null,
                'label' => get_string('ratestars', 'filter_rating', (object) [
                    'stars' => $value,
                    'max' => rating_manager::MAX_STARS,
                    'criterion' => $this->criterion,
                ]),
            ];
        }

        return [
            'criterion' => $this->criterion,
            'contextid' => $this->context->id,
            'courseid' => $this->courseid,
            'userrating' => $userrating,
            'count' => $stats->count,
            'average' => $stats->average,
            'canrate' => $canrate,
            'stars' => $stars,
            'grouplabel' => get_string('ratinggroup', 'filter_rating', $this->criterion),
            'statustext' => $this->get_status_text($userrating),
            'showaverage' => $showaverage,
            'averagetext' => $this->get_average_text($stats),
            'canclear' => $canrate && $allowclear && $userrating > 0,
            'clearurl' => $canrate ? $this->build_rate_url(0)->out(false) : null,
            'clearappurl' => $canrate ? $this->build_rate_url(0, true)->out_as_local_url(false) : null,
            'clearlabel' => get_string('clearrating', 'filter_rating'),
            'clearaltlabel' => get_string('clearratingaria', 'filter_rating', $this->criterion),
        ];
    }

    /**
     * Link to the endpoint that stores a rating.
     *
     * The app opens its own, auto logged in browser session, in which the return
     * URL of the web page would be meaningless. It gets a variant of the link
     * that reports back and lets the user close the window instead.
     *
     * @param int $rating Number of stars, 0 to withdraw the rating.
     * @param bool $forapp Whether to build the variant used by the Moodle app.
     * @return moodle_url
     */
    protected function build_rate_url(int $rating, bool $forapp = false): moodle_url {
        global $USER;

        // The course is derived from the context by the endpoint, it is not a parameter.
        $params = [
            'contextid' => $this->context->id,
            'criterion' => $this->criterion,
            'rating' => $rating,
            'token' => rating_token::generate((int) $USER->id, (int) $this->context->id, $this->criterion, $rating),
        ];

        if ($forapp) {
            $params['appreturn'] = 1;
        } else if ($this->returnurl) {
            $params['returnurl'] = $this->returnurl->out_as_local_url(false);
        }

        return new moodle_url('/filter/rating/rate.php', $params);
    }

    /**
     * The rating of the current user in words, so that it is not conveyed by colour alone.
     *
     * @param int $userrating Number of stars the current user gave.
     * @return string
     */
    protected function get_status_text(int $userrating): string {
        if ($userrating > 0) {
            return get_string('yourrating', 'filter_rating', (object) [
                'stars' => $userrating,
                'max' => rating_manager::MAX_STARS,
                'criterion' => $this->criterion,
            ]);
        }

        return get_string('notratedyet', 'filter_rating', $this->criterion);
    }

    /**
     * The average of all ratings in words.
     *
     * @param \stdClass $stats Object with the count and average properties.
     * @return string
     */
    protected function get_average_text(\stdClass $stats): string {
        if ($stats->count === 0) {
            return get_string('noratingsyet', 'filter_rating');
        }

        return get_string('averagetext', 'filter_rating', (object) [
            'average' => format_float($stats->average, 1),
            'max' => rating_manager::MAX_STARS,
            'count' => $stats->count,
        ]);
    }
}
