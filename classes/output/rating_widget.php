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
 * Each star is a plain link to rate.php, which stores the rating and returns to
 * the page it was given on.
 *
 * In the Moodle app the stars are rendered as a display only. A link in app
 * content can either be claimed by one of the core content link handlers, which
 * a filter plugin cannot register, or be handed to a browser, and the browser
 * route asks for a login again as soon as the six minute auto login lockout in
 * tool_mobile bites. Showing the rating without offering a broken way to change
 * it is the honest option.
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
        // The app gets the read only variant, see the class comment.
        $canrate = rating_manager::can_rate($this->context) && !\core_useragent::is_moodle_app();
        $showaverage = (bool) get_config('filter_rating', 'showaverage');
        $allowclear = (bool) get_config('filter_rating', 'allowclear');

        $stars = [];
        for ($value = 1; $value <= rating_manager::MAX_STARS; $value++) {
            $stars[] = [
                'value' => $value,
                'on' => $value <= $userrating,
                'url' => $canrate ? $this->build_rate_url($value)->out(false) : null,
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
            'statustext' => rating_manager::get_status_text($this->criterion, $userrating),
            'showaverage' => $showaverage,
            'averagetext' => rating_manager::get_average_text($stats),
            // The link is always rendered when withdrawing is allowed, only hidden
            // while there is nothing to withdraw, so that the app can reveal it.
            'showclear' => $canrate && $allowclear,
            'canclear' => $canrate && $allowclear && $userrating > 0,
            'clearurl' => $canrate ? $this->build_rate_url(0)->out(false) : null,
            'clearlabel' => get_string('clearrating', 'filter_rating'),
            'clearaltlabel' => get_string('clearratingaria', 'filter_rating', $this->criterion),
        ];
    }

    /**
     * Link to the endpoint that stores a rating.
     *
     * @param int $rating Number of stars, 0 to withdraw the rating.
     * @return moodle_url
     */
    protected function build_rate_url(int $rating): moodle_url {
        global $USER;

        // The course is derived from the context by the endpoint, it is not a parameter.
        $params = [
            'contextid' => $this->context->id,
            'criterion' => $this->criterion,
            'rating' => $rating,
            'token' => rating_token::generate((int) $USER->id, (int) $this->context->id, $this->criterion, $rating),
        ];

        if ($this->returnurl) {
            $params['returnurl'] = $this->returnurl->out_as_local_url(false);
        }

        return new moodle_url('/filter/rating/rate.php', $params);
    }
}
