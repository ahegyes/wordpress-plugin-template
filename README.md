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
│   ├── Component/            # WelcomeNotice + ExampleWPSettings + ExampleSettings (HookableInterface components)
│   └── Settings/             # ExampleWCSettingsPage (DescriptorBackedWCSettingsPage subclass)
├── config/
│   ├── container.php         # PHP-DI definitions (the composition root)
│   └── footprint.php         # Single source of the persistent option/meta keys (container + uninstall fallback)
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

## Fork Setup

Step 1 in a fork is the **Fill in scaffold** workflow (Actions → "Fill in scaffold" → Run workflow). It takes the plugin's display name, slug, PHP namespace, and wp-env port, then substitutes every placeholder below, renames the entry file, resets the version to `0.1.0` and the changelog to a single Unreleased heading, rewrites the repository-metadata URLs to the fork, and pushes the result back to the ref you ran it on (run it on trunk for the standard flow). The workflow refuses to run on the template repo itself.

What the workflow can't do — review by hand afterwards:

- Write real `readme.txt` prose: the workflow resets Description and Tags to TODO placeholders; Contributors stays.
- Adjust the Composer package name when it should follow the `wc-*` / `wp-*` convention instead of the slug — the workflow sets `ahegyes/<slug>`.
- wp.org listing assets (banners, screenshots) when publishing there.
- Review the `.github/ISSUE_TEMPLATE/` forms — their wording targets the unmodified template, not a forked plugin — and `SECURITY.md`'s scope prose.
- Prose in `README.md` / `docs/` that describes the example components you replace.

## Placeholder Convention

The rename surface the workflow substitutes (the manual list for forks without Actions). Pick the column matching your fork:

| Placeholder                                    | WC plugin example                            | Generic WP plugin example            |
|------------------------------------------------|----------------------------------------------|--------------------------------------|
| `dws-plugin-template` (slug + text-domain)     | `locked-payment-methods-for-woocommerce`     | `internal-comments`                  |
| `DWS_PLUGIN_TEMPLATE` (constants)              | `DWS_LPMWC`                                  | `DWS_IC`                             |
| `dws_plugin_template_` (functions)             | `dws_lpmwc_`                                 | `dws_ic_`                            |
| `dws_plugin_template` (slug / option-key base) | `dws_lpmwc`                                  | `dws_ic`                             |
| `DeepWebSolutions\PluginTemplate\` (namespace) | `DeepWebSolutions\LockedPaymentMethods\`     | `DeepWebSolutions\InternalComments\` |
| `DWS Plugin Template` (display)                | `Locked Payment Methods for WooCommerce`     | `Internal Comments`                  |
| `ahegyes/dws-plugin-template` (Composer name)  | `ahegyes/wc-locked-payment-methods`          | `ahegyes/wp-internal-comments`       |
| `8811` (wp-env port)                           | any free port (avoid 8888/8889)              | any free port (avoid 8888/8889)      |

The two underscore forms are distinct. `dws_plugin_template` (bare) is the settings-page slug and the option-key base every persistent row in `config/footprint.php` is built from; `dws_plugin_template_` (trailing) is the function and option-key prefix. Replacing the bare form as a plain substring covers both — the prefix is just the base plus `_` — so renaming only the trailing form leaves the settings options written under the old slug, orphaned on uninstall.

**WC plugins follow these conventions** (WC trademark policy included):
- **Display**: `X for WooCommerce` (WC trademark requirement). NOT "WooCommerce X" or "WC: X".
- **Slug** + **text-domain**: end in `-for-woocommerce`.
- **Constant + function abbreviations** end in `WC` (`LPMWC` = Locked Payment Methods + WooCommerce). Keeps `DWS_` prefix.
- **Composer package name**: `ahegyes/wc-<short-name>` (e.g., `ahegyes/wc-locked-payment-methods`).

**Generic WP plugins** drop the `WC` everything: plain slug, plain abbreviation, `wp-` Composer prefix (e.g., `ahegyes/wp-internal-comments`).

The Composer **package** name (`ahegyes/wc-*` / `ahegyes/wp-*`) and the GitHub **repository** name are separate identifiers: the repository keeps the plugin slug (e.g., `github.com/ahegyes/locked-payment-methods-for-woocommerce`), while the Composer name is what `composer.json` declares and other manifests `require`.

**Both variants use a flat namespace** — `DeepWebSolutions\PluginName\` only, no grouping segments between the vendor and the plugin name.

The port appears in `.wp-env.json` and `.wp-env.belowfloor.json` (`"port"`), `playwright.config.js` (`WP_BASE_URL`), and this README's "Open localhost" line below — keep them in sync.

Scoped deps land under `\Scoped\` inside the plugin's namespace (`DeepWebSolutions\PluginTemplate\Scoped\DI\...`). The `\Scoped\` segment is invariant; renaming the namespace placeholder above is enough.

## Fork Reset Checklist

The mechanical reset the workflow performs — the manual list for forks without Actions:

- Reset `2.0.0` → `0.1.0` everywhere (plugin header, `_VERSION` constant, all `@since` / `@version`, `readme.txt` Stable tag, `CHANGELOG.md` heading). Don't touch the SemVer / Keep-a-Changelog URLs.
- Wipe `CHANGELOG.md` body to a single empty `## 0.1.0 - unreleased` block (keep prologue + markers).
- Delete `changelog/*.md` (keep `.gitkeep`).
- Rewrite `readme.txt` Description / Tags / Contributors / etc.
- Repoint every repository-metadata URL at the fork: `composer.json` `homepage` + `authors.homepage`, `package.json` `repository` + `bugs`, `SECURITY.md`'s advisory link, and `readme.txt`'s full-changelog link all reference this template's GitHub repository until reset.

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
