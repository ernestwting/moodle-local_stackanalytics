# local_stackanalytics

A Moodle **local plugin** that builds two Analytics API prediction models plus a
non-ML diagnostics dashboard, purpose-built for courses using `qtype_stack`
(the STACK/Maxima computer-algebra question type).

## What this is

- **Model 1 — Student risk.** A binary target ("will this student not achieve
  course success?") on Moodle's core course/enrolment analyser, fed by five
  bounded behavioural indicators: grade trajectory, response-latency anomaly,
  disengagement entropy, help-seeking gap, and feedback-revision distance.
- **Model 2 — Question/PRT review.** A binary target ("does this question's
  Potential Response Tree need instructor review?") on a custom
  analysable/analyser (Moodle has no built-in question-level analytics unit),
  fed by IRT difficulty, syntax-error rate, unreached-node ratio, and
  feedback-ineffectiveness indicators.
- **Diagnostics Dashboard.** Seed-bias (ANOVA), bloated-PRT-tree, and
  concept-dependency reports — deliberately kept *outside* the ML pipeline
  since they have no natural ground-truth label (statistical/descriptive
  reports, not trained targets).

The full design rationale — why each detection is a target, an indicator, or a
diagnostic rather than shoehorned into the ML pipeline — lives in
[`docs/moodle-stack-analytics-architecture.md`](docs/moodle-stack-analytics-architecture.md)
(the design document this plugin implements).

## Requirements

- Moodle 4.0 (`2022041900`) or later.
- `mod_quiz` and `qtype_stack` installed (this plugin is a no-op without STACK
  questions to analyze).

## Status

Alpha, under active phased development. See `CHANGELOG.md` for what has
landed so far.

## Install

Drop this repository's contents directly into `local/stackanalytics/` on your
Moodle install (the repo root *is* the plugin root — no extra nesting), then
visit Site Administration to complete the install, or install via the plugin
uploader as a ZIP of this repo.

## License

GPL v3 or later — see `LICENSE`.
