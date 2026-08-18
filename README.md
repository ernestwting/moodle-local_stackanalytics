# local_stackanalytics

[![Moodle Plugin CI](https://github.com/ernestwting/moodle-local_stackanalytics/actions/workflows/ci.yml/badge.svg)](https://github.com/ernestwting/moodle-local_stackanalytics/actions/workflows/ci.yml)

A Moodle **local plugin** that builds two Analytics API prediction models plus a
non-ML diagnostics dashboard, purpose-built for courses using `qtype_stack`
(the STACK/Maxima computer-algebra question type).

## What this is

- **Model 1 — Student risk.** A binary target ("will this student not achieve
  course success?") on Moodle's core course/enrolment analyser
  (`\core\analytics\analyser\student_enrolments`), fed by five bounded
  behavioural indicators: grade trajectory, response-latency anomaly,
  disengagement entropy, help-seeking gap, and feedback-revision distance.
- **Model 2 — Question/PRT review.** A binary target ("does this question's
  Potential Response Tree need instructor review?"). Moodle has no built-in
  question-level analytics unit, but its two shipped analyser base classes
  (`by_course`/`sitewide`) both hardcode their analysable — so rather than
  building an unprecedented fully-custom analysable, this plugin's analyser
  extends `by_course` and reuses the existing course analysable, with each
  STACK question-in-a-quiz as a *sample* within it (exactly how core's own
  `student_enrolments` analyser works for Model 1). Fed by IRT-inspired
  difficulty, syntax-error rate, unreached-node ratio, and feedback-
  ineffectiveness indicators.
- **Diagnostics Dashboard.** Seed-bias (one-way ANOVA) and PRT branch-coverage
  reports — deliberately kept *outside* the ML pipeline since they have no
  natural ground-truth label (statistical/descriptive reports, not trained
  targets). Concept-dependency mapping is stubbed as explicit future work.

All three show up on one page — the **STACK Analytics Dashboard**
(`index.php`, reached from a course's secondary navigation): a course
selector, a "View:" switcher that shows exactly one section at a time, and
a plain-language explanation of what each section means. Model 1/2 are each
a table (a row per student / a row per question) showing each indicator's
*live reading* today — both models ship disabled by default (alpha stage),
so nothing here is a trained AI prediction until an administrator enables
and trains one under Site Administration > Analytics > Models. The
Diagnostics section is a collapsed-by-default list, one question per row,
expandable for the full statistics. A "Download PDF" form at the bottom of
every view re-derives whichever sections are ticked as a landscape PDF
report, via Moodle core's own bundled TCPDF.

The full design rationale — why each detection is a target, an indicator, or a
diagnostic rather than shoehorned into the ML pipeline — lives in
[`docs/moodle-stack-analytics-architecture.md`](docs/moodle-stack-analytics-architecture.md)
(the design document this plugin implements). Where this implementation had
to depart from that document — because a Moodle API constraint made the
literal spec impossible, not because of a shortcut — is called out in both
the relevant class's docblock and `CHANGELOG.md`.

## Requirements

- Moodle 4.0 (`2022041900`) or later.
- `mod_quiz` (core) and `qtype_stack` installed — this plugin is a no-op
  without STACK questions to analyze.

## Status

Alpha, under active phased development — see `CHANGELOG.md` for what has
landed so far, phase by phase. Both models ship **disabled** by default;
review `INSTALL.md` before enabling either on a live site.

Known gaps, tracked rather than hidden:
- Two indicators are documented simplifications of the architecture doc's
  literal spec (`question_difficulty_irt`'s classical-test-theory proxy
  instead of a jointly-fitted 2PL IRT model; `feedback_ineffectiveness`'s
  aggregate log-odds effect size instead of a per-branch paired McNemar's
  test) — both because the full version needs data or a batch step the
  Analytics API's per-sample indicator model doesn't provide, not because it
  was skipped for convenience. Details in each class's docblock.
- Some PHPUnit coverage needing real STACK *attempt* data (as opposed to just
  a STACK question existing) is deferred — see `CHANGELOG.md`'s Phase 7 entry
  for exactly what's missing and the real mechanism to build it with.

## Install

See `INSTALL.md` for the full walkthrough (placement, running the upgrade,
enabling the models, configuring thresholds, and a troubleshooting table).
Short version: this repository's own root *is* the plugin root (no extra
nesting) — drop it into `local/stackanalytics/` on your Moodle install, or
upload a ZIP of this repo through Site administration's plugin installer.

## License

GPL v3 or later — see `LICENSE`.
