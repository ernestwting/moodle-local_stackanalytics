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
 * Model 1 indicator (d): help-seeking gap.
 *
 * Architecture doc §2.2(d): co-occurrence (or its absence) between repeated
 * STACK failures and access to help resources (forums/glossary/etc.) within
 * a lookback window after each failure. P(resource_access | recent_failure)
 * for the student, compared against the same conditional probability
 * computed course-wide as the baseline. Normalize:
 * 2 * (P_student / P_baseline_capped_at_2) - 1 — read here as: the ratio
 * P_student / P_baseline is capped at 2 before being rescaled from [0, 2] to
 * [-1, 1], so "exactly at the course baseline" maps to 0.
 *
 * @package local_stackanalytics
 * @copyright  2026 Ernest Ting <eting@caltech.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_stackanalytics\analytics\indicator;

use local_stackanalytics\local\stack_attempt_reader;
use local_stackanalytics\local\stack_course_helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Conditional probability of seeking help after a STACK failure, relative to
 * the course-wide baseline rate.
 */
class help_seeking_gap extends \core_analytics\local\indicator\linear {

    /** How long after a failure a resource access still counts as "seeking help for it". */
    const LOOKBACK_SECONDS = HOURSECS;

    /**
     * @return \lang_string
     */
    public static function get_name(): \lang_string {
        return new \lang_string('indicator:helpseekinggap', 'local_stackanalytics');
    }

    /**
     * @return string[]
     */
    public static function required_sample_data() {
        return ['user_enrolments'];
    }

    /**
     * Fraction of $failuretimestamps that are followed, within
     * $lookbackseconds, by at least one timestamp in $accesstimestamps.
     * Pure function over plain timestamp arrays — no DB access — so it can
     * be exercised directly with synthetic data.
     *
     * @param int[] $failuretimestamps
     * @param int[] $accesstimestamps
     * @param int $lookbackseconds
     * @return float|null null if there were no failures to condition on
     */
    public static function conditional_access_rate(array $failuretimestamps, array $accesstimestamps, int $lookbackseconds): ?float {
        if (empty($failuretimestamps)) {
            return null;
        }

        sort($accesstimestamps);
        $matched = 0;
        foreach ($failuretimestamps as $failure) {
            foreach ($accesstimestamps as $access) {
                if ($access >= $failure && $access <= $failure + $lookbackseconds) {
                    $matched++;
                    break;
                }
                if ($access > $failure + $lookbackseconds) {
                    break;
                }
            }
        }
        return $matched / count($failuretimestamps);
    }

    /**
     * @param float $pstudent
     * @param float $pbaseline
     * @return float
     */
    public static function conditional_probability_to_indicator(float $pstudent, float $pbaseline): float {
        if ($pbaseline <= 0.0) {
            return $pstudent > 0.0 ? 1.0 : 0.0;
        }
        $ratio = min($pstudent / $pbaseline, 2.0);
        return max(-1.0, min(1.0, 2.0 * ($ratio / 2.0) - 1.0));
    }

    /**
     * @param int $sampleid a user_enrolments.id
     * @param string $sampleorigin
     * @param int|false $starttime
     * @param int|false $endtime
     * @return float|null
     */
    protected function calculate_sample($sampleid, $sampleorigin, $starttime, $endtime) {
        $enrolment = stack_course_helper::get_enrolment_user_and_course((int) $sampleid);
        if (!$enrolment) {
            return null;
        }
        $courseid = (int) $enrolment->courseid;
        $userid = (int) $enrolment->userid;

        $courseaccess = stack_attempt_reader::get_resource_access_timestamps($courseid, $starttime, $endtime);
        $coursefailures = stack_attempt_reader::get_stack_failure_events($courseid, $starttime, $endtime);

        $failuresbyuser = [];
        foreach ($coursefailures as $failure) {
            $failuresbyuser[$failure->userid][] = (int) $failure->timecreated;
        }

        if (!isset($failuresbyuser[$userid])) {
            return null; // No failures yet for this student — nothing to condition help-seeking on.
        }

        $pstudent = self::conditional_access_rate(
            $failuresbyuser[$userid],
            $courseaccess[$userid] ?? [],
            self::LOOKBACK_SECONDS
        );

        $matchedtotal = 0;
        $failuretotal = 0;
        foreach ($failuresbyuser as $otheruserid => $failures) {
            $rate = self::conditional_access_rate($failures, $courseaccess[$otheruserid] ?? [], self::LOOKBACK_SECONDS);
            if ($rate !== null) {
                $matchedtotal += $rate * count($failures);
                $failuretotal += count($failures);
            }
        }
        $pbaseline = $failuretotal > 0 ? $matchedtotal / $failuretotal : 0.0;

        return self::conditional_probability_to_indicator($pstudent, $pbaseline);
    }
}
