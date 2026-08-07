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

use local_stackanalytics\analytics\target\question_needs_review;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for the Model 2 question_needs_review target.
 *
 * calculate_sample()'s pass-rate-threshold logic needs a real qtype_stack
 * question fixture to exercise end to end (stack_course_helper::get_stack_slots()
 * filters to qtype 'stack'), so — like the other Model 2 tests — that
 * DB-fixture-backed coverage is deferred to Phase 7.
 *
 * @package local_stackanalytics
 * @copyright  2026 Ernest Ting <eting@caltech.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class question_needs_review_test extends \advanced_testcase {

    public function test_rejects_course_without_stack_activity(): void {
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $analysable = \core_analytics\course::instance($course);
        $target = new question_needs_review();

        $this->assertEquals(
            get_string('errornostackactivity', 'local_stackanalytics'),
            $target->is_valid_analysable($analysable)
        );
    }

    public function test_analyser_class(): void {
        $target = new question_needs_review();
        $this->assertEquals(
            '\local_stackanalytics\analytics\analyser\stack_question_analyser',
            $target->get_analyser_class()
        );
    }

    public function test_can_use_timesplitting_restricted_to_single_range(): void {
        $target = new question_needs_review();

        $this->assertTrue($target->can_use_timesplitting(new \core\analytics\time_splitting\single_range()));
        $this->assertFalse($target->can_use_timesplitting(new \core\analytics\time_splitting\quarters_accum()));
    }
}
