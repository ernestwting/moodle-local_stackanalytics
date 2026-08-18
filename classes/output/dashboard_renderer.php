<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Renders the dashboard's Model 1 / Model 2 sections from the report-builder
 * classes' plain data (model1_report.php, model2_report.php) — keeps
 * index.php thin, matching local_quizanalytics's index.php/sections_output_helper
 * split.
 *
 * Every indicator cell follows the same shape regardless of which of the
 * nine indicators it's showing: a plain-language band badge ('good' /
 * 'neutral' / 'watch', from the indicator's own compute_for_sample()) plus a
 * one-line sentence built from that indicator's real-world facts — never the
 * bare [-1, 1] value a machine-learning model would actually consume.
 *
 * @package local_stackanalytics
 * @copyright  2026 Ernest Ting <eting@caltech.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_stackanalytics\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Static HTML-building helpers for the Model 1 / Model 2 dashboard sections.
 */
class dashboard_renderer {
    /** @var array<string, string> indicator key => lang string suffix, in table-column order, for Model 1. */
    const MODEL1_INDICATORS = [
        'gradetrajectory' => 'gradetrajectory',
        'responselatencyanomaly' => 'responselatencyanomaly',
        'disengagemententropy' => 'disengagemententropy',
        'helpseekinggap' => 'helpseekinggap',
        'feedbackrevisiondistance' => 'feedbackrevisiondistance',
    ];

    /** @var array<string, string> badge 'band' token => Bootstrap badge class suffix. */
    const BAND_CLASSES = [
        'good' => 'badge-success',
        'neutral' => 'badge-secondary',
        'watch' => 'badge-warning',
    ];

    /**
     * The collapsible "About this model" panel for Model 1 — the architecture
     * doc's §2.1-2.6 content (target, indicator catalog, time-splitting,
     * evaluation), in plain language, kept out of the main table so the page
     * stays scannable.
     *
     * @return string
     */
    public static function render_model1_about(): string {
        $items = '';
        foreach (self::MODEL1_INDICATORS as $indicatorkey => $stringsuffix) {
            $items .= \html_writer::tag('li', \html_writer::tag(
                'strong',
                get_string('indicator:' . $stringsuffix, 'local_stackanalytics') . ': '
            ) . get_string('model1desc_' . $stringsuffix, 'local_stackanalytics'));
        }

        $body = \html_writer::tag('p', get_string('model1aboutbody', 'local_stackanalytics'))
            . \html_writer::tag('ul', $items)
            . \html_writer::tag('p', get_string('model1aboutfooter', 'local_stackanalytics'), ['class' => 'text-muted small']);

        return self::about_panel(get_string('target:studentatrisk', 'local_stackanalytics'), $body);
    }

    /**
     * The Model 1 student table, or a "no students" notice if there are none.
     *
     * @param \stdClass $report model1_report::build()'s return value
     * @return string
     */
    public static function render_model1_table(\stdClass $report): string {
        if (empty($report->rows)) {
            return \html_writer::tag('p', get_string('model1nostudents', 'local_stackanalytics'), ['class' => 'text-muted']);
        }

        $table = new \html_table();
        $table->head = array_merge(
            [get_string('columnstudent', 'local_stackanalytics'), get_string('columncurrentstatus', 'local_stackanalytics')],
            array_map(
                fn($stringsuffix) => get_string('indicator:' . $stringsuffix, 'local_stackanalytics'),
                array_values(self::MODEL1_INDICATORS)
            )
        );

        foreach ($report->rows as $row) {
            $tablerow = [s($row->fullname), self::render_grade_status($row->gradestatus)];
            foreach (self::MODEL1_INDICATORS as $indicatorkey => $stringsuffix) {
                $tablerow[] = self::render_indicator_cell($row->indicators[$indicatorkey], 'model1sentence_' . $stringsuffix);
            }
            $table->data[] = $tablerow;
        }

        $html = \html_writer::table($table);
        if ($report->truncated) {
            $html .= self::truncated_notice(count($report->rows), $report->total);
        }
        return $html;
    }

    /**
     * The "Current status" table cell: a passing/at-risk badge, or a muted note if it can't be computed.
     *
     * @param \stdClass $gradestatus {gradepasspercent, gradepercent, atrisk} — see model1_report::get_grade_status()
     * @return string
     */
    private static function render_grade_status(\stdClass $gradestatus): string {
        if ($gradestatus->gradepasspercent === null) {
            return \html_writer::tag(
                'span',
                get_string('gradestatusnothreshold', 'local_stackanalytics'),
                ['class' => 'text-muted small']
            );
        }
        if ($gradestatus->gradepercent === null) {
            return \html_writer::tag(
                'span',
                get_string('gradestatusnogradeyet', 'local_stackanalytics'),
                ['class' => 'text-muted small']
            );
        }

        $stringkey = $gradestatus->atrisk ? 'gradestatusatrisk' : 'gradestatuspassing';
        $badgeclass = $gradestatus->atrisk ? 'badge-warning' : 'badge-success';
        $label = get_string($stringkey, 'local_stackanalytics', (object) [
            'grade' => $gradestatus->gradepercent,
            'gradepass' => $gradestatus->gradepasspercent,
        ]);
        return \html_writer::tag('span', $label, ['class' => 'badge ' . $badgeclass]);
    }

    /**
     * One indicator table cell: a band badge plus a real-world-facts
     * sentence, or a muted "not enough data" note if $result is null.
     *
     * @param \stdClass|null $result an indicator's compute_for_sample() return value
     * @param string $sentencestringkey the lang string key for the facts sentence, taking $result->summary as $a
     * @return string
     */
    private static function render_indicator_cell(?\stdClass $result, string $sentencestringkey): string {
        if ($result === null) {
            return \html_writer::tag(
                'span',
                get_string('notenoughdata', 'local_stackanalytics'),
                ['class' => 'text-muted small']
            );
        }

        $badgeclass = self::BAND_CLASSES[$result->band] ?? self::BAND_CLASSES['neutral'];
        $badge = \html_writer::tag(
            'span',
            get_string('band_' . $result->band, 'local_stackanalytics'),
            ['class' => 'badge ' . $badgeclass]
        );
        $sentence = get_string($sentencestringkey, 'local_stackanalytics', (object) $result->summary);

        return $badge . \html_writer::tag('div', $sentence, ['class' => 'small text-muted mt-1']);
    }

    /**
     * A native, JS-free collapsible panel (no styling dependency beyond the
     * theme's own <details> rendering).
     *
     * @param string $title
     * @param string $bodyhtml already-escaped/tag-built HTML
     * @return string
     */
    private static function about_panel(string $title, string $bodyhtml): string {
        return \html_writer::tag(
            'details',
            \html_writer::tag('summary', get_string('aboutthismodel', 'local_stackanalytics') . ': ' . $title)
                . \html_writer::div($bodyhtml, 'mt-2'),
            ['class' => 'mb-3']
        );
    }

    /**
     * The "showing the first N of M" note shown under a truncated table.
     *
     * @param int $shown
     * @param int $total
     * @return string
     */
    private static function truncated_notice(int $shown, int $total): string {
        return \html_writer::tag('p', get_string('truncatednotice', 'local_stackanalytics', (object) [
            'shown' => $shown,
            'total' => $total,
        ]), ['class' => 'text-muted small']);
    }
}
