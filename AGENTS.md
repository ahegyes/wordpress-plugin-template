# wordpress-plugin-template

GitHub template for WordPress plugins built on the DWS v2 framework: per-fork scoped framework + PHP-DI (via `humbug/php-scoper`), with full test + CI scaffolding. The README explains the scoping/collision model.

Forking (step 1: the Fill in scaffold workflow), the placeholder convention (side-by-side WC-vs-generic examples), and the fork-reset checklist live in the [README](README.md); `docs/getting-started.md` walks through how the reference boots and how to extend it. This file covers what an agent needs beyond those: the as-configured inventory and the cache layout.

## Pre-configured

- **Framework deps** (VCS): all seven `wp-framework-*` packages — `bootstrap`, `shared`, `storage`, `core`, `utilities`, `settings`, `woocommerce` — each scoped per-fork
- **Configs** (VCS): `wordpress-configs`
- **PHP-DI** scoped via `wordpress-configs/php/php-scoper/contrib/php-di.inc.php`
- **wp-framework** scoped via `wordpress-configs/php/php-scoper/contrib/wp-framework.inc.php` (auto-detects installed framework packages)
- **Tests**: PHPUnit (Unit + Integration via wp-env's `cli` container) + Playwright E2E
- **Quality**: PHPCS + PHPStan
- **Changelog**: `automattic/jetpack-changelogger` ^6 + `pronamic/changelog-md-to-wordpress-plugin-readme-txt` ^1
- **CI**: `quality.yml` (php-qa + changelog:validate + readme.txt linter) + `tests.yml` (unit + integration matrix + e2e) + `release.yml` (version tag → `reusable-release.yml`: build → artifact test → wp.org deploy; needs a `wp-org-release` environment with `SVN_USERNAME`/`SVN_PASSWORD` secrets) + `audit.yml` (blocking supply-chain audit: full-graph composer, production-deps npm) + `codeql.yml` (`actions` language only) + `workflow-checks.yml` (actionlint + blocking zizmor) + `fill-in-scaffold.yml` (fork-only `workflow_dispatch`: placeholder substitution + version/changelog/metadata reset; refuses to run on the template repo)
- **GitHub** repo files: `dependabot.yml` (composer + npm + github-actions weekly grouped), PR template, bug + feature issue templates
- **wp-env**: `.wp-env.json` (default) + `.wp-env.belowfloor.json` (WP 6.9.4, the below-floor requirements job), both on **port 8811** with `"testsEnvironment": false`. Change the port in both configs + `playwright.config.js` together if 8811 collides locally.
- **WP Packages registry** + `extra.installer-paths` mapping `wordpress-plugin` / `wordpress-theme` types to `vendor/{$vendor}/{$name}/` — relevant when forks add wp.org plugins as dev deps

## Reference engine

The template is a working reference plugin, not bare scaffolding: `src/Plugin.php` implements the framework's `PluginInterface`; `src/Feature/` holds an always-on Feature plus a WooCommerce-gated one; `src/Installer/` an `InstallerInterface`; `src/Component/` the hookable components; `config/container.php` is the PHP-DI composition root. `tests/Unit/PluginBootTest.php` is a mock-WP boot smoke; `tests/Integration/` boots it in real WP + WooCommerce. See `docs/getting-started.md`.

## Cache layout

All generated test/build cache under `tests/.cache/`:
- `tests/.cache/phpunit/` — PHPUnit's `cacheDirectory`
- `tests/.cache/artifacts/` — Playwright outputs (storage states, screenshots, test-results)

`.gitignore` has `tests/.cache` in the custom block above the toptal section.
