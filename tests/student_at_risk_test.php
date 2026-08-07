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

namespace local_stackanalytics;

use local_stackanalytics\analytics\target\student_at_risk;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for the Model 1 student_at_risk target.
 *
 * The is_valid_analysable() and calculate_sample() test bodies mirror core's
 * own test_core_target_course_gradetopass_analysable() /
 * test_core_target_course_gradetopass_calculate()
 * (course/tests/targets_test.php in a real Moodle checkout) — student_at_risk
 * inherits both methods unchanged from course_gradetopass, so the same
 * fixture pattern applies; what's new here is the STACK-activity gate.
 *
 * A positive-path test (a course that *does* have STACK activity passes the
 * gate) needs a qtype_stack question fixture, which requires qtype_stack's
 * own question generator — left for Phase 7's fixture-backed test pass, once
 * a CI environment with qtype_stack installed (a declared plugin dependency)
 * is available to verify that generator's API against, rather than guessing it.
 *
 * @package local_stackanalytics
 * @copyright  2026 Ernest Ting <eting@caltech.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class student_at_risk_test extends \advanced_testcase {

    public function test_rejects_course_without_stack_activity(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();

        $dg = $this->getDataGenerator();
        $course = $dg->create_course(['startdate' => $now - WEEKSECS, 'enddate' => $now - DAYSECS]);
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $dg->enrol_user($dg->create_user()->id, $course->id, $studentrole->id);

        // Give the course a grade-to-pass so course_gradetopass's own check
        // passes, isolating the assertion to the STACK-activity gate this
        // class adds on top of it.
        $courseitem = \grade_item::fetch_course_item($course->id);
        $courseitem->gradepass = 50;
        $DB->update_record('grade_items', $courseitem);

        $analysable = new \core_analytics\course($course);
        $target = new student_at_risk();

        $this->assertEquals(
            get_string('errornostackactivity', 'local_stackanalytics'),
            $target->is_valid_analysable($analysable)
        );
    }

    public function test_rejects_course_without_gradetopass_before_checking_stack_activity(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();

        $dg = $this->getDataGenerator();
        $course = $dg->create_course(['startdate' => $now - WEEKSECS, 'enddate' => $now - DAYSECS]);
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $dg->enrol_user($dg->create_user()->id, $course->id, $studentrole->id);

        $analysable = new \core_analytics\course($course);
        $target = new student_at_risk();

        // No grade-to-pass set at all — course_gradetopass's own parent
        // check should short-circuit before ours ever runs.
        $this->assertEquals(
            get_string('gradetopassnotset', 'course'),
            $target->is_valid_analysable($analysable)
        );
    }

    public function test_calculate_sample_matches_gradetopass_outcome(): void {
        global $DB;

        $this->resetAfterTest(true);

        $dg = $this->getDataGenerator();
        $course = $dg->create_course();
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);

        $passingstudent = $dg->create_user();
        $failingstudent = $dg->create_user();
        $dg->enrol_user($passingstudent->id, $course->id, $studentrole->id);
        $dg->enrol_user($failingstudent->id, $course->id, $studentrole->id);

        $courseitem = \grade_item::fetch_course_item($course->id);
        $courseitem->update_final_grade($passingstudent->id, 60);
        $courseitem->update_final_grade($failingstudent->id, 30);
        $courseitem->gradepass = 50;
        $DB->update_record('grade_items', $courseitem);

        $expectations = [
            $passingstudent->id => 0,
            $failingstudent->id => 1,
        ];

        $target = new student_at_risk();
        $analyser = new \core\analytics\analyser\student_enrolments(1, $target, [], [], []);
        $analysable = new \core_analytics\course($course);

        $analyserclass = new \ReflectionClass(\core\analytics\analyser\student_enrolments::class);
        $getallsamples = $analyserclass->getMethod('get_all_samples');
        [$sampleids, $samplesdata] = $getallsamples->invoke($analyser, $analysable);
        $target->add_sample_data($samplesdata);

        $targetclass = new \ReflectionClass(student_at_risk::class);
        $calculatesample = $targetclass->getMethod('calculate_sample');

        foreach ($sampleids as $sampleid => $key) {
            $userid = $samplesdata[$key]['user']->id;
            $this->assertEquals($expectations[$userid], $calculatesample->invoke($target, $sampleid, $analysable));
        }
    }
}
