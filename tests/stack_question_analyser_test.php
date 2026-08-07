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

use local_stackanalytics\analytics\analyser\stack_question_analyser;
use local_stackanalytics\analytics\target\student_at_risk;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for the Model 2 stack_question_analyser.
 *
 * The quiz/question fixture setup mirrors mod_quiz's own
 * question_helper_test_trait::create_test_quiz() /
 * add_two_regular_questions() (mod/quiz/tests/classes/), read directly from
 * a real Moodle 4.5 core checkout to confirm the exact generator API
 * (quiz_add_quiz_question(), the 'core_question' plugin generator) rather
 * than guessing it.
 *
 * A positive-path test (a course whose quiz slot *is* a qtype_stack question
 * gets included as a sample) needs qtype_stack's own question generator,
 * which — like student_at_risk_test.php's equivalent gap — is deferred to
 * Phase 7 rather than guessed, since qtype_stack is a separate plugin not
 * present in this checkout to verify its generator against.
 *
 * @package local_stackanalytics
 * @copyright  2026 Ernest Ting <eting@caltech.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class stack_question_analyser_test extends \advanced_testcase {

    public function test_get_all_samples_excludes_non_stack_questions(): void {
        $this->resetAfterTest(true);

        $dg = $this->getDataGenerator();
        $course = $dg->create_course();

        $quizgenerator = $dg->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance(['course' => $course->id]);

        $questiongenerator = $dg->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();
        $question = $questiongenerator->create_question('shortanswer', null, ['category' => $cat->id]);
        quiz_add_quiz_question($question->id, $quiz);

        // Model 2's own target (question_needs_review) doesn't exist until
        // Phase 4; student_at_risk is used here purely as a concrete
        // \core_analytics\local\target\base to satisfy the analyser
        // constructor's type hint — get_all_samples()/get_samples_origin()
        // never call anything on it. Swap this for question_needs_review
        // once Phase 4 lands.
        $target = new student_at_risk();
        $analyser = new stack_question_analyser(1, $target, [], [], []);
        $analysable = \core_analytics\course::instance($course);

        [$sampleids, $samplesdata] = $analyser->get_all_samples($analysable);

        $this->assertEmpty($sampleids);
        $this->assertEmpty($samplesdata);
    }

    public function test_get_samples_origin(): void {
        // Model 2's own target (question_needs_review) doesn't exist until
        // Phase 4; student_at_risk is used here purely as a concrete
        // \core_analytics\local\target\base to satisfy the analyser
        // constructor's type hint — get_all_samples()/get_samples_origin()
        // never call anything on it. Swap this for question_needs_review
        // once Phase 4 lands.
        $target = new student_at_risk();
        $analyser = new stack_question_analyser(1, $target, [], [], []);
        $this->assertEquals('quiz_slots', $analyser->get_samples_origin());
    }
}
