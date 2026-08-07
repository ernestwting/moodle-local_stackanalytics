# Changelog

All notable changes to `local_stackanalytics` are documented here.

## [0.1.0] — Phase 0

- Initial plugin skeleton: `version.php`, `lib.php`, `settings.php`, `db/access.php`,
  `lang/en/local_stackanalytics.php`, `LICENSE`.
- No Analytics API classes yet — this phase only establishes an installable no-op
  plugin (`local/stackanalytics:view` capability, dependency on `mod_quiz` +
  `qtype_stack`) before any target/indicator/analyser code lands.
- Design document (`docs/moodle-stack-analytics-architecture.md`) checked in as
  the source of truth for every subsequent phase.

## [0.2.0] — Phase 1

- `classes/local/stack_course_helper.php` and `classes/local/stack_attempt_reader.php`:
  shared STACK-question detection and question-engine data access reused by all
  Model 1 indicators (and, later, both models' targets).
- Five Model 1 indicators (`classes/analytics/indicator/`): `grade_trajectory`,
  `response_latency_anomaly`, `disengagement_entropy`, `help_seeking_gap`,
  `feedback_revision_distance` — each extends `\core_analytics\local\indicator\linear`
  and implements the exact [-1, 1] normalization formulas from the architecture
  doc's §2.2. Base-class contracts (`calculate_sample($sampleid, $sampleorigin,
  $starttime, $endtime)`, `get_name(): \lang_string`, `required_sample_data()`)
  were verified against a real Moodle 4.5 core checkout rather than assumed.
- Each indicator's normalization math is factored into small public static
  methods, unit-tested directly with synthetic values in `tests/`. DB-fixture-backed
  integration tests for `calculate_sample()` itself are deferred to Phase 7.

## [0.3.0] — Phase 2

- `classes/analytics/target/student_at_risk.php`: Model 1's binary target,
  extending core's own `\core_course\analytics\target\course_gradetopass`
  (grade-to-pass-threshold risk, with all of `course_enrolments`'s enrolment-
  window/course-validity checks) and adding the architecture doc's "STACK
  courses only" restriction via `stack_course_helper::course_has_stack_activity()`.
- `db/analytics.php`: registers Model 1 (target + five Phase 1 indicators +
  `\core\analytics\time_splitting\quarters_accum`, disabled by default pending
  admin review of thresholds) — the schema and its automatic-registration
  mechanism (`\core_analytics\manager::update_default_models_for_component()`,
  called from every plugin install/upgrade) were confirmed against the real
  Moodle 4.5 core checkout, not assumed from documentation.
- `tests/student_at_risk_test.php`: the STACK-activity gate, the inherited
  grade-to-pass validity check taking precedence over it, and a
  `calculate_sample()`/analyser integration test modelled directly on core's
  own `course_gradetopass` test in `course/tests/targets_test.php`.

## [0.4.0] — Phase 3

- `classes/analytics/analyser/stack_question_analyser.php`: Model 2's analyser.
  After verifying against a real Moodle 4.5 core checkout that core's only two
  analyser base classes (`by_course`, `sitewide`) both hardcode their
  analysable, this extends `by_course` and reuses the existing
  `\core_analytics\course` analysable unchanged — mirroring exactly how core's
  own `student_enrolments` analyser works for Model 1 — rather than building a
  from-scratch custom analysable with no precedent to verify against. STACK
  questions become *samples* (one `quiz_slots` row each) within a course,
  which still delivers the course-scoped, memory-safe processing the
  architecture doc calls for.
- `classes/local/stack_course_helper.php`: adds `get_course_stack_slots()` and
  `get_stack_slots()`, reusing the same STACK-question join as Phase 0/1's
  `course_has_stack_activity()` (now factored into a shared private helper).
- `tests/stack_question_analyser_test.php`: confirms non-STACK quiz slots are
  correctly excluded from Model 2's samples. A positive-path test (a real
  qtype_stack question included as a sample) needs qtype_stack's own question
  generator and is deferred to Phase 7, same as `student_at_risk_test.php`'s
  equivalent gap.

## [0.5.0] — Phase 4

- `classes/local/stack_prt_graph.php`: PRT branch enumeration and coverage,
  built on a real finding from the qtype_stack source (question/type/stack in
  the same Moodle 4.5 checkout): `qtype_stack_prt_nodes`' teacher-authored
  `trueanswernote`/`falseanswernote` strings are exactly what
  `qtype_stack\question::summarise_response()` writes into the standard
  `question_attempts.responsesummary` field, so a branch's reach is
  observable by substring-matching its answernote against attempts'
  response summaries — no STACK-internal parsing needed.
- Four Model 2 indicators: `question_difficulty_irt` (logit-scale difficulty
  from empirical pass rate — documented as a deliberate simplification of the
  architecture doc's full 2PL IRT model, since joint a/b/c/θ estimation needs
  a batch calibration step the per-sample `calculate_sample()` API has no
  hook for), `syntax_error_rate` (reuses the standard question-engine
  `'invalid'` state rather than parsing STACK's AnswerTest output),
  `unreached_node_ratio` (on `stack_prt_graph`), and `feedback_ineffectiveness`
  (a documented simplification of the doc's per-branch paired McNemar's test:
  an aggregate log-odds effect size of post-failure improvement vs. first-try
  baseline, since per-branch attribution isn't observable from
  `responsesummary`'s current-value-only history).
- `classes/analytics/target/question_needs_review.php`: Model 2's binary
  target, using the architecture doc's proxy-label option 2 (pass rate below
  a threshold) — the doc's own circularity caveat against
  `question_difficulty_irt` is called out directly in the class docblock.
- `db/analytics.php`: registers Model 2 (target + four new indicators,
  `\core\analytics\time_splitting\single_range` per the doc's §3.5, disabled
  by default like Model 1).
- Pure-math tests for all four new indicators plus `stack_prt_graph`, and an
  `is_valid_analysable`/`can_use_timesplitting` test for the new target.
  `calculate_sample()` integration coverage remains deferred to Phase 7
  pending a qtype_stack question fixture.
