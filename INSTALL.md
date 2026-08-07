# Installing STACK Analytics

This is a single Moodle plugin — installing `local_stackanalytics` is the
whole install. It builds two Moodle Analytics API prediction models and a
diagnostics dashboard entirely in-process; there's no separate service to
deploy and nothing here ever talks to the public internet.

## Prerequisites

- Admin access to the Moodle site, plus shell/SFTP access to the Moodle
  codebase (not just the web UI).
- Moodle 4.0+ (`version.php`'s `requires`; raise or lower it if your target
  Moodle needs a different floor).
- `mod_quiz` (core, always present) and `qtype_stack` installed — this
  plugin is a no-op without STACK questions to analyze.

## 1. Place the files

This repository's own root **is** the plugin (`version.php`, `classes/`,
`lang/`, etc. sit directly here), matching the sibling `local_quizanalytics`
plugin's layout — a plain GitHub "Download ZIP" of this repo can go straight
into Moodle's plugin uploader.

**Option A — Moodle's own plugin installer:** Site administration → Plugins
→ Install plugins → upload a zip of this repository's contents.

**Option B — shell/SFTP:**

```bash
# From a clone/extract of this repo:
cp -r . <moodleroot>/local/stackanalytics
chown -R www-data:www-data <moodleroot>/local/stackanalytics
# (use whatever user your web server actually runs as)
```

The folder Moodle sees it at must be exactly
`<moodleroot>/local/stackanalytics` — Moodle derives the component name
`local_stackanalytics` from that path.

## 2. Run the Moodle upgrade

Log in as an admin and visit **Site administration** (or
`<yoursite>/admin/index.php` directly if the upgrade screen doesn't appear
on its own). This single step:

- Registers the `local/stackanalytics:view` capability (`db/access.php`).
- Registers both prediction models — **Student at risk in a STACK-based
  course** and **STACK question/PRT needs review** — from `db/analytics.php`,
  via `\core_analytics\manager::update_default_models_for_component()`. Both
  are created **disabled**; see step 3.

## 3. Review and enable the models

**Site administration → Analytics → Models.** Both models this plugin
registers appear here, disabled by default (deliberately — see
`db/analytics.php`'s own comment for why). Before enabling either:

- Review the thresholds in **Site administration → Plugins → Local plugins
  → STACK Analytics** (see step 4).
- Note the proxy-label circularity caveat on "STACK question/PRT needs
  review" (architecture doc §3.3) before relying on its predictions.
- A model needs training data before it predicts anything — use the
  **Evaluate**/**Get predictions** actions on the model's own page once
  enabled.

## 4. Configure thresholds

**Site administration → Plugins → Local plugins → STACK Analytics:**

- **Question-needs-review pass-rate threshold** (default 0.5) — the Model 2
  proxy label. A question's empirical pass rate below this is labelled
  "needs review".
- **Bloated-tree "low traffic" floor** (default 2) — on the Diagnostics
  Dashboard, a PRT branch below this many observed traversals is "low
  traffic" rather than "never reached".
- **Help-seeking lookback window** (default 3600 seconds) — how long after a
  STACK failure a forum/glossary/resource access still counts as seeking
  help for it.

## 5. Test the Diagnostics Dashboard

1. Go to a course with **at least one quiz containing a STACK question**
   (added directly to a slot), with at least one attempt.
2. Look at the course's secondary navigation bar for a **STACK Analytics**
   entry (check inside **More** too if the bar is full).
3. You should see one section per STACK question slot in the course, each
   with a seed-bias ANOVA table and a PRT branch-coverage table.
4. Confirm the page is correctly **hidden** on a course with no STACK
   activity, and that a student account gets Moodle's standard
   permission-denied error navigating to
   `local/stackanalytics/index.php?id=<courseid>` directly.

## 6. Test the prediction models

Once a model is enabled (step 3) and has run at least once (Moodle's own
scheduled task, `\core\task\analytics_process_models`, or the model's own
**Get predictions now** action), check **Site administration → Analytics →
Insights** for generated predictions. If nothing appears, confirm the target
course has enough data — `is_valid_analysable()`/`is_valid_sample()` on both
targets reject courses/samples without enough to work with rather than
producing a misleading prediction.

---

## Troubleshooting quick-reference

| Symptom | Likely cause |
|---|---|
| "STACK Analytics" doesn't appear in course navigation | Plugin not installed, or the course has no STACK question in any quiz slot (`stack_course_helper::course_has_stack_activity()` gates this) |
| A model shows 0 samples after training/prediction | `is_valid_analysable()` or `is_valid_sample()` rejected the course/sample — check the model's own log in Site administration → Analytics → Models → (model) → Log for the specific reason string |
| Seed-bias table says "Not enough attempt data yet" | Fewer than 2 distinct STACK seeds have recorded attempts for that quiz slot yet |
| PRT branch-coverage table is empty for a question | That question's PRT nodes have no non-blank `trueanswernote`/`falseanswernote` set — coverage can't be observed without one (see `stack_prt_graph.php`'s docblock) |
| "STACK question/PRT needs review" predictions look circular/self-fulfilling | Expected, documented limitation — see the architecture doc's §3.3 and this target's own class docblock |
| CI fails on Code Checker / PHPDoc Checker but PHPUnit passes | Those two steps are `continue-on-error` for now (first-ever run against this codebase) — check the annotated findings and fix them incrementally, they don't block the build |
