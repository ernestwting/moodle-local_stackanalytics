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
use local_stackanalytics\diagnostics\concept_dependency_report;
use local_stackanalytics\analytics\report\model1_report;
use local_stackanalytics\analytics\report\model2_report;
use local_stackanalytics\analytics\report\diagnostics_report;
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
    echo html_writer::div($OUTPUT->render($courseselector), 'd-inline-block mr-4 mb-3');
}

$slots = stack_course_helper::get_course_stack_slots($courseid);

if (empty($slots)) {
    echo $OUTPUT->notification(get_string('errornostackactivity', 'local_stackanalytics'), 'notifymessage');
    echo $OUTPUT->footer();
    exit;
}

// One section rendered per page load, not all three at once — the previous
// anchor-link "Jump to section" nav still rendered (and queried) everything
// on every load, which is what actually made the page unusably long on a
// course with many students/questions.
$view = optional_param('view', 'model1', PARAM_ALPHANUM);
if (!in_array($view, ['model1', 'model2', 'diagnostics'], true)) {
    $view = 'model1';
}
$viewoptions = [
    'model1' => get_string('model1heading', 'local_stackanalytics'),
    'model2' => get_string('model2heading', 'local_stackanalytics'),
    'diagnostics' => get_string('diagnosticsheading', 'local_stackanalytics'),
];
$viewselector = new single_select(
    new moodle_url('/local/stackanalytics/index.php', ['id' => $courseid]),
    'view',
    $viewoptions,
    $view,
    null
);
$viewselector->label = get_string('viewselectorlabel', 'local_stackanalytics');
echo html_writer::div($OUTPUT->render($viewselector), 'd-inline-block mb-3');

echo html_writer::div(get_string('responsibleusecallout', 'local_stackanalytics'), 'alert alert-warning mt-2');

// Narrows Model 2/Diagnostics (both per-question) to one quiz at a time —
// Model 1 isn't quiz-scoped (its indicators are per-student), so this
// selector is skipped entirely on that view.
$quizid = optional_param('quizid', 0, PARAM_INT);

if ($view === 'model1') {
    echo $OUTPUT->heading(get_string('model1heading', 'local_stackanalytics'), 3);
    echo html_writer::tag('p', get_string('model1intro', 'local_stackanalytics'));
    echo dashboard_renderer::render_model1_about();
    echo dashboard_renderer::render_model1_table(model1_report::build($courseid));
} else {
    $quiznames = $DB->get_records_menu('quiz', ['course' => $courseid], '', 'id, name');
    $slotsperquiz = [];
    foreach ($slots as $slot) {
        $slotsperquiz[(int) $slot->quizid] = ($slotsperquiz[(int) $slot->quizid] ?? 0) + 1;
    }
    if (count($slotsperquiz) > 1) {
        // Each option names both the quiz and how many STACK questions it
        // has, so the list reads as "this quiz, with this much STACK
        // content" rather than a bare, unfamiliar-looking name.
        $quizoptions = [];
        foreach ($slotsperquiz as $slotquizid => $questioncount) {
            $quizname = format_string($quiznames[$slotquizid] ?? get_string('unknownquiz', 'local_stackanalytics'));
            $quizoptions[$slotquizid] = get_string('quizoptionlabel', 'local_stackanalytics', (object) [
                'name' => $quizname,
                'count' => $questioncount,
            ]);
        }
        asort($quizoptions);
        $quizselector = new single_select(
            new moodle_url('/local/stackanalytics/index.php', ['id' => $courseid, 'view' => $view]),
            'quizid',
            $quizoptions,
            $quizid,
            [0 => get_string('allquizzes', 'local_stackanalytics')]
        );
        $quizselector->label = get_string('quizselectorlabel', 'local_stackanalytics');
        echo html_writer::div($OUTPUT->render($quizselector), 'd-inline-block mb-3');
    } else {
        $quizid = 0; // Only one quiz in this course — nothing to filter.
    }

    if ($view === 'model2') {
        echo $OUTPUT->heading(get_string('model2heading', 'local_stackanalytics'), 3);
        echo html_writer::tag('p', get_string('model2intro', 'local_stackanalytics'));
        echo dashboard_renderer::render_model2_about();
        echo dashboard_renderer::render_model2_table(model2_report::build($courseid, $quizid !== 0 ? $quizid : null));
    } else {
        echo $OUTPUT->heading(get_string('diagnosticsheading', 'local_stackanalytics'), 3);
        echo html_writer::tag('p', get_string('diagnosticsintro', 'local_stackanalytics'));

        if (!concept_dependency_report::is_available()) {
            echo html_writer::tag(
                'p',
                get_string('conceptdependencynote', 'local_stackanalytics'),
                ['class' => 'text-muted small mb-3']
            );
        }

        echo dashboard_renderer::render_diagnostics_section(diagnostics_report::build($courseid, $quizid !== 0 ? $quizid : null));
    }
}

echo dashboard_renderer::render_pdf_form(
    new moodle_url('/local/stackanalytics/pdf.php'),
    $courseid,
    $quizid !== 0 ? $quizid : null
);

echo $OUTPUT->footer();
