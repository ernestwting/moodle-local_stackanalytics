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
 * Privacy provider for local_stackanalytics.
 *
 * Modelled directly on the sibling local_quizanalytics plugin's own
 * null_provider (found already installed in the real Moodle checkout used
 * to verify this build's Analytics API contracts) — the same reasoning
 * applies here: this plugin stores no personal data of its own.
 *
 * Every indicator and target in this plugin (classes/local/stack_attempt_reader.php,
 * stack_course_helper.php, stack_prt_graph.php) reads live from mod_quiz, the
 * question engine, grade_grades, and logstore_standard_log — all already
 * covered by their own privacy providers — and never writes any of it back
 * into a table this plugin owns (there is no db/install.xml here). The
 * *predictions and per-sample indicator calculations* the Analytics API
 * itself produces from that data are stored by core_analytics in its own
 * analytics_* tables, keyed generically by model id rather than by the
 * component that declared the target/indicators, and are already handled by
 * \core_analytics\privacy\provider regardless of which plugin registered the
 * model — confirmed by reading that provider's source, which queries the
 * analytics_* tables directly with no per-component branching.
 *
 * @package local_stackanalytics
 * @copyright  2026 Ernest Ting <eting@caltech.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_stackanalytics\privacy;

/**
 * This plugin stores no personal data of its own — see get_reason() and the
 * privacy:metadata language string for the full explanation.
 */
class provider implements \core_privacy\local\metadata\null_provider {

    /**
     * @return string the language string identifier explaining why this plugin stores no data
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
