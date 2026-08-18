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
 * English language strings for local_stackanalytics.
 *
 * @package local_stackanalytics
 * @copyright  2026 Ernest Ting <eting@caltech.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Pluginname shows up in Site Administration > Plugins > Local plugins, and
// labels the course-level Diagnostics Dashboard link (see lib.php).
$string['pluginname'] = 'STACK Analytics';
$string['stackanalytics:view'] = 'View STACK Analytics dashboard and predictions';
$string['privacy:metadata'] = 'The STACK Analytics plugin does not store any personal data of its own. Its indicators and targets read finished quiz attempts, question responses, grades, and log events directly from Moodle\'s own database (mod_quiz, the question engine, grade_grades, and logstore_standard_log) at calculation time, all of which are already covered by their own privacy providers. The predictions and per-sample calculations the Analytics API produces from that data are stored by core_analytics in its own tables and handled by core_analytics\'s privacy provider, not by this plugin.';

// Model 1 (student risk) indicator names — shown in Site Administration >
// Analytics > Models when configuring/inspecting a model's indicators.
$string['indicator:gradetrajectory'] = 'STACK grade trajectory';
$string['indicator:responselatencyanomaly'] = 'Anomalous STACK response latency';
$string['indicator:disengagemententropy'] = 'STACK disengagement entropy';
$string['indicator:helpseekinggap'] = 'STACK help-seeking gap';
$string['indicator:feedbackrevisiondistance'] = 'STACK feedback revision distance';

// Model 1 target.
$string['target:studentatrisk'] = 'Student at risk in a STACK-based course';
$string['errornostackactivity'] = 'This course has no STACK (qtype_stack) question activity';

// Model 2 indicators.
$string['indicator:questiondifficultyirt'] = 'STACK question difficulty';
$string['indicator:syntaxerrorrate'] = 'STACK syntax-error rate';
$string['indicator:unreachednoderatio'] = 'STACK PRT unreached-node ratio';
$string['indicator:feedbackineffectiveness'] = 'STACK feedback ineffectiveness';

// Model 2 target.
$string['target:questionneedsreview'] = 'STACK question/PRT needs review';

// The dashboard (index.php) — Model 1, Model 2, and the non-ML Diagnostics
// Dashboard (architecture doc §3.1) all live on this one page, one section
// each.
$string['dashboardtitle'] = 'STACK Analytics Dashboard';
$string['courseselectorlabel'] = 'Course:';
$string['quizselectorlabel'] = 'Quiz:';
$string['allquizzes'] = 'All quizzes';
$string['jumptoquestion'] = 'Jump to:';
$string['seedbiasheading'] = 'Seed bias (one-way ANOVA across random seeds)';
$string['bloatedtreeheading'] = 'PRT branch coverage';
$string['seedgroups'] = 'Distinct seeds observed';
$string['notenoughdata'] = 'Not enough attempt data yet to compute this.';
$string['notavailable'] = 'n/a';
$string['etamagnitude_negligible'] = 'negligible effect';
$string['etamagnitude_small'] = 'small effect';
$string['etamagnitude_medium'] = 'medium effect';
$string['etamagnitude_large'] = 'large effect';
$string['node'] = 'Node';
$string['branch'] = 'Branch';
$string['traversals'] = 'Traversals observed';
$string['coverage'] = 'Coverage';
$string['coverage_unreached'] = 'Never reached — pruning candidate';
$string['coverage_low_traffic'] = 'Low traffic — review before pruning';
$string['coverage_adequate'] = 'Adequately traversed';
$string['unknownquestion'] = 'Unknown question';
$string['unknownquiz'] = 'Unknown quiz';

// Model 1 dashboard section (index.php).
$string['model1heading'] = 'Model 1: Student Risk & Behaviour';
$string['model1intro'] = 'Predicts which students are at risk of not passing the course, from five behavioural signals in their STACK question activity. It\'s recomputed at points through the course, so a warning can fire before the course ends rather than only at the final grade.';
$string['aboutthismodel'] = 'About this model';
$string['model1aboutbody'] = 'What\'s actually predicted (the "target") is simple: will this student\'s final grade fall below the course\'s own pass grade? The five indicators below are what a trained model would use as evidence for that prediction — today, before any model is trained, this page just shows each indicator\'s current reading directly.';
$string['model1aboutfooter'] = 'This model ships disabled, so nothing here is a trained AI prediction yet — only live readings of each signal. An administrator can enable and train it under Site Administration > Analytics > Models, after which trained predictions appear in Moodle\'s own Insights report.';
$string['model1nostudents'] = 'No students are enrolled in this course yet.';
$string['columnstudent'] = 'Student';
$string['columncurrentstatus'] = 'Current status';
$string['gradestatusatrisk'] = 'At risk — {$a->grade}%, below the {$a->gradepass}% needed to pass';
$string['gradestatuspassing'] = 'On track — {$a->grade}%, at or above the {$a->gradepass}% needed to pass';
$string['gradestatusnogradeyet'] = 'No grade recorded yet';
$string['gradestatusnothreshold'] = 'This course has no pass grade set';
$string['band_good'] = 'Good';
$string['band_neutral'] = 'Typical';
$string['band_watch'] = 'Worth a look';
$string['truncatednotice'] = 'Showing the first {$a->shown} of {$a->total}. Use the selectors above to narrow this down.';

$string['model1desc_gradetrajectory'] = 'How this student\'s STACK scores compare to full marks.';
$string['model1sentence_gradetrajectory'] = 'Averaging {$a->meanpercent}% across {$a->attempts} finished attempt(s).';
$string['model1desc_responselatencyanomaly'] = 'Whether this student answers implausibly fast compared to the class — a correlational flag only, never evidence of misconduct on its own.';
$string['model1sentence_responselatencyanomaly'] = 'Averages {$a->userseconds}s between tries, vs. a class average of {$a->cohortseconds}s.';
$string['model1desc_disengagemententropy'] = 'Whether this student\'s attempts look mechanical (very regular timing, questions abandoned) rather than genuine problem-solving.';
$string['model1sentence_disengagemententropy'] = '{$a->abandonedcount} of {$a->attempts} attempt(s) abandoned before completion.';
$string['model1desc_helpseekinggap'] = 'Whether this student seeks help (forums, glossary, other resources) after a wrong answer as often as their classmates do.';
$string['model1sentence_helpseekinggap'] = 'Seeks help after {$a->studentpercent}% of mistakes, vs. a class average of {$a->baselinepercent}%.';
$string['model1desc_feedbackrevisiondistance'] = 'Whether this student meaningfully changes their answer after seeing feedback, or resubmits close to the same thing.';
$string['model1sentence_feedbackrevisiondistance'] = 'Changes their answer by {$a->changepercent}% on average, across {$a->revisions} revision(s).';

// Model 2 dashboard section (index.php).
$string['model2heading'] = 'Model 2: Question & PRT Quality';
$string['model2intro'] = 'Flags STACK questions (and their PRT trees — the branching logic that grades each answer) that may be worth an instructor\'s review, from four signals in how students actually answer them.';
$string['model2aboutbody'] = 'What\'s actually predicted (the "target") is: does this question\'s pass rate fall below a threshold (50% by default, an admin setting)? The four indicators below are the evidence a trained model would use for that — today, before any model is trained, this page just shows each indicator\'s current reading directly. Note: this pass-rate read and the difficulty indicator both ultimately come from the same pass rate, so treat "needs review" and "difficult" as related, not independent, signals.';
$string['model2noquestions'] = 'No STACK questions to show for this selection.';
$string['columnquestion'] = 'Question';
$string['needsreviewyes'] = 'Needs review — {$a->passpercent}% pass rate, below the {$a->thresholdpercent}% threshold';
$string['needsreviewno'] = 'No flag — {$a->passpercent}% pass rate, at or above the {$a->thresholdpercent}% threshold';

$string['model2desc_questiondifficultyirt'] = 'How hard this question is in practice, from its empirical pass rate.';
$string['model2sentence_questiondifficultyirt'] = '{$a->passpercent}% pass rate across {$a->attempts} finished attempt(s).';
$string['model2desc_syntaxerrorrate'] = 'Whether most of this question\'s wrong answers are input/syntax mistakes (an input-format problem) rather than genuine maths errors.';
$string['model2sentence_syntaxerrorrate'] = '{$a->syntaxerrorcount} of {$a->totalfailed} failed attempt(s) were syntax/input errors.';
$string['model2desc_unreachednoderatio'] = 'How much of this question\'s PRT branching logic has never actually been exercised by a real attempt — a pruning candidate if it stays that way.';
$string['model2sentence_unreachednoderatio'] = '{$a->unreachedcount} of {$a->totalbranches} PRT branch(es) never reached.';
$string['model2desc_feedbackineffectiveness'] = 'Whether students who get this wrong tend to improve on their next try more than they would on a fresh question — a rough read on whether the feedback is actually helping.';
$string['model2sentence_feedbackineffectiveness'] = '{$a->improvepercent}% improve after a wrong try, vs. a {$a->baselinepercent}% first-try baseline.';

// Diagnostics Dashboard section (index.php) — statistical/descriptive, not
// part of either model's ML pipeline (architecture doc §3.1).
$string['diagnosticsheading'] = 'Diagnostics Dashboard';
$string['diagnosticsintro'] = 'Statistical reports that don\'t fit either model above — not predictions, just direct calculations from the same attempt data.';
$string['conceptdependencynote'] = 'Concept-dependency mapping (finding which questions\' failures tend to predict failures on others) isn\'t implemented in this plugin yet — the architecture doc frames it as offline sequence-mining work outside a live dashboard page, not something to half-build here. Noted so it doesn\'t just silently not appear.';

// Section jump nav (index.php), shown once all three sections exist.
$string['jumptosection'] = 'Jump to section:';

// Top-of-page framing (index.php) — read before the three sections below.
$string['pageintro'] = '<strong>Model 1</strong> looks at each student\'s behaviour, to flag who might be at risk of not passing. <strong>Model 2</strong> looks at each question\'s marking logic (its PRT), to flag ones that might be worth a teacher\'s review. <strong>Diagnostics Dashboard</strong> is a set of statistical reports that don\'t fit either model — not predictions, just direct calculations from the same attempt data.';
$string['pageintrolivedata'] = 'Both models ship disabled by default, so everything below is a live reading of each signal today, not a trained AI prediction. An administrator can enable and train a model under Site Administration > Analytics > Models — once trained, real predictions appear alongside this page in Moodle\'s own Insights report.';
$string['responsibleusecallout'] = 'A few things worth keeping in mind when reading the flags below: they\'re statistical patterns, not proof of anything — an "anomalous" response time is a prompt to check in with a student, not evidence of misconduct on its own. Small courses will show noisier, less reliable readings simply from having fewer data points to work from. And every number here is about what a student did in this course, not who they are.';

// Admin settings.
$string['questionneedsreviewthreshold'] = 'Question-needs-review pass-rate threshold';
$string['questionneedsreviewthreshold_desc'] = 'A question is labelled "needs review" (Model 2\'s proxy label) when its empirical pass rate falls below this value (0.0-1.0). See the architecture doc\'s §3.3 circularity caveat before lowering this to chase a particular result.';
$string['lowtrafficfloor'] = 'Bloated-tree "low traffic" floor';
$string['lowtrafficfloor_desc'] = 'On the Diagnostics Dashboard, a PRT branch with at least one but fewer than this many observed traversals is reported as "low traffic" (needs a human look) rather than "never reached" (a pruning candidate).';
$string['helpseekinglookback'] = 'Help-seeking lookback window (seconds)';
$string['helpseekinglookback_desc'] = 'How long after a STACK question failure a forum/glossary/resource access still counts as "seeking help for it", for the help-seeking-gap indicator. Defaults to one hour.';
