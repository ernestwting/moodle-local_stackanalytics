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
use local_stackanalytics\analytics\target\question_needs_review;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for the Model 2 stack_question_analyser.
 *
 * The quiz/question fixture setup mirrors mod_quiz's own
 * question_helper_test_trait::create_test_quiz() /
 * add_two_regular_questions() (mod/quiz/tests/classes/); the positive-path
 * STACK question uses $questiongenerator->create_question('stack', 'test0', ...),
 * one of qtype_stack_test_helper::get_test_questions()'s named fixtures
 * (question/type/stack/tests/helper.php) — confirmed, not guessed, that
 * core_question_generator::create_question() saves it as a real DB-backed
 * question (question/tests/generator/lib.php).
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

        $target = new question_needs_review();
        $analyser = new stack_question_analyser(1, $target, [], [], []);
        $analysable = \core_analytics\course::instance($course);

        [$sampleids, $samplesdata] = $analyser->get_all_samples($analysable);

        $this->assertEmpty($sampleids);
        $this->assertEmpty($samplesdata);
    }

    public function test_get_all_samples_includes_a_real_stack_question(): void {
        $this->resetAfterTest(true);

        $dg = $this->getDataGenerator();
        $course = $dg->create_course();

        $quizgenerator = $dg->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance(['course' => $course->id]);

        $questiongenerator = $dg->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();
        $question = $questiongenerator->create_question('stack', 'test0', ['category' => $cat->id]);
        quiz_add_quiz_question($question->id, $quiz);

        $target = new question_needs_review();
        $analyser = new stack_question_analyser(1, $target, [], [], []);
        $analysable = \core_analytics\course::instance($course);

        [$sampleids, $samplesdata] = $analyser->get_all_samples($analysable);

        $this->assertCount(1, $sampleids);
        $slotid = reset($sampleids);
        $this->assertEquals($question->id, $samplesdata[$slotid]['quiz_slots']->questionid);
        $this->assertEquals($quiz->id, $samplesdata[$slotid]['quiz_slots']->quizid);

        $this->assertEquals(
            \context_course::instance($course->id)->id,
            $analyser->sample_access_context($slotid)->id
        );
        $this->assertEquals($course->id, $analyser->get_sample_analysable($slotid)->get_id());
    }

    public function test_get_samples_origin(): void {
        $target = new question_needs_review();
        $analyser = new stack_question_analyser(1, $target, [], [], []);
        $this->assertEquals('quiz_slots', $analyser->get_samples_origin());
    }
}
