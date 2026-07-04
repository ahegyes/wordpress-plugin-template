# DWS Plugin Template

Scaffold for WordPress plugins on the DWS framework. Each fork ships its own scoped copy of the framework + PHP-DI (via humbug/php-scoper) so two plugins can't collide on framework versions.

**New here?** Start with [docs/getting-started.md](docs/getting-started.md) — how the reference boots and how to extend it.

## Architecture

```
dws-plugin-template/
├── dws-plugin-template.php   # WP plugin entry: guards → check_requirements → register_lifecycle_hooks → boot
├── functions.php             # Global functions: instance accessor + plugins_loaded boot callback
├── uninstall.php             # WP-invoked cleanup → Installer::uninstall()
├── src/
│   ├── Plugin.php            # Singleton implementing PluginInterface: container + kernel + lifecycle
│   ├── Feature/              # GenericFeature (always-on) + WooCommerceFeature (WC-gated)
│   ├── Installer/            # Installer: stored-version I/O, install/update/uninstall
│   ├── Component/            # AdminNotice + ExampleSettings (HookableInterface components)
│   └── Settings/             # ExampleWCSettingsPage (DescriptorBackedWCSettingsPage subclass)
├── config/
│   └── container.php         # PHP-DI definitions (the composition root)
├── docs/
│   └── getting-started.md    # How the reference boots + how to extend it
├── tests/
│   ├── bootstrap.php         # Composer autoload + (in wp-env) WP load
│   ├── Unit/                 # Pure PHP, no Docker (incl. the mock-WP boot smoke)
│   ├── Integration/          # wp-env Docker, real WP loaded
│   └── e2e/                  # Playwright + @wordpress/e2e-test-utils-playwright
├── scoper.inc.php            # php-scoper config — extends wordpress-configs base
└── dependencies/             # Generated: scoped framework + PHP-DI (gitignored)
```

## Placeholder Convention

Replace these throughout the codebase when forking. Two real v1 plugins shown as examples — pick the column matching your fork:

| Placeholder                                    | WC plugin example                            | Generic WP plugin example            |
|------------------------------------------------|----------------------------------------------|--------------------------------------|
| `dws-plugin-template` (slug + text-domain)     | `locked-payment-methods-for-woocommerce`     | `internal-comments`                  |
| `DWS_PLUGIN_TEMPLATE` (constants)              | `DWS_LPMWC`                                  | `DWS_IC`                             |
| `dws_plugin_template_` (functions)             | `dws_lpmwc_`                                 | `dws_ic_`                            |
| `dws_plugin_template` (slug / option-key base) | `dws_lpmwc`                                  | `dws_ic`                             |
| `DeepWebSolutions\PluginTemplate\` (namespace) | `DeepWebSolutions\LockedPaymentMethods\`     | `DeepWebSolutions\InternalComments\` |
| `DWS Plugin Template` (display)                | `Locked Payment Methods for WooCommerce`     | `Internal Comments`                  |
| `8811` (wp-env port)                           | any free port (avoid 8888/8889)              | any free port (avoid 8888/8889)      |

The two underscore forms are distinct. `dws_plugin_template` (bare) is the WooCommerce settings-page slug, the installer's `STORE_KEY`, and the base every `{slug}_{field}` option row is built from; `dws_plugin_template_` (trailing) is the function and option-key prefix. Replacing the bare form as a plain substring covers both — the prefix is just the base plus `_` — so renaming only the trailing form leaves the settings options written under the old slug, orphaned on uninstall.

**WC plugins follow extra conventions** (per v1 + WC trademark policy):
- **Display**: `X for WooCommerce` (WC trademark requirement). NOT "WooCommerce X" or "WC: X".
- **Slug** + **text-domain**: end in `-for-woocommerce`.
- **Constant + function abbreviations** end in `WC` (`LPMWC` = Locked Payment Methods + WooCommerce). Keeps `DWS_` prefix.
- **Composer package name** convention is `deep-web-solutions/wc-<short-name>` (e.g., `wc-locked-payment-methods`).

**Generic WP plugins** drop the `WC` everything: plain slug, plain abbreviation, `wp-` composer prefix (e.g., `deep-web-solutions/wp-internal-comments`).

**Both variants share v2's flat namespace** — `DeepWebSolutions\PluginName\` only. v1's `\WC_Plugins\` and `\Plugins\` middle segments are dropped in v2.

The port appears in `.wp-env.json` and `.wp-env.belowfloor.json` (`"port"`), `playwright.config.js` (`WP_BASE_URL`), and this README's "Open localhost" line below — keep them in sync.

Scoped deps land under `\Scoped\` inside the plugin's namespace (`DeepWebSolutions\PluginTemplate\Scoped\DI\...`). The `\Scoped\` segment is invariant; renaming the namespace placeholder above is enough.

## Fork Reset Checklist

Template tracks its own version + history; forks start fresh:

- Reset `2.0.0` → `1.0.0` everywhere (plugin header, `_VERSION` constant, all `@since` / `@version`, `readme.txt` Stable tag, `CHANGELOG.md` heading). Don't touch the SemVer / Keep-a-Changelog URLs.
- Wipe `CHANGELOG.md` body to a single empty `## 1.0.0 - unreleased` block (keep prologue + markers).
- Delete `changelog/*.md` (keep `.gitkeep`).
- Rewrite `readme.txt` Description / Tags / Contributors / etc.

## Local Development

Requires:

- PHP 8.5+.
- Node.js 26+ / npm 11+ (for `@wordpress/env`).
- Docker (for `@wordpress/env`).

Coverage runs need the `pcov` extension (`pecl install pcov`): CI's unit job collects coverage so the strict coverage-metadata gate enforces, and `composer test:unit -- --coverage-text` does the same locally. Plain test runs don't need it.

```sh
composer packages-install   # Resolves deps + runs php-scoper → dependencies/
composer test:unit          # Pure PHP unit tests, no Docker

npm install                 # Installs @wordpress/env, @playwright/test, etc.
npx playwright install      # Downloads browser binaries (one-time, ~150MB)
npm run wp-env:start        # Boots WordPress in Docker

composer test:integration   # PHPUnit Integration suite (real WP, no browser)
npm run test:e2e            # Playwright E2E suite (browser, real admin UI)

npm run wp-env:stop
```

> Use `composer packages-install` / `packages-update` (never bare `composer install` / `update`) — the wrappers pass `--ignore-platform-reqs`, which prevents composer from emitting a `platform_check.php` that would bypass the framework's friendly version-check admin notice.

`wp-env:start` activates the plugin for you (via the `afterStart` script in `.wp-env.json`). Open <http://localhost:8811/wp-admin> and you'll see the "DWS Plugin Template is active." admin notice.

CI pins the integration and E2E jobs to the WP floor; reproduce locally with `WP_ENV_CORE=https://wordpress.org/wordpress-7.0.zip npm run wp-env:start`.

The plugin declares a `dws-plugin-template` text domain and `Domain Path: /languages`. Translation catalogs are generated at release — wp.org builds them for hosted plugins, and forks distributed elsewhere run `wp i18n make-pot . languages/dws-plugin-template.pot` (the shipped `languages/` directory is ready for them).

## Commands

| Command                           | What it does                                            |
|-----------------------------------|---------------------------------------------------------|
| `composer test:unit`              | PHPUnit Unit suite (no Docker)                          |
| `composer test:integration`       | PHPUnit Integration suite (requires wp-env)             |
| `npm run test:e2e`                | Playwright E2E suite (requires wp-env)                  |
| `composer lint:php`               | PHPCS + PHPStan                                         |
| `composer format:php`             | PHPCBF auto-fix                                         |
| `composer quality-check`          | `lint:php` + `test:unit`                                |
| `composer scope-php-dependencies` | Manual scoping run (auto-runs during `packages-install` / `packages-update`) |
