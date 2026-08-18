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

use cm_info;
use context_module;
use mod_starrating\local\rating_manager;
use mod_starrating\local\rating_token;
use moodle_url;
use renderable;
use renderer_base;
use templatable;

/**
 * The five stars of one star rating activity.
 *
 * Rendered inline on the course page, the way a label renders its text, and
 * again on the activity page itself. Every star is a plain link to rate.php,
 * which stores the rating and returns to the page it was given on.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rating_widget implements renderable, templatable {

    /**
     * Constructor.
     *
     * @param cm_info $cm The course module the stars belong to.
     * @param moodle_url|null $returnurl Page to return to after rating.
     * @param bool $showname Whether to show the activity name above the stars.
     */
    public function __construct(
        protected cm_info $cm,
        protected ?moodle_url $returnurl = null,
        protected bool $showname = true
    ) {
    }

    /**
     * Build the template context.
     *
     * @param renderer_base $output Renderer.
     * @return array Template context.
     */
    public function export_for_template(renderer_base $output): array {
        $context = context_module::instance($this->cm->id);
        $name = format_string($this->cm->name, true, ['context' => $context]);
        $customdata = (array) $this->cm->customdata;

        $userrating = rating_manager::get_user_rating($this->cm->instance);
        $stats = rating_manager::get_stats($this->cm->instance);
        $canrate = rating_manager::can_rate($context);
        $showaverage = !empty($customdata['showaverage']);
        $allowclear = !empty($customdata['allowclear']);

        $stars = [];
        for ($value = 1; $value <= rating_manager::MAX_STARS; $value++) {
            $stars[] = [
                'value' => $value,
                'on' => $value <= $userrating,
                'url' => $canrate ? $this->build_rate_url($value)->out(false) : null,
                'label' => get_string('ratestars', 'mod_starrating', (object) [
                    'stars' => $value,
                    'max' => rating_manager::MAX_STARS,
                    'criterion' => $name,
                ]),
            ];
        }

        return [
            'cmid' => $this->cm->id,
            'instanceid' => $this->cm->instance,
            'name' => $name,
            'showname' => $this->showname,
            'userrating' => $userrating,
            'count' => $stats->count,
            'average' => $stats->average,
            'canrate' => $canrate,
            'stars' => $stars,
            'grouplabel' => get_string('ratinggroup', 'mod_starrating', $name),
            'statustext' => rating_manager::get_status_text($name, $userrating),
            'showaverage' => $showaverage,
            'averagetext' => rating_manager::get_average_text($stats),
            'canclear' => $canrate && $allowclear && $userrating > 0,
            'clearurl' => $canrate ? $this->build_rate_url(0)->out(false) : null,
            'clearlabel' => get_string('clearrating', 'mod_starrating'),
            'clearaltlabel' => get_string('clearratingaria', 'mod_starrating', $name),
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

        $params = [
            'id' => $this->cm->id,
            'rating' => $rating,
            'token' => rating_token::generate((int) $USER->id, (int) $this->cm->id, $rating),
        ];

        if ($this->returnurl) {
            $params['returnurl'] = $this->returnurl->out_as_local_url(false);
        }

        return new moodle_url('/mod/starrating/rate.php', $params);
    }
}
