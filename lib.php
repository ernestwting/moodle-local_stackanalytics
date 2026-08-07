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
 * Library functions for local_stackanalytics.
 *
 * @package local_stackanalytics
 * @copyright  2026 Ernest Ting <eting@caltech.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Adds a "STACK Diagnostics" link to a course's own administration
 * navigation, pointing at this plugin's Diagnostics Dashboard (index.php).
 *
 * Mirrors local_quizanalytics_extend_navigation_course() (local_quizanalytics/lib.php)
 * exactly: local_ plugins get this callback invoked automatically for every
 * course's courseadmin navigation node (public/lib/classes/navigation/settings_navigation.php),
 * and a child key not in core's "expected" list gets promoted onto the
 * course's secondary nav bar (or its "More" overflow) automatically.
 *
 * @param navigation_node $navigation the course admin node
 * @param stdClass $course
 * @param context_course $context
 */
function local_stackanalytics_extend_navigation_course($navigation, $course, $context) {
    global $CFG;

    if (!has_capability('local/stackanalytics:view', $context)) {
        return;
    }

    require_once($CFG->dirroot . '/local/stackanalytics/classes/local/stack_course_helper.php');

    if (!\local_stackanalytics\local\stack_course_helper::course_has_stack_activity($course->id)) {
        return;
    }

    $url = new moodle_url('/local/stackanalytics/index.php', ['id' => $course->id]);
    $navigation->add(
        get_string('pluginname', 'local_stackanalytics'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'stackanalyticscourse',
        new pix_icon('i/report', '')
    );
}
