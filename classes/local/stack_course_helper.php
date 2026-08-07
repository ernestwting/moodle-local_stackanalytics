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
 * Shared "does this course/enrolment involve STACK?" checks used by both
 * models' target classes (is_valid_analysable()) and by indicators that need
 * to resolve a Model 1 sample id (a user_enrolments.id) back to a user/course
 * pair.
 *
 * @package local_stackanalytics
 * @copyright  2026 Ernest Ting <eting@caltech.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_stackanalytics\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Course/enrolment level helpers shared across targets and indicators.
 */
class stack_course_helper {

    /**
     * Does this course contain at least one quiz slot backed by a
     * qtype_stack question?
     *
     * Reuses the exact join pattern local_quizanalytics's data_fetcher uses
     * (quiz -> quiz_slots -> question_references -> question_bank_entries ->
     * question_versions -> question, the Moodle 4.0+ question bank schema),
     * rather than re-deriving it — that query is already proven against a
     * real Moodle checkout for this same "which quizzes have STACK
     * questions" problem.
     *
     * @param int $courseid
     * @return bool
     */
    public static function course_has_stack_activity(int $courseid): bool {
        global $DB;

        $sql = "SELECT 1
                  FROM {quiz} quiz
                  JOIN {course_modules} cm ON cm.instance = quiz.id
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'quiz'
                  JOIN {context} ctx ON ctx.contextlevel = :contextmodule AND ctx.instanceid = cm.id
                  JOIN {quiz_slots} slot ON slot.quizid = quiz.id
                  JOIN {question_references} qr ON qr.usingcontextid = ctx.id
                                                AND qr.component = 'mod_quiz'
                                                AND qr.questionarea = 'slot'
                                                AND qr.itemid = slot.id
                  JOIN {question_bank_entries} qbe ON qbe.id = qr.questionbankentryid
                  JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
                  JOIN {question} q ON q.id = qv.questionid AND q.qtype = 'stack'
                 WHERE quiz.course = :courseid";

        return $DB->record_exists_sql($sql, [
            'contextmodule' => CONTEXT_MODULE,
            'courseid'      => $courseid,
        ]);
    }

    /**
     * Resolves a Model 1 sample id back to the (userid, courseid) pair it
     * represents.
     *
     * Model 1 runs on \core_analytics\course, whose samples are enrolments —
     * confirmed against moodledev.io's Analytics API docs (Section 2.1) as
     * the standard "sample = enrolment" analyser core's own student-risk-style
     * models use; the concrete sample-origin table name ('user_enrolments')
     * should be double-checked against your target Moodle's own
     * \core_analytics\course::get_samples_origin() the first time a Model 1
     * indicator unexpectedly returns null in a real install, since that is
     * the one detail this helper assumes rather than something read from a
     * live core checkout.
     *
     * @param int $enrolmentid a user_enrolments.id
     * @return \stdClass|null object with ->userid and ->courseid, or null if not found
     */
    public static function get_enrolment_user_and_course(int $enrolmentid): ?\stdClass {
        global $DB;

        $sql = "SELECT ue.id, ue.userid, e.courseid
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE ue.id = :id";

        $record = $DB->get_record_sql($sql, ['id' => $enrolmentid]);
        return $record ?: null;
    }
}
