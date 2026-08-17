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

namespace filter_rating\local;

use context_course;
use stdClass;

/**
 * Aggregated figures behind the rating report.
 *
 * @package    filter_rating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report {

    /** @var string Average per course and criterion. */
    public const GROUP_COURSE_CRITERION = 'coursecriterion';

    /** @var string Average per course, over all its criteria. */
    public const GROUP_COURSE = 'course';

    /** @var string Average per criterion, over all courses. */
    public const GROUP_CRITERION = 'criterion';

    /**
     * The grouping options offered by the report.
     *
     * @return array Grouping key to human readable label.
     */
    public static function get_group_options(): array {
        return [
            self::GROUP_COURSE_CRITERION => get_string('groupbycoursecriterion', 'filter_rating'),
            self::GROUP_COURSE => get_string('groupbycourse', 'filter_rating'),
            self::GROUP_CRITERION => get_string('groupbycriterion', 'filter_rating'),
        ];
    }

    /**
     * Make sure a grouping coming from a request is one we support.
     *
     * @param string $groupby Requested grouping.
     * @return string A valid grouping.
     */
    public static function validate_groupby(string $groupby): string {
        return array_key_exists($groupby, self::get_group_options()) ? $groupby : self::GROUP_COURSE_CRITERION;
    }

    /**
     * Build the WHERE clause shared by all report queries.
     *
     * @param int $courseid Restrict to this course, 0 for all courses.
     * @param string $criterion Restrict to this criterion, empty for all criteria.
     * @return array The clause and its parameters.
     */
    public static function get_filter_sql(int $courseid, string $criterion): array {
        $wheres = ['r.rating > 0'];
        $params = [];

        if ($courseid > 0) {
            $wheres[] = 'r.courseid = :courseid';
            $params['courseid'] = $courseid;
        }
        if ($criterion !== '') {
            $wheres[] = 'r.criterion = :criterion';
            $params['criterion'] = $criterion;
        }

        return [implode(' AND ', $wheres), $params];
    }

    /**
     * The averages shown at the top of the report.
     *
     * @param int $courseid Restrict to this course, 0 for all courses.
     * @param string $criterion Restrict to this criterion, empty for all criteria.
     * @param string $groupby One of the GROUP_* constants.
     * @return stdClass[] Rows with coursename, criterion, numratings and avgrating.
     */
    public static function get_summary(int $courseid, string $criterion, string $groupby): array {
        global $DB;

        [$where, $params] = self::get_filter_sql($courseid, $criterion);

        switch (self::validate_groupby($groupby)) {
            case self::GROUP_CRITERION:
                $sql = "SELECT r.criterion, COUNT(r.id) AS numratings, AVG(r.rating) AS avgrating
                          FROM {filter_rating} r
                         WHERE {$where}
                      GROUP BY r.criterion
                      ORDER BY r.criterion";
                break;

            case self::GROUP_COURSE:
                $sql = "SELECT r.courseid, c.fullname, COUNT(r.id) AS numratings, AVG(r.rating) AS avgrating
                          FROM {filter_rating} r
                          JOIN {course} c ON c.id = r.courseid
                         WHERE {$where}
                      GROUP BY r.courseid, c.fullname
                      ORDER BY c.fullname";
                break;

            default:
                $sql = "SELECT r.courseid, c.fullname, r.criterion, COUNT(r.id) AS numratings, AVG(r.rating) AS avgrating
                          FROM {filter_rating} r
                          JOIN {course} c ON c.id = r.courseid
                         WHERE {$where}
                      GROUP BY r.courseid, c.fullname, r.criterion
                      ORDER BY c.fullname, r.criterion";
                break;
        }

        $rows = [];
        $recordset = $DB->get_recordset_sql($sql, $params);
        foreach ($recordset as $record) {
            $row = new stdClass();
            $row->courseid = isset($record->courseid) ? (int) $record->courseid : 0;
            $row->coursename = $row->courseid
                ? format_string($record->fullname, true, ['context' => context_course::instance($row->courseid)])
                : null;
            $row->criterion = $record->criterion ?? null;
            $row->numratings = (int) $record->numratings;
            $row->avgrating = round((float) $record->avgrating, 2);
            $rows[] = $row;
        }
        $recordset->close();

        return $rows;
    }

    /**
     * Count and average over everything the current filter selects.
     *
     * @param int $courseid Restrict to this course, 0 for all courses.
     * @param string $criterion Restrict to this criterion, empty for all criteria.
     * @return stdClass Object with the int property count and the float property average.
     */
    public static function get_overall(int $courseid, string $criterion): stdClass {
        global $DB;

        [$where, $params] = self::get_filter_sql($courseid, $criterion);

        $sql = "SELECT COUNT(r.id) AS numratings, AVG(r.rating) AS avgrating
                  FROM {filter_rating} r
                 WHERE {$where}";
        $record = $DB->get_record_sql($sql, $params);

        $overall = new stdClass();
        $overall->count = (int) ($record->numratings ?? 0);
        $overall->average = $overall->count ? round((float) $record->avgrating, 2) : 0.0;

        return $overall;
    }

    /**
     * Courses that have at least one rating, for the course filter.
     *
     * @return array Course id to course name, prefixed by an "all courses" entry.
     */
    public static function get_course_options(): array {
        global $DB;

        $sql = 'SELECT DISTINCT r.courseid, c.fullname
                  FROM {filter_rating} r
                  JOIN {course} c ON c.id = r.courseid
              ORDER BY c.fullname';

        $options = [0 => get_string('allcourses', 'filter_rating')];
        foreach ($DB->get_records_sql($sql) as $record) {
            $options[(int) $record->courseid] = format_string($record->fullname, true, [
                'context' => context_course::instance($record->courseid),
            ]);
        }

        return $options;
    }

    /**
     * Criteria that have at least one rating, for the criterion filter.
     *
     * @return array Criterion to criterion, prefixed by an "all criteria" entry.
     */
    public static function get_criterion_options(): array {
        global $DB;

        $criteria = $DB->get_fieldset_sql('SELECT DISTINCT criterion FROM {filter_rating} ORDER BY criterion');

        $options = ['' => get_string('allcriteria', 'filter_rating')];
        foreach ($criteria as $criterion) {
            $options[$criterion] = $criterion;
        }

        return $options;
    }
}
