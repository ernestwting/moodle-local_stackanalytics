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
 * Empty for now (Phase 0 skeleton): the indicator/target threshold settings
 * (anomaly z-score cutoff, IRT minimum sample size, seed-bias ANOVA alpha,
 * pass-rate threshold for the Model 2 proxy label) land here once those
 * classes exist, so the settings page has something to configure.
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
}
