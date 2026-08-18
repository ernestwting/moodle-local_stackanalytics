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
 * STACK Diagnostics Dashboard — the non-ML half of this plugin (architecture
 * doc §3.1's "separate STACK Diagnostics Dashboard": seed-bias ANOVA and
 * bloated-PRT-tree coverage, computed directly rather than through the
 * Analytics API's ML machinery). One row per STACK question slot in the
 * course; Model 1/2's trained predictions live in Moodle's own Site
 * Administration > Analytics > Insights instead.
 *
 * Reached from the course's secondary navigation "STACK Analytics" entry
 * (see lib.php's local_stackanalytics_extend_navigation_course()), or
 * directly via /local/stackanalytics/index.php?id=<courseid>.
 *
 * Deliberately free of student-identifying data (architecture doc §7's
 * data-minimization note) — this page is about questions, not people.
 *
 * @package local_stackanalytics
 * @copyright  2026 Ernest Ting <eting@caltech.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_stackanalytics\local\stack_course_helper;
use local_stackanalytics\diagnostics\seed_bias_report;
use local_stackanalytics\diagnostics\bloated_tree_report;

$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

require_login($course);
$context = context_course::instance($course->id);
require_capability('local/stackanalytics:view', $context);

$PAGE->set_url('/local/stackanalytics/index.php', ['id' => $courseid]);
$PAGE->set_pagelayout('report');
$PAGE->set_context($context);
$PAGE->set_title($course->shortname . ': ' . get_string('dashboardtitle', 'local_stackanalytics'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('dashboardtitle', 'local_stackanalytics'));

$slots = stack_course_helper::get_course_stack_slots($courseid);

if (empty($slots)) {
    echo $OUTPUT->notification(get_string('errornostackactivity', 'local_stackanalytics'), 'notifymessage');
    echo $OUTPUT->footer();
    exit;
}

$quiznames = $DB->get_records_menu('quiz', ['course' => $courseid], '', 'id, name');
$questionids = array_unique(array_map(fn($slot) => (int) $slot->questionid, $slots));
$questionnames = $questionids
    ? array_map(fn($q) => $q->name, $DB->get_records_list('question', 'id', $questionids, '', 'id, name'))
    : [];

foreach ($slots as $slot) {
    $questionname = $questionnames[$slot->questionid] ?? get_string('unknownquestion', 'local_stackanalytics');
    $quizname = $quiznames[$slot->quizid] ?? get_string('unknownquiz', 'local_stackanalytics');

    echo $OUTPUT->heading(format_string($questionname) . ' — ' . format_string($quizname), 4);

    // Seed bias.
    $seedgroups = seed_bias_report::get_seed_score_groups((int) $slot->quizid, (int) $slot->questionid);
    $anova = seed_bias_report::anova($seedgroups);

    echo html_writer::tag('h5', get_string('seedbiasheading', 'local_stackanalytics'));
    if ($anova === null) {
        echo html_writer::tag('p', get_string('notenoughdata', 'local_stackanalytics'), ['class' => 'text-muted']);
    } else {
        $rows = [
            [get_string('seedgroups', 'local_stackanalytics'), $anova->ngroups],
            ['F', $anova->f !== null ? format_float($anova->f, 3) : get_string('notavailable', 'local_stackanalytics')],
            ['η²', format_float($anova->etasquared, 3) . ' (' . get_string(
                'etamagnitude_' . seed_bias_report::eta_squared_magnitude($anova->etasquared),
                'local_stackanalytics'
            ) . ')'],
        ];
        $table = new html_table();
        $table->data = $rows;
        echo html_writer::table($table);
    }

    // Bloated tree / branch coverage.
    $branches = bloated_tree_report::build_report((int) $slot->quizid, (int) $slot->questionid);

    echo html_writer::tag('h5', get_string('bloatedtreeheading', 'local_stackanalytics'));
    if (empty($branches)) {
        echo html_writer::tag('p', get_string('notenoughdata', 'local_stackanalytics'), ['class' => 'text-muted']);
    } else {
        $table = new html_table();
        $table->head = [
            get_string('node', 'local_stackanalytics'),
            get_string('branch', 'local_stackanalytics'),
            get_string('traversals', 'local_stackanalytics'),
            get_string('coverage', 'local_stackanalytics'),
        ];
        foreach ($branches as $branch) {
            $table->data[] = [
                s($branch->nodename),
                s($branch->branch),
                $branch->count,
                get_string('coverage_' . $branch->classification, 'local_stackanalytics'),
            ];
        }
        echo html_writer::table($table);
    }
}

echo $OUTPUT->footer();
