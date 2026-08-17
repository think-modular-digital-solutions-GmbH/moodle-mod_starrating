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

use filter_rating\local\rating_manager;
use filter_rating\local\report;
use html_table;
use html_writer;
use moodle_url;
use plugin_renderer_base;
use stdClass;

/**
 * Renderer for the star rating filter.
 *
 * @package    filter_rating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Render the interactive five star widget.
     *
     * @param rating_widget $widget Widget to render.
     * @return string HTML.
     */
    public function render_rating_widget(rating_widget $widget): string {
        return $this->render_from_template('filter_rating/rating', $widget->export_for_template($this));
    }

    /**
     * Render a read only star display, used for the averages in the report.
     *
     * @param float $average Average rating.
     * @param int $count Number of ratings the average is based on.
     * @return string HTML.
     */
    public function static_stars(float $average, int $count): string {
        if ($count === 0) {
            return get_string('noratingsyet', 'filter_rating');
        }

        $stars = [];
        for ($value = 1; $value <= rating_manager::MAX_STARS; $value++) {
            $stars[] = ['on' => $value <= (int) round($average)];
        }

        return $this->render_from_template('filter_rating/staticstars', [
            'stars' => $stars,
            'label' => get_string('averagetext', 'filter_rating', (object) [
                'average' => format_float($average, 1),
                'max' => rating_manager::MAX_STARS,
                'count' => $count,
            ]),
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
     * @param string $groupby Selected grouping.
     * @return string HTML.
     */
    public function report_filter(moodle_url $actionurl, array $courseoptions, array $criterionoptions,
            int $courseid, string $criterion, string $groupby): string {

        $controls = $this->filter_control('courseid', get_string('filtercourse', 'filter_rating'),
            $courseoptions, $courseid);
        $controls .= $this->filter_control('criterion', get_string('filtercriterion', 'filter_rating'),
            $criterionoptions, $criterion);
        $controls .= $this->filter_control('groupby', get_string('filtergroupby', 'filter_rating'),
            report::get_group_options(), $groupby);

        $controls .= html_writer::div(html_writer::empty_tag('input', [
            'type' => 'submit',
            'class' => 'btn btn-primary',
            'value' => get_string('applyfilter', 'filter_rating'),
        ]), 'form-group mb-2');

        $fieldset = html_writer::tag('legend', get_string('filterheading', 'filter_rating'), ['class' => 'h5']);
        $fieldset .= html_writer::div($controls, 'd-flex flex-wrap align-items-end');

        return html_writer::tag('form', html_writer::tag('fieldset', $fieldset), [
            'method' => 'get',
            'action' => $actionurl->out_omit_querystring(),
            'class' => 'filter-rating-report-filter mb-4',
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
        $id = 'filter_rating_' . $name;

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
            return $this->notification(get_string('noresults', 'filter_rating'), 'info');
        }

        $showcourse = $groupby !== report::GROUP_CRITERION;
        $showcriterion = $groupby !== report::GROUP_COURSE;

        $table = new html_table();
        $table->caption = get_string('averagescaption', 'filter_rating');
        $table->attributes['class'] = 'generaltable';
        $table->head = [];
        if ($showcourse) {
            $table->head[] = get_string('colcourse', 'filter_rating');
        }
        if ($showcriterion) {
            $table->head[] = get_string('colcriterion', 'filter_rating');
        }
        $table->head[] = get_string('colratings', 'filter_rating');
        $table->head[] = get_string('colaverage', 'filter_rating');

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
            $cells[] = $this->static_stars($row->avgrating, $row->numratings);
            $table->data[] = $cells;
        }

        $overallrow = new \html_table_row();
        $overallrow->attributes['class'] = 'font-weight-bold fw-bold';
        $label = new \html_table_cell(get_string('overallaverage', 'filter_rating'));
        $label->colspan = (int) $showcourse + (int) $showcriterion;
        $label->header = true;
        $overallrow->cells = [
            $label,
            new \html_table_cell((string) $overall->count),
            new \html_table_cell($this->static_stars($overall->average, $overall->count)),
        ];
        $table->data[] = $overallrow;

        return html_writer::table($table);
    }
}
