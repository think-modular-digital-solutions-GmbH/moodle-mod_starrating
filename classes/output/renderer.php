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

use mod_starrating\local\report;
use html_table;
use html_writer;
use moodle_url;
use plugin_renderer_base;
use stdClass;

/**
 * Renderer for the star rating activity.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Render the interactive star widget.
     *
     * @param rating_widget $widget Widget to render.
     * @return string HTML.
     */
    public function render_rating_widget(rating_widget $widget): string {
        return $this->render_from_template('mod_starrating/rating', $widget->export_for_template($this));
    }

    /**
     * Render a read only star display, used for the averages in the report.
     *
     * @param float $average Average rating, in stars on the given scale.
     * @param int $count Number of ratings the average is based on.
     * @param int $maxstars Length of the rating scale.
     * @return string HTML.
     */
    public function static_stars(float $average, int $count, int $maxstars): string {
        if ($count === 0) {
            return get_string('noratingsyet', 'mod_starrating');
        }

        $stars = [];
        for ($value = 1; $value <= $maxstars; $value++) {
            $stars[] = ['on' => $value <= (int) round($average)];
        }

        return $this->render_from_template('mod_starrating/staticstars', [
            'stars' => $stars,
            'label' => get_string('averagetext', 'mod_starrating', (object) [
                'average' => format_float($average, 1),
                'max' => $maxstars,
                'count' => $count,
            ]),
        ]);
    }

    /**
     * The average of a group of ratings, however long the scales in it are.
     *
     * Averaging over activities with scales of different lengths cannot be shown
     * as stars, because there is no scale to draw them on. Such a group is
     * reported as a share of the scale instead, and says so.
     *
     * @param int $count Number of ratings.
     * @param int $maxstars Shared scale length, 0 when the group mixes scales.
     * @param float $average Average in stars, only meaningful when $maxstars is set.
     * @param float $fraction Average as a fraction of the scale, 0 to 1.
     * @return string HTML.
     */
    public function average_cell(int $count, int $maxstars, float $average, float $fraction): string {
        if ($count === 0) {
            return get_string('noratingsyet', 'mod_starrating');
        }

        if ($maxstars > 0) {
            return $this->static_stars($average, $count, $maxstars);
        }

        return get_string('averagemixed', 'mod_starrating', (object) [
            'percent' => format_float($fraction * 100, 1),
            'count' => $count,
        ]);
    }

    /**
     * The course, criterion and grouping selectors above the report.
     *
     * @param moodle_url $actionurl The report URL.
     * @param array $courseoptions Course id to course name.
     * @param array $criterionoptions Criterion to criterion.
     * @param int $courseid Selected course, 0 for all.
     * @param string $criterion Selected criterion, empty for all.
     * @param string|null $groupby Selected grouping, null to leave the grouping control out.
     * @return string HTML.
     */
    public function report_filter(moodle_url $actionurl, array $courseoptions, array $criterionoptions,
            int $courseid, string $criterion, ?string $groupby = null): string {

        $controls = $this->filter_control('courseid', get_string('filtercourse', 'mod_starrating'),
            $courseoptions, $courseid);
        $controls .= $this->filter_control('activity', get_string('filteractivity', 'mod_starrating'),
            $criterionoptions, $criterion);

        if ($groupby !== null) {
            $controls .= $this->filter_control('groupby', get_string('filtergroupby', 'mod_starrating'),
                report::get_group_options(), $groupby);
        }

        $controls .= html_writer::div(html_writer::empty_tag('input', [
            'type' => 'submit',
            'class' => 'btn btn-primary',
            'value' => get_string('applyfilter', 'mod_starrating'),
        ]), 'form-group mb-2');

        $fieldset = html_writer::tag('legend', get_string('filterheading', 'mod_starrating'), ['class' => 'h5']);
        $fieldset .= html_writer::div($controls, 'd-flex flex-wrap align-items-end');

        return html_writer::tag('form', html_writer::tag('fieldset', $fieldset), [
            'method' => 'get',
            'action' => $actionurl->out_omit_querystring(),
            'class' => 'starrating-report-filter mb-4',
        ]);
    }

    /**
     * The activity selector above the course report.
     *
     * The course is fixed here, so it travels as a hidden field rather than as a
     * select, and the report stays on the course it was opened for.
     *
     * @param moodle_url $actionurl The course report URL.
     * @param array $criterionoptions Activity name to activity name.
     * @param int $courseid The course being reported on.
     * @param string $criterion Selected activity name, empty for all.
     * @return string HTML.
     */
    public function course_report_filter(moodle_url $actionurl, array $criterionoptions,
            int $courseid, string $criterion): string {

        $controls = $this->filter_control('activity', get_string('filteractivity', 'mod_starrating'),
            $criterionoptions, $criterion);

        $controls .= html_writer::div(html_writer::empty_tag('input', [
            'type' => 'submit',
            'class' => 'btn btn-primary',
            'value' => get_string('applyfilter', 'mod_starrating'),
        ]), 'form-group mb-2');

        $fieldset = html_writer::tag('legend', get_string('filterheading', 'mod_starrating'), ['class' => 'h5']);
        $fieldset .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $courseid]);
        $fieldset .= html_writer::div($controls, 'd-flex flex-wrap align-items-end');

        return html_writer::tag('form', html_writer::tag('fieldset', $fieldset), [
            'method' => 'get',
            'action' => $actionurl->out_omit_querystring(),
            'class' => 'starrating-report-filter mb-4',
        ]);
    }

    /**
     * One labelled select in the report filter.
     *
     * @param string $name Request parameter name.
     * @param string $label Visible label.
     * @param array $options Value to label.
     * @param string|int $selected Currently selected value.
     * @return string HTML.
     */
    protected function filter_control(string $name, string $label, array $options, $selected): string {
        $id = 'starrating_' . $name;

        $control = html_writer::label($label, $id, true, ['class' => 'd-block']);
        $control .= html_writer::select($options, $name, $selected, false, [
            'id' => $id,
            'class' => 'custom-select form-select',
        ]);

        return html_writer::div($control, 'form-group mr-3 mb-2');
    }

    /**
     * The averages table.
     *
     * @param stdClass[] $rows Rows returned by report::get_summary().
     * @param string $groupby The grouping the rows were built with.
     * @param stdClass $overall Count and average over the whole selection.
     * @return string HTML.
     */
    public function summary_table(array $rows, string $groupby, stdClass $overall): string {
        if (empty($rows)) {
            return $this->notification(get_string('noresults', 'mod_starrating'), 'info');
        }

        $showcourse = $groupby !== report::GROUP_CRITERION;
        $showcriterion = $groupby !== report::GROUP_COURSE;

        $table = new html_table();
        $table->caption = get_string('averagescaption', 'mod_starrating');
        $table->attributes['class'] = 'generaltable';
        $table->head = [];
        if ($showcourse) {
            $table->head[] = get_string('colcourse', 'mod_starrating');
        }
        if ($showcriterion) {
            $table->head[] = get_string('colcriterion', 'mod_starrating');
        }
        $table->head[] = get_string('colratings', 'mod_starrating');
        $table->head[] = get_string('colaverage', 'mod_starrating');

        foreach ($rows as $row) {
            $cells = [];
            if ($showcourse) {
                $cells[] = html_writer::link(
                    new moodle_url('/course/view.php', ['id' => $row->courseid]),
                    $row->coursename
                );
            }
            if ($showcriterion) {
                $cells[] = s($row->criterion);
            }
            $cells[] = $row->numratings;
            $cells[] = $this->average_cell($row->numratings, $row->maxstars, $row->avgrating, $row->avgfraction);
            $table->data[] = $cells;
        }

        $overallrow = new \html_table_row();
        $overallrow->attributes['class'] = 'font-weight-bold fw-bold';
        $label = new \html_table_cell(get_string('overallaverage', 'mod_starrating'));
        $label->colspan = (int) $showcourse + (int) $showcriterion;
        $label->header = true;
        $overallrow->cells = [
            $label,
            new \html_table_cell((string) $overall->count),
            new \html_table_cell($this->average_cell(
                $overall->count, $overall->maxstars, $overall->average, $overall->fraction)),
        ];
        $table->data[] = $overallrow;

        return html_writer::table($table);
    }

    /**
     * The averages of every star rating in one course, for its teachers.
     *
     * One row per activity, including the ones nobody has rated yet, because an
     * activity that is waiting for its first rating is a result too.
     *
     * @param stdClass[] $rows Rows returned by report::get_course_summary().
     * @return string HTML.
     */
    public function course_summary_table(array $rows): string {
        if (empty($rows)) {
            return $this->notification(get_string('noresults', 'mod_starrating'), 'info');
        }

        $table = new html_table();
        $table->caption = get_string('coursesummarycaption', 'mod_starrating');
        $table->attributes['class'] = 'generaltable';
        $table->head = [
            get_string('colcriterion', 'mod_starrating'),
            get_string('colscale', 'mod_starrating'),
            get_string('colratings', 'mod_starrating'),
            get_string('colaverage', 'mod_starrating'),
        ];

        foreach ($rows as $row) {
            $table->data[] = [
                html_writer::link(
                    new moodle_url('/mod/starrating/view.php', ['id' => $row->cmid]),
                    format_string($row->name)
                ),
                $row->maxstars,
                $row->numratings,
                $this->average_cell($row->numratings, $row->maxstars, $row->avgrating, $row->avgfraction),
            ];
        }

        return html_writer::table($table);
    }
}
