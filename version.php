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
 * Version details for the STACK Analytics plugin.
 *
 * @package    local_stackanalytics
 * @copyright  2026 Ernest Ting <eting@caltech.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_stackanalytics'; // Must match the folder this unzips into: local/stackanalytics
                                              // on a Moodle install. This repo's own root IS that folder's
                                              // contents (no local/stackanalytics/ nesting in the repo itself),
                                              // matching the sibling local_quizanalytics plugin's layout so a
                                              // plain "Download ZIP" of the repo has version.php sitting
                                              // directly inside the single top-level wrapper folder, which is
                                              // what the Moodle plugin uploader requires to detect the
                                              // frankenstyle component, plugin type, and required core version.
$plugin->version   = 2026080700;             // YYYYMMDDXX — bump this every time you push an update.
$plugin->requires  = 2022041900;             // Moodle 4.0.0 — matches the analyser/target/indicator base
                                              // classes this plugin extends (\core_analytics\local\...),
                                              // present since Moodle 3.4 and stable through 4.x/5.x.
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = '0.1.0';

// This plugin builds two Moodle Analytics API prediction models (student risk,
// question/PRT review) and a diagnostics dashboard, all specific to STACK
// questions — it is a no-op without qtype_stack installed, and Model 1's
// analyser (course enrolment) implicitly needs mod_quiz to be the vehicle for
// STACK question attempts. ANY_VERSION for qtype_stack since nothing here calls
// an API added in a particular STACK release; question_attempts/
// question_attempt_steps are read through Moodle's own stable question-engine
// tables, not qtype_stack's PHP API directly (except for PRT tree definitions
// in mdl_qtype_stack_prts, a long-standing, stable table).
$plugin->dependencies = [
    'mod_quiz' => 2022041900,
    'qtype_stack' => ANY_VERSION,
];
