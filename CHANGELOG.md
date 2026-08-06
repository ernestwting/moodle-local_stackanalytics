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
