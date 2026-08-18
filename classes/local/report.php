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
     * The average as a fraction of the scale, together with the scale itself.
     *
     * Every activity has its own number of stars, so a plain AVG(rating) over
     * several activities would add three out of five to three out of ten. The
     * average is therefore always computed as a fraction of the scale, which
     * for a single scale is the same figure expressed differently, and the
     * shortest and longest scale in the group come along so that a caller can
     * tell whether the group has one scale at all.
     *
     * The multiplication by 1.0000 is what keeps the division out of integer
     * arithmetic, with enough decimal places left for the fraction to survive
     * being multiplied back into stars.
     *
     * @return string The select list, without a leading comma.
     */
    protected static function average_select(): string {
        return 'COUNT(r.id) AS numratings,
                AVG(r.rating * 1.0000 / s.maxstars) AS avgfraction,
                MIN(s.maxstars) AS minmaxstars,
                MAX(s.maxstars) AS maxmaxstars';
    }

    /**
     * Turn the aggregate columns of one row into count, scale and average.
     *
     * @param stdClass $record A record selected with average_select().
     * @return array The count, the shared scale length or 0 when the group mixes
     *               scales, the average in stars on that scale, and the average
     *               as a fraction of the scale.
     */
    protected static function read_average(stdClass $record): array {
        $count = (int) ($record->numratings ?? 0);
        $fraction = $count ? (float) $record->avgfraction : 0.0;

        $minmax = (int) ($record->minmaxstars ?? 0);
        $maxmax = (int) ($record->maxmaxstars ?? 0);
        $maxstars = ($count && $minmax === $maxmax) ? $minmax : 0;

        return [$count, $maxstars, round($fraction * $maxstars, 2), round($fraction, 4)];
    }

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
     * @return stdClass[] Rows with courseid, coursename, criterion, numratings, maxstars,
     *                     avgrating and avgfraction.
     */
    public static function get_summary(int $courseid, string $activity, string $groupby): array {
        global $DB;

        [$where, $params] = self::get_filter_sql($courseid, $activity);

        switch (self::validate_groupby($groupby)) {
            case self::GROUP_CRITERION:
                $sql = 'SELECT s.name, ' . self::average_select() . '
                          FROM ' . self::FROM . '
                         WHERE ' . $where . '
                      GROUP BY s.name
                      ORDER BY s.name';
                break;

            case self::GROUP_COURSE:
                $sql = 'SELECT s.course, c.fullname, ' . self::average_select() . '
                          FROM ' . self::FROM . '
                         WHERE ' . $where . '
                      GROUP BY s.course, c.fullname
                      ORDER BY c.fullname';
                break;

            default:
                $sql = 'SELECT s.course, c.fullname, s.name, ' . self::average_select() . '
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
            [$row->numratings, $row->maxstars, $row->avgrating, $row->avgfraction] = self::read_average($record);
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
     * @return stdClass Object with count, maxstars, average and fraction, as described
     *                  by read_average().
     */
    public static function get_overall(int $courseid, string $activity): stdClass {
        global $DB;

        [$where, $params] = self::get_filter_sql($courseid, $activity);

        $sql = 'SELECT ' . self::average_select() . '
                  FROM ' . self::FROM . '
                 WHERE ' . $where;
        $record = $DB->get_record_sql($sql, $params);

        $overall = new stdClass();
        [$overall->count, $overall->maxstars, $overall->average, $overall->fraction] = self::read_average($record);

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
     * @param int $courseid Restrict to the activities of this course, 0 for the whole site.
     * @return array Name to name, prefixed by an "all activities" entry.
     */
    public static function get_activity_options(int $courseid = 0): array {
        global $DB;

        $sql = 'SELECT DISTINCT name FROM {starrating}';
        $params = [];

        if ($courseid > 0) {
            $sql .= ' WHERE course = :courseid';
            $params['courseid'] = $courseid;
        }

        $names = $DB->get_fieldset_sql($sql . ' ORDER BY name', $params);

        $options = ['' => get_string('allactivities', 'mod_starrating')];
        foreach ($names as $name) {
            $options[$name] = $name;
        }

        return $options;
    }

    /**
     * One row per star rating activity of a course, for the course report.
     *
     * Unlike the site wide report this groups by activity rather than by
     * activity name, because one course can hold two activities with the same
     * name, and it lists activities nobody has rated yet, which is exactly what
     * a teacher waiting for feedback wants to see.
     *
     * @param int $courseid The course to report on.
     * @param string $activity Restrict to this activity name, empty for all.
     * @return stdClass[] Rows with instanceid, cmid, name, numratings, maxstars,
     *                    avgrating and avgfraction.
     */
    public static function get_course_summary(int $courseid, string $activity = ''): array {
        global $DB;

        $params = ['courseid' => $courseid, 'modname' => 'starrating'];

        // An activity that is being deleted is on its way out, not a result.
        $where = 's.course = :courseid AND cm.deletioninprogress = 0';

        if ($activity !== '') {
            $where .= ' AND s.name = :activity';
            $params['activity'] = $activity;
        }

        // A left join, so an activity without a single rating still gets a row.
        $sql = 'SELECT s.id, s.name, s.maxstars, cm.id AS cmid,
                       COUNT(r.id) AS numratings,
                       AVG(r.rating * 1.0000 / s.maxstars) AS avgfraction
                  FROM {starrating} s
                  JOIN {modules} m ON m.name = :modname
                  JOIN {course_modules} cm ON cm.instance = s.id AND cm.module = m.id
             LEFT JOIN {' . rating_manager::TABLE . '} r ON r.starratingid = s.id AND r.rating > 0
                 WHERE ' . $where . '
              GROUP BY s.id, s.name, s.maxstars, cm.id
              ORDER BY s.name, cm.id';

        $rows = [];
        $recordset = $DB->get_recordset_sql($sql, $params);
        foreach ($recordset as $record) {
            $row = new stdClass();
            $row->instanceid = (int) $record->id;
            $row->cmid = (int) $record->cmid;
            $row->name = $record->name;
            $row->maxstars = rating_manager::normalise_max_stars($record->maxstars);
            $row->numratings = (int) $record->numratings;
            $row->avgfraction = $row->numratings ? round((float) $record->avgfraction, 4) : 0.0;
            $row->avgrating = round($row->avgfraction * $row->maxstars, 2);
            $rows[] = $row;
        }
        $recordset->close();

        return $rows;
    }
}
