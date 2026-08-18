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

namespace mod_starrating\local;

use context_course;
use stdClass;

/**
 * Aggregated figures behind the star rating report.
 *
 * The criterion is the name of the activity, so grouping by criterion means
 * grouping by activity name across courses.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report {

    /** @var string Average per course and activity. */
    public const GROUP_COURSE_CRITERION = 'coursecriterion';

    /** @var string Average per course, over all its activities. */
    public const GROUP_COURSE = 'course';

    /** @var string Average per activity name, over all courses. */
    public const GROUP_CRITERION = 'criterion';

    /** @var string The tables every report query reads from. */
    public const FROM = '{starrating_rating} r
                          JOIN {starrating} s ON s.id = r.starratingid
                          JOIN {course} c ON c.id = s.course';

    /**
     * The grouping options offered by the report.
     *
     * @return array Grouping key to human readable label.
     */
    public static function get_group_options(): array {
        return [
            self::GROUP_COURSE_CRITERION => get_string('groupbycoursecriterion', 'mod_starrating'),
            self::GROUP_COURSE => get_string('groupbycourse', 'mod_starrating'),
            self::GROUP_CRITERION => get_string('groupbycriterion', 'mod_starrating'),
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
     * @param string $activity Restrict to this activity name, empty for all.
     * @return array The clause and its parameters.
     */
    public static function get_filter_sql(int $courseid, string $activity): array {
        $wheres = ['r.rating > 0'];
        $params = [];

        if ($courseid > 0) {
            $wheres[] = 's.course = :courseid';
            $params['courseid'] = $courseid;
        }
        if ($activity !== '') {
            $wheres[] = 's.name = :activity';
            $params['activity'] = $activity;
        }

        return [implode(' AND ', $wheres), $params];
    }

    /**
     * The averages shown at the top of the report.
     *
     * @param int $courseid Restrict to this course, 0 for all courses.
     * @param string $activity Restrict to this activity name, empty for all.
     * @param string $groupby One of the GROUP_* constants.
     * @return stdClass[] Rows with courseid, coursename, criterion, numratings and avgrating.
     */
    public static function get_summary(int $courseid, string $activity, string $groupby): array {
        global $DB;

        [$where, $params] = self::get_filter_sql($courseid, $activity);

        switch (self::validate_groupby($groupby)) {
            case self::GROUP_CRITERION:
                $sql = 'SELECT s.name, COUNT(r.id) AS numratings, AVG(r.rating) AS avgrating
                          FROM ' . self::FROM . '
                         WHERE ' . $where . '
                      GROUP BY s.name
                      ORDER BY s.name';
                break;

            case self::GROUP_COURSE:
                $sql = 'SELECT s.course, c.fullname, COUNT(r.id) AS numratings, AVG(r.rating) AS avgrating
                          FROM ' . self::FROM . '
                         WHERE ' . $where . '
                      GROUP BY s.course, c.fullname
                      ORDER BY c.fullname';
                break;

            default:
                $sql = 'SELECT s.course, c.fullname, s.name, COUNT(r.id) AS numratings, AVG(r.rating) AS avgrating
                          FROM ' . self::FROM . '
                         WHERE ' . $where . '
                      GROUP BY s.course, c.fullname, s.name
                      ORDER BY c.fullname, s.name';
                break;
        }

        $rows = [];
        $recordset = $DB->get_recordset_sql($sql, $params);
        foreach ($recordset as $record) {
            $row = new stdClass();
            $row->courseid = isset($record->course) ? (int) $record->course : 0;
            $row->coursename = $row->courseid
                ? format_string($record->fullname, true, ['context' => context_course::instance($row->courseid)])
                : null;
            $row->criterion = $record->name ?? null;
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
     * @param string $activity Restrict to this activity name, empty for all.
     * @return stdClass Object with the int property count and the float property average.
     */
    public static function get_overall(int $courseid, string $activity): stdClass {
        global $DB;

        [$where, $params] = self::get_filter_sql($courseid, $activity);

        $sql = 'SELECT COUNT(r.id) AS numratings, AVG(r.rating) AS avgrating
                  FROM ' . self::FROM . '
                 WHERE ' . $where;
        $record = $DB->get_record_sql($sql, $params);

        $overall = new stdClass();
        $overall->count = (int) ($record->numratings ?? 0);
        $overall->average = $overall->count ? round((float) $record->avgrating, 2) : 0.0;

        return $overall;
    }

    /**
     * Courses that hold at least one rated activity, for the course filter.
     *
     * @return array Course id to course name, prefixed by an "all courses" entry.
     */
    public static function get_course_options(): array {
        global $DB;

        $sql = 'SELECT DISTINCT s.course, c.fullname
                  FROM {starrating} s
                  JOIN {course} c ON c.id = s.course
              ORDER BY c.fullname';

        $options = [0 => get_string('allcourses', 'mod_starrating')];
        foreach ($DB->get_records_sql($sql) as $record) {
            $options[(int) $record->course] = format_string($record->fullname, true, [
                'context' => context_course::instance($record->course),
            ]);
        }

        return $options;
    }

    /**
     * Activity names in use, for the activity filter.
     *
     * @return array Name to name, prefixed by an "all activities" entry.
     */
    public static function get_activity_options(): array {
        global $DB;

        $names = $DB->get_fieldset_sql('SELECT DISTINCT name FROM {starrating} ORDER BY name');

        $options = ['' => get_string('allactivities', 'mod_starrating')];
        foreach ($names as $name) {
            $options[$name] = $name;
        }

        return $options;
    }
}
