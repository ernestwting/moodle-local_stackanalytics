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

// Diagnostics Dashboard (index.php) — statistical/descriptive, not part of
// the ML pipeline (architecture doc §3.1).
$string['dashboardtitle'] = 'STACK Diagnostics Dashboard';
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
