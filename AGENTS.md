# wordpress-plugin-template

GitHub template for WordPress plugins built on the DWS v2 framework. Per-fork scoped framework + PHP-DI (via `humbug/php-scoper`), full test + CI scaffolding.

Forks: "Use this template" on GitHub → manual placeholder substitution per the README's table → `composer packages-install` populates scoped `dependencies/`.

The README ships the full placeholder convention (with two real v1 plugins as side-by-side examples for WC vs generic) + the fork-reset checklist (version → 1.0.0, CHANGELOG wipe, etc.).

## Pre-configured

- **Framework deps** (VCS): all seven `wp-framework-*` packages — `bootstrap`, `shared`, `storage`, `core`, `utilities`, `settings`, `woocommerce` — each scoped per-fork
- **Configs** (VCS): `wordpress-configs`
- **PHP-DI** scoped via `wordpress-configs/php/php-scoper/contrib/php-di.inc.php`
- **wp-framework** scoped via `wordpress-configs/php/php-scoper/contrib/wp-framework.inc.php` (auto-detects installed framework packages)
- **Tests**: PHPUnit (Unit + Integration via wp-env's `cli` container) + Playwright E2E
- **Quality**: PHPCS + PHPStan
- **Changelog**: `automattic/jetpack-changelogger` ^6 + `pronamic/changelog-md-to-wordpress-plugin-readme-txt` ^1
- **CI**: `quality.yml` (php-qa + changelog:validate + readme.txt linter) + `tests.yml` (unit + integration matrix + e2e) + `codeql.yml` (`actions` language only)
- **GitHub** repo files: `dependabot.yml` (composer + npm + github-actions weekly grouped), PR template, bug + feature issue templates
- **wp-env**: `.wp-env.tests.json` single-config on **port 8811** with `"testsEnvironment": false` (per workspace port scheme)
- **WP Packages registry** + `extra.installer-paths` mapping `wordpress-plugin` / `wordpress-theme` types to `vendor/{$vendor}/{$name}/` — relevant when forks add wp.org plugins as dev deps

## Reference engine

The template is a working rev-2 reference plugin, not bare scaffolding: `src/Plugin.php` implements the framework's `PluginInterface`; `src/Feature/` holds an always-on Feature plus a WooCommerce-gated one; `src/Installer/` an `InstallerInterface`; `src/Component/` the hookable components; `config/container.php` is the PHP-DI composition root. `tests/Unit/PluginBootTest.php` is a mock-WP boot smoke; `tests/Integration/` boots it in real WP + WooCommerce. See `docs/getting-started.md`.

## Cache layout

All generated test/build cache under `tests/.cache/`:
- `tests/.cache/phpunit/` — PHPUnit's `cacheDirectory`
- `tests/.cache/artifacts/` — Playwright outputs (storage states, screenshots, test-results)

`.gitignore` has `tests/.cache` in the custom block above the toptal section.

## Deferred

- **`release.yml`** — calls `wordpress-configs/.github/workflows/reusable-release.yml@trunk` for wp.org publish from the plugin's tag → zip → wp.org upload. Per project memory, blocking for shipping plugin v1.0.0 to wp.org.
- **`fill-in-scaffold.yml`** — `workflow_dispatch` with display-name / slug / namespace inputs auto-substitutes placeholders. Per project memory's spec, must also handle wp-env port replacement + version reset + CHANGELOG/changelog wipe + readme.txt metadata reset.
