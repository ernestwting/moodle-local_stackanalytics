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
 * Declares this plugin's default Analytics API prediction models.
 *
 * Consumed automatically by \core_analytics\manager::update_default_models_for_component(),
 * which core's own upgrade_component_updated() calls for every plugin on
 * install/upgrade (lib/upgradelib.php) — confirmed directly against a real
 * Moodle 4.5 core checkout while planning this phase, along with this
 * array's exact shape (analytics/tests/fixtures/db_analytics_php/*.php).
 * Models are created once per declared target class; changing an already-
 * registered model's indicators/timesplitting here has no further effect —
 * that has to be done through Site Administration > Analytics > Models.
 *
 * Model 2 (question/PRT review) is added to this same array in a later
 * phase, once its custom analysable/analyser/target/indicators exist.
 *
 * `enabled` is deliberately false: this plugin is alpha-stage software and
 * a newly-installed site should not start generating "at risk" predictions
 * — and the insight notifications those trigger — on live student data
 * before an administrator has reviewed the indicator thresholds (Phase 6)
 * and had a chance to train/evaluate the model. Site Administration >
 * Analytics > Models is where it gets enabled once that review is done.
 *
 * @package local_stackanalytics
 * @copyright  2026 Ernest Ting <eting@caltech.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$models = [
    [
        'target' => '\local_stackanalytics\analytics\target\student_at_risk',
        'indicators' => [
            '\local_stackanalytics\analytics\indicator\grade_trajectory',
            '\local_stackanalytics\analytics\indicator\response_latency_anomaly',
            '\local_stackanalytics\analytics\indicator\disengagement_entropy',
            '\local_stackanalytics\analytics\indicator\help_seeking_gap',
            '\local_stackanalytics\analytics\indicator\feedback_revision_distance',
        ],
        'timesplitting' => '\core\analytics\time_splitting\quarters_accum',
        'enabled' => false,
    ],
];
