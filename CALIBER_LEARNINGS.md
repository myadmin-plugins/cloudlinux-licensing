# Caliber Learnings

Accumulated patterns and anti-patterns from development sessions.
Auto-managed by [caliber](https://github.com/caliber-ai-org/ai-setup) — do not edit manually.

- **[gotcha:project]** Skill files are stored at `.claude/skills/{name}/SKILL.md` (directory per skill), NOT as `.claude/skills/{name}.md` flat files. Attempting to read or write a flat `.md` path will silently fail — always use the subdirectory pattern.
- **[gotcha:project]** `tests/PluginTest.php` has hard-coded count assertions: `testGetHooksCount()` (currently 10 hooks), `testPublicMethodCount()` (currently 11 public methods), `testStaticPropertyCount()` (currently 5 static props). When adding hooks or methods to `src/Plugin.php`, always update these counts or tests will fail with misleading count-mismatch errors.
- **[pattern:project]** Caliber's reference-validation scoring flags `vendor/bin/phpunit` and other `vendor/` paths as invalid because they are not in the project file tree. When writing `.claude/skills/*/SKILL.md` content, reference commands as plain prose or code blocks without backtick-quoting `vendor/` paths as if they were project file references.
- **[convention:project]** When adding a new hook to `getHooks()` in `src/Plugin.php`, two test updates are always required: (1) add the new key to the `$expectedKeys` array in `testGetHooksReturnsExpectedKeys()`, and (2) increment the count in `testGetHooksCount()`.
