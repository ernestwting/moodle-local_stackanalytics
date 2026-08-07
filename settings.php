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
 * Admin settings for local_stackanalytics.
 *
 * Like local_quizanalytics, a local_ plugin must create its own
 * admin_settingpage and add it to the tree itself — core\plugininfo\
 * local::load_settings() just include()s this file with $ADMIN available.
 *
 * These three are the hardcoded constants from Phases 4-5 worth putting in
 * an administrator's hands rather than leaving as code-only defaults: the
 * Model 2 proxy-label threshold (a real methodological choice, not just a
 * tuning knob), the bloated-tree "low traffic" floor, and the help-seeking
 * lookback window. Read via get_config() with the original class constants
 * kept as fallback defaults, so an unconfigured site behaves exactly as it
 * did before this phase.
 *
 * @package local_stackanalytics
 * @copyright  2026 Ernest Ting <eting@caltech.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_stackanalytics',
        get_string('pluginname', 'local_stackanalytics')
    );
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configtext(
        'local_stackanalytics/questionneedsreviewthreshold',
        get_string('questionneedsreviewthreshold', 'local_stackanalytics'),
        get_string('questionneedsreviewthreshold_desc', 'local_stackanalytics'),
        \local_stackanalytics\analytics\target\question_needs_review::DEFAULT_PASSRATE_THRESHOLD,
        PARAM_FLOAT
    ));

    $settings->add(new admin_setting_configtext(
        'local_stackanalytics/lowtrafficfloor',
        get_string('lowtrafficfloor', 'local_stackanalytics'),
        get_string('lowtrafficfloor_desc', 'local_stackanalytics'),
        \local_stackanalytics\diagnostics\bloated_tree_report::LOW_TRAFFIC_FLOOR,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_stackanalytics/helpseekinglookback',
        get_string('helpseekinglookback', 'local_stackanalytics'),
        get_string('helpseekinglookback_desc', 'local_stackanalytics'),
        \local_stackanalytics\analytics\indicator\help_seeking_gap::LOOKBACK_SECONDS,
        PARAM_INT
    ));
}
