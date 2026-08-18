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
 * STACK Analytics Dashboard — one page, three sections, one per the
 * architecture doc's own structure: Model 1 (student risk & behaviour, §2),
 * Model 2 (question/PRT quality, §3), and the non-ML Diagnostics Dashboard
 * (seed-bias ANOVA and bloated-PRT-tree coverage, computed directly rather
 * than through the Analytics API's ML machinery, §3.1's "separate STACK
 * Diagnostics Dashboard"). Both models ship disabled by default (alpha
 * stage — see db/analytics.php), so what this page shows is each model's
 * *live indicator readings*, not a trained model's predictions; those, once
 * an administrator enables and trains a model, live in Moodle's own Site
 * Administration > Analytics > Insights instead.
 *
 * Reached from the course's secondary navigation "STACK Analytics" entry
 * (see lib.php's local_stackanalytics_extend_navigation_course()), or
 * directly via /local/stackanalytics/index.php?id=<courseid> — the
 * course selector at the top of the page switches between any course the
 * viewer has local/stackanalytics:view in.
 *
 * @package local_stackanalytics
 * @copyright  2026 Ernest Ting <eting@caltech.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_stackanalytics\local\stack_course_helper;
use local_stackanalytics\diagnostics\seed_bias_report;
use local_stackanalytics\diagnostics\bloated_tree_report;
use local_stackanalytics\diagnostics\concept_dependency_report;
use local_stackanalytics\analytics\report\model1_report;
use local_stackanalytics\analytics\report\model2_report;
use local_stackanalytics\output\dashboard_renderer;

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

echo html_writer::div(get_string('pageintro', 'local_stackanalytics'), 'alert alert-info');
echo html_writer::div(get_string('pageintrolivedata', 'local_stackanalytics'), 'alert alert-info');

$viewablecourses = stack_course_helper::get_viewable_courses();
if (count($viewablecourses) > 1) {
    $courseoptions = [];
    foreach ($viewablecourses as $viewablecourse) {
        $courseoptions[$viewablecourse->id] = format_string($viewablecourse->fullname);
    }
    $courseselector = new single_select(
        new moodle_url('/local/stackanalytics/index.php'),
        'id',
        $courseoptions,
        $courseid,
        null
    );
    $courseselector->label = get_string('courseselectorlabel', 'local_stackanalytics');
    echo $OUTPUT->render($courseselector);
}

$slots = stack_course_helper::get_course_stack_slots($courseid);

if (empty($slots)) {
    echo $OUTPUT->notification(get_string('errornostackactivity', 'local_stackanalytics'), 'notifymessage');
    echo $OUTPUT->footer();
    exit;
}

echo html_writer::tag('p', get_string('jumptosection', 'local_stackanalytics') . ' ' . implode(' · ', [
    html_writer::link('#stackanalytics-model1', get_string('model1heading', 'local_stackanalytics')),
    html_writer::link('#stackanalytics-model2', get_string('model2heading', 'local_stackanalytics')),
    html_writer::link('#stackanalytics-diagnostics', get_string('diagnosticsheading', 'local_stackanalytics')),
]), ['class' => 'text-muted']);

echo html_writer::div(get_string('responsibleusecallout', 'local_stackanalytics'), 'alert alert-warning');

echo html_writer::tag('a', '', ['id' => 'stackanalytics-model1']);
echo $OUTPUT->heading(get_string('model1heading', 'local_stackanalytics'), 3);
echo html_writer::tag('p', get_string('model1intro', 'local_stackanalytics'));
echo dashboard_renderer::render_model1_about();
echo dashboard_renderer::render_model1_table(model1_report::build($courseid));

// Narrows the (potentially very long) per-question Diagnostics section below
// to one quiz at a time — the course-wide Model 1 section further down isn't
// quiz-scoped (its indicators are per-student, not per-quiz-slot), so this
// selector only ever affects Diagnostics/Model 2 content.
$quizid = optional_param('quizid', 0, PARAM_INT);
$quiznames = $DB->get_records_menu('quiz', ['course' => $courseid], '', 'id, name');

$quizidsinslots = array_unique(array_map(fn($slot) => (int) $slot->quizid, $slots));
if (count($quizidsinslots) > 1) {
    $quizoptions = [];
    foreach ($quizidsinslots as $slotquizid) {
        $quizoptions[$slotquizid] = format_string($quiznames[$slotquizid] ?? get_string('unknownquiz', 'local_stackanalytics'));
    }
    $quizselector = new single_select(
        new moodle_url('/local/stackanalytics/index.php', ['id' => $courseid]),
        'quizid',
        $quizoptions,
        $quizid,
        [0 => get_string('allquizzes', 'local_stackanalytics')]
    );
    $quizselector->label = get_string('quizselectorlabel', 'local_stackanalytics');
    echo $OUTPUT->render($quizselector);
}

if ($quizid !== 0) {
    $slots = array_filter($slots, fn($slot) => (int) $slot->quizid === $quizid);
}

echo html_writer::tag('a', '', ['id' => 'stackanalytics-model2']);
echo $OUTPUT->heading(get_string('model2heading', 'local_stackanalytics'), 3);
echo html_writer::tag('p', get_string('model2intro', 'local_stackanalytics'));
echo dashboard_renderer::render_model2_about();
echo dashboard_renderer::render_model2_table(model2_report::build($courseid, $quizid !== 0 ? $quizid : null));

echo html_writer::tag('a', '', ['id' => 'stackanalytics-diagnostics']);
echo $OUTPUT->heading(get_string('diagnosticsheading', 'local_stackanalytics'), 3);
echo html_writer::tag('p', get_string('diagnosticsintro', 'local_stackanalytics'));

if (!concept_dependency_report::is_available()) {
    echo html_writer::tag('p', get_string('conceptdependencynote', 'local_stackanalytics'), ['class' => 'text-muted small']);
}

// Soft cap, same purpose as model1_report::MAX_ROWS/model2_report::MAX_ROWS —
// a course with many STACK questions shouldn't make this page unusably slow.
$diagnosticsslotstotal = count($slots);
$maxdiagnosticsslots = 100;
if ($diagnosticsslotstotal > $maxdiagnosticsslots) {
    $slots = array_slice($slots, 0, $maxdiagnosticsslots, true);
}

$questionids = array_unique(array_map(fn($slot) => (int) $slot->questionid, $slots));
$questionnames = $questionids
    ? array_map(fn($q) => $q->name, $DB->get_records_list('question', 'id', $questionids, '', 'id, name'))
    : [];

// Jump-to links for the per-question blocks below — the same anchor ids
// the heading loop sets on each block's heading.
if (count($slots) > 1) {
    $jumplinks = [];
    foreach ($slots as $slot) {
        $questionname = $questionnames[$slot->questionid] ?? get_string('unknownquestion', 'local_stackanalytics');
        $jumplinks[] = html_writer::link('#stackanalytics-slot-' . $slot->id, format_string($questionname));
    }
    echo html_writer::tag(
        'p',
        get_string('jumptoquestion', 'local_stackanalytics') . ' ' . implode(' · ', $jumplinks),
        ['class' => 'text-muted']
    );
}

foreach ($slots as $slot) {
    $questionname = $questionnames[$slot->questionid] ?? get_string('unknownquestion', 'local_stackanalytics');
    $quizname = $quiznames[$slot->quizid] ?? get_string('unknownquiz', 'local_stackanalytics');

    echo html_writer::tag('a', '', ['id' => 'stackanalytics-slot-' . $slot->id]);
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

if ($diagnosticsslotstotal > $maxdiagnosticsslots) {
    echo html_writer::tag('p', get_string('truncatednotice', 'local_stackanalytics', (object) [
        'shown' => count($slots),
        'total' => $diagnosticsslotstotal,
    ]), ['class' => 'text-muted small']);
}

echo $OUTPUT->footer();
