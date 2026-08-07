# Changelog

All notable changes to `local_stackanalytics` are documented here.

## CI fixes (post-Phase 8)

- Run #2 failed at plugin install with "maxima_opt_auto creation failed" —
  traced to `question/type/stack/db/install.php`: qtype_stack unconditionally
  tries to build an optimised Maxima image whenever `PHPUNIT_TEST` is true
  (moodle-plugin-ci's own phpunit-init sub-step), and no `maxima` binary was
  installed anywhere in the workflow. Fixed by installing Ubuntu's packaged
  `maxima` before the plugin-install step.
- Run #3, with `maxima` now installed, hit the *same* failure — Ubuntu's
  packaged Maxima isn't a drop-in match for whatever
  `connectorhelper.class.php`'s `create_auto_maxima_image()` expects to
  compile against. Rather than keep guessing at Maxima package
  compatibility, used `install.php`'s own documented escape hatch instead:
  it skips the optimised-image build entirely if
  `QTYPE_STACK_TEST_CONFIG_PLATFORM` is defined as `'none'` before its
  `PHPUNIT_TEST` branch runs. `moodle-plugin-ci add-config` can't set this in
  time — its own source (`AddConfigCommand.php`) confirms it edits
  `config.php` *after* `install` completes, by which point install.php has
  already run. Used PHP's `auto_prepend_file` ini setting instead (wired via
  the `Setup PHP` step's `ini-values`), which guarantees the constant exists
  for every PHP process in the job from the start, including
  moodle-plugin-ci's internal phpunit-init sub-step.

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

## [0.6.0] — Phase 5

- `classes/diagnostics/seed_bias_report.php`: one-way ANOVA of question score
  by STACK random seed (architecture doc §3.4e). The per-attempt seed is read
  from `question_attempt_step_data`'s `'_seed'` name — traced directly to
  `qtype_stack\question::start_attempt()` in the real qtype_stack source,
  since STACK's seed/variant mechanism has no core-Moodle equivalent to infer
  it from. Reports the F-statistic and η² (with Cohen's standard magnitude
  labels) but deliberately not an exact p-value, which would need the
  F-distribution's CDF — a numerical routine not worth risking getting subtly
  wrong without a reference implementation to verify it against, for a
  dashboard that's exploratory by design.
- `classes/diagnostics/bloated_tree_report.php`: per-branch PRT traversal
  coverage on the same `stack_prt_graph` Phase 4 built, reported as a
  maintenance metric (never-reached vs. low-traffic vs. adequate) rather than
  folded into an ML feature, per the doc's own non-ML triage.
- `classes/diagnostics/concept_dependency_report.php`: an explicit stub —
  the doc itself frames concept-dependency Markov-chain mapping as offline/
  future work outside the live pipeline, so this is a placeholder rather than
  a half-built approximation.
- `index.php` + `lib.php`'s `local_stackanalytics_extend_navigation_course()`:
  the Diagnostics Dashboard page and its course navigation link, mirroring
  local_quizanalytics's nav-hook pattern exactly. Deliberately free of
  student-identifying data (§7).
- Pure-math tests for the ANOVA and branch-classification logic.

## [0.7.0] — Phase 6

- `settings.php`: three admin settings replacing hardcoded constants from
  Phases 4-5 — `questionneedsreviewthreshold` (Model 2's proxy-label pass-rate
  cutoff, a real methodological choice flagged as such in its own
  description string, not just a tuning knob), `lowtrafficfloor` (the
  bloated-tree dashboard's never-reached-vs-low-traffic boundary), and
  `helpseekinglookback` (the help-seeking-gap indicator's post-failure
  window). Each class keeps its original constant as the fallback default
  via a new `get_*()` accessor reading `get_config()`, so an unconfigured
  site behaves exactly as it did before this phase.
- `classes/privacy/provider.php`: a `null_provider`, modelled directly on the
  sibling local_quizanalytics plugin's own privacy provider (found already
  installed in the Moodle checkout used throughout this build) — this plugin
  creates no tables of its own and reads everything live from core tables
  already covered by their own privacy providers; the Analytics API's own
  prediction storage is handled generically by core_analytics's privacy
  provider regardless of which plugin registered the model.

## [0.8.0] — Phase 7

- Closed several of the "deferred to Phase 7" test gaps for real, having
  confirmed the exact mechanism: `core_question_generator::create_question('stack',
  'test0', ...)` (question/tests/generator/lib.php) saves a genuine DB-backed
  qtype_stack question from one of `qtype_stack_test_helper::get_test_questions()`'s
  named fixtures (question/type/stack/tests/helper.php) — not an in-memory-only
  object. Added positive-path tests to `student_at_risk_test.php`,
  `stack_question_analyser_test.php`, and `question_needs_review_test.php`
  using this.
- What's still genuinely deferred: `calculate_sample()`-level tests that need
  real *attempt* data (responses, seeds, PRT traversal), not just a question
  existing. These need a full quiz-attempt walkthrough fixture — qtype_stack's
  own `tests/walkthrough_interactive_test.php` is the real, verified mechanism
  to build that from — left for a future pass rather than shipping an
  unverified attempt-simulation test with no live DB available in this
  session to actually run it against.
- `.github/workflows/ci.yml`: a GitHub Actions workflow running
  moodle-plugin-ci (phplint, phpmd, phpcs, phpdoc, validate, savepoints,
  phpunit) against a real Moodle 4.5 + qtype_stack + PostgreSQL environment.
  qtype_stack is checked out separately and wired in via `EXTRA_PLUGINS_DIR`
  (confirmed against moodle-plugin-ci's own `InstallCommand.php` source,
  not guessed) — its real repository (`github.com/maths/moodle-qtype_stack`)
  was confirmed from a CDN URL inside qtype_stack's own `mkdocs.yml`, not
  assumed. Matrix deliberately kept to one PHP/Moodle/DB combination for now;
  `phpcs`/`phpdoc` are `continue-on-error` since this codebase has never been
  run through Code Checker/PHPDoc Checker and may have real, fixable style
  findings on the first run. No Behat step yet — no `.feature` files exist.

## [0.8.0] — Phase 8

- `README.md`: corrected the Model 2 description (no custom analysable, per
  Phase 3's design decision), added a CI badge, and a "Known gaps" section
  naming the two documented indicator simplifications and the deferred
  attempt-data test coverage explicitly, rather than letting them only live
  in scattered docblocks.
- `INSTALL.md`: full placement/upgrade/enable-the-models/configure-thresholds
  walkthrough plus a troubleshooting table, mirroring local_quizanalytics's
  own `INSTALL.md` depth.
- `version.php`: release bumped to `0.8.0` to match this phase's CHANGELOG
  entry.

This closes the phased build. Custom-regression-backend and offline
concept-dependency-mapping work (architecture doc §8's Phase 6/stretch items)
remain explicit future work, not attempted here.
