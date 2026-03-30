# MyAdmin CloudLinux Licensing Plugin

PHP plugin for MyAdmin that provisions CloudLinux, KernelCare, and Imunify360 licenses via the CloudLinux XML-RPC API. Installs as a Composer package of type `myadmin-plugin`.

## Commands

```bash
composer install                        # install deps incl. detain/cloudlinux-licensing
composer test                           # run all tests
composer coverage                       # coverage report
php bin/cloudlinux_check.php <ip>       # check if IP is licensed
php bin/cloudlinux_list.php             # list all licenses
php bin/cloudlinux_types.php            # list license type IDs
```

## Architecture

**Entry**: `src/Plugin.php` — registers all Symfony EventDispatcher hooks via `Plugin::getHooks()`
**API helpers**: `src/cloudlinux.inc.php` — `get_cloudlinux_licenses()`, `deactivate_cloudlinux($ip, $type)`, `deactivate_kcare($ip)`
**Admin UI**: `src/cloudlinux_licenses_list.php` — `cloudlinux_licenses_list()` renders `TFTable`, admin-only
**CLI tools**: `bin/` — standalone scripts requiring `include/functions.inc.php` and `CLOUDLINUX_LOGIN`/`CLOUDLINUX_KEY` constants
**Tests**: `tests/` — `PluginTest.php` (reflection-based), `SourceFileAnalysisTest.php` (file content assertions), `ComposerConfigTest.php`
**Bootstrap**: `tests/bootstrap.php` — searches up the directory tree for the Composer autoloader
**CI/CD**: `.github/` — GitHub Actions workflows for automated testing and deployment pipelines
**IDE Config**: `.idea/` — PhpStorm project settings including `inspectionProfiles/`, `deployment.xml`, and `encodings.xml`

## Plugin Hook Pattern

`src/Plugin.php::getHooks()` returns event-to-handler map. All handlers are `static` methods accepting `GenericEvent $event`:

```php
public static function getHooks()
{
    return [
        'plugin.install'           => [__CLASS__, 'getInstall'],
        self::$module.'.activate'  => [__CLASS__, 'getActivate'],
        self::$module.'.change_ip' => [__CLASS__, 'getChangeIp'],
        'function.requirements'    => [__CLASS__, 'getRequirements'],
    ];
}
```

Each handler MUST call `$event->stopPropagation()` after handling. Check `$event['category'] == get_service_define('CLOUDLINUX')` before acting.

## CloudLinux API Usage

```php
use Detain\Cloudlinux\Cloudlinux;

$cl = new Cloudlinux(CLOUDLINUX_LOGIN, CLOUDLINUX_KEY);
$cl->isLicensed($ip, true);          // check if IP has license; true = return array
$cl->license($ip, $typeId);          // activate license (type IDs in bin/cloudlinux_types.txt)
$cl->remove($ip, $typeId);           // deactivate license
$cl->licenseList();                  // list all licenses
$cl->kcareList();                    // KernelCare-specific list
$cl->imunifyList();                  // Imunify-specific list
```

License type IDs: `1`=CloudLinux, `16`=KernelCare, `40`=ImunityAV+, `41-43,49`=Imunify360 tiers (see `bin/cloudlinux_types.txt`).

## Logging & Error Handling

```php
myadmin_log(self::$module, 'info', 'Message', __LINE__, __FILE__, self::$module, $serviceClass->getId());
request_log(self::$module, $custid, __FUNCTION__, 'cloudlinux', 'license', [$ip, $type], $response, $id);
```

On deactivation failure, send email via `TFSmarty` + `\MyAdmin\Mail::multiMail()` using template `email/client/client_email.tpl`.

## Required Constants

```php
define('CLOUDLINUX_LOGIN', 'your-login');
define('CLOUDLINUX_KEY', 'your-api-key');
define('OUTOFSTOCK_LICENSES_CLOUDLINUX', 0); // 0=in stock, 1=out of stock
```

## Conventions

- Namespace: `Detain\MyAdminCloudlinux\` → `src/`; tests: `Detain\MyAdminCloudlinux\Tests\` → `tests/`
- All `Plugin` event methods: `public static`, typed `GenericEvent $event`, no return type
- Tabs for indentation (see `.scrutinizer.yml` coding style)
- Commit messages: lowercase, descriptive (`fix kcare deactivation`, `add imunify type`)
- Never commit `CLOUDLINUX_LOGIN`/`CLOUDLINUX_KEY` values
- Run `caliber refresh` before commits; stage any modified doc files

<!-- caliber:managed:pre-commit -->
## Before Committing

**IMPORTANT:** Before every git commit, you MUST ensure Caliber syncs agent configs with the latest code changes.

First, check if the pre-commit hook is already installed:
```bash
grep -q "caliber" .git/hooks/pre-commit 2>/dev/null && echo "hook-active" || echo "no-hook"
```

- If **hook-active**: the hook handles sync automatically — just commit normally. Tell the user: "Caliber will sync your agent configs automatically via the pre-commit hook."
- If **no-hook**: run Caliber manually before committing:
  1. Tell the user: "Caliber: Syncing agent configs with your latest changes..."
  2. Run: `caliber refresh && git add CLAUDE.md .claude/ .cursor/ .cursorrules .github/copilot-instructions.md .github/instructions/ AGENTS.md CALIBER_LEARNINGS.md .agents/ .opencode/ 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

If `caliber` is not found, tell the user: "This project uses Caliber for agent config sync. Run /setup-caliber to get set up."
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->

<!-- caliber:managed:sync -->
## Context Sync

This project uses [Caliber](https://github.com/caliber-ai-org/ai-setup) to keep AI agent configs in sync across Claude Code, Cursor, Copilot, and Codex.
Configs update automatically before each commit via `caliber refresh`.
If the pre-commit hook is not set up, run `/setup-caliber` to configure everything automatically.
<!-- /caliber:managed:sync -->
