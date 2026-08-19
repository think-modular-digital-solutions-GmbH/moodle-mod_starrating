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

use context_course;
use core_user\fields;
use html_writer;
use mod_starrating\local\rating_manager;
use mod_starrating\local\report;
use moodle_url;
use table_sql;

/**
 * The individual ratings listed at the bottom of the star rating report.
 *
 * Requires lib/tablelib.php, which the report page includes before this class
 * is autoloaded.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ratings_table extends table_sql {
    /**
     * Constructor.
     *
     * @param string $uniqueid Unique id of the table.
     * @param moodle_url $baseurl Report URL carrying the current filter.
     * @param int $courseid Restrict to this course, 0 for all courses.
     * @param string $activity Restrict to this activity name, empty for all.
     * @param bool $showcourse Whether to show the course column, which says nothing
     *                         in a report that is about one course already.
     */
    public function __construct(
        string $uniqueid,
        moodle_url $baseurl,
        int $courseid,
        string $activity,
        bool $showcourse = true
    ) {
        parent::__construct($uniqueid);

        $columns = ['coursename', 'criterion', 'fullname', 'rating', 'timemodified'];
        $headers = [
            get_string('colcourse', 'mod_starrating'),
            get_string('colcriterion', 'mod_starrating'),
            get_string('coluser', 'mod_starrating'),
            get_string('colrating', 'mod_starrating'),
            get_string('coltimemodified', 'mod_starrating'),
        ];

        if (!$showcourse) {
            array_shift($columns);
            array_shift($headers);
        }

        $this->define_baseurl($baseurl);
        $this->define_columns($columns);
        $this->define_headers($headers);

        $this->sortable(true, 'timemodified', SORT_DESC);
        $this->no_sorting('fullname');
        $this->collapsible(false);
        $this->is_downloadable(true);
        $this->set_attribute('class', 'generaltable generalbox');

        // Alias u, no named parameters, no prefix, no id rename, leading comma.
        $namefields = fields::for_name()->get_sql('u', false, '', '', true);
        [$where, $params] = report::get_filter_sql($courseid, $activity);

        $this->set_sql(
            'r.id, r.rating, r.timemodified, r.userid, s.course AS courseid, s.name AS criterion,
             s.maxstars, c.fullname AS coursename' . $namefields->selects,
            report::FROM . ' JOIN {user} u ON u.id = r.userid',
            $where,
            array_merge($params, $namefields->params)
        );
    }

    /**
     * The course the rating belongs to.
     *
     * @param \stdClass $row Table row.
     * @return string
     */
    public function col_coursename($row) {
        $name = format_string($row->coursename, true, ['context' => context_course::instance($row->courseid)]);

        if ($this->is_downloading()) {
            return $name;
        }

        return html_writer::link(new moodle_url('/course/view.php', ['id' => $row->courseid]), $name);
    }

    /**
     * The activity that was rated, which is the criterion.
     *
     * @param \stdClass $row Table row.
     * @return string
     */
    public function col_criterion($row) {
        return s($row->criterion);
    }

    /**
     * The user who submitted the rating.
     *
     * @param \stdClass $row Table row.
     * @return string
     */
    public function col_fullname($row) {
        $name = fullname($row);

        if ($this->is_downloading()) {
            return $name;
        }

        return html_writer::link(
            new moodle_url('/user/view.php', ['id' => $row->userid, 'course' => $row->courseid]),
            $name
        );
    }

    /**
     * The rating itself.
     *
     * @param \stdClass $row Table row.
     * @return string
     */
    public function col_rating($row) {
        return get_string('starsofmax', 'mod_starrating', (object) [
            'stars' => (int) $row->rating,
            'max' => rating_manager::normalise_max_stars($row->maxstars),
        ]);
    }

    /**
     * When the rating was last changed.
     *
     * @param \stdClass $row Table row.
     * @return string
     */
    public function col_timemodified($row) {
        if ($this->is_downloading()) {
            return userdate($row->timemodified, get_string('strftimedatetimeshort', 'langconfig'));
        }

        return userdate($row->timemodified);
    }
}
