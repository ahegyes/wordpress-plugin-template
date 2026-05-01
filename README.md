# DWS Plugin Template

Scaffold for WordPress plugins built on the DWS v2 framework. Consumes the framework + PHP-DI via per-plugin scoped dependencies (humbug/php-scoper), so each shipped plugin carries its own frozen copy of the framework — eliminating major-version conflict risk between plugins shipping different framework versions.

## Architecture

```
dws-plugin-template/
├── dws-plugin-template.php   # WP plugin entry: header → autoload → check_requirements → boot
├── functions.php             # Global facade (theme/snippet API surface)
├── uninstall.php             # WP-invoked cleanup on plugin deletion
├── src/
│   ├── Plugin.php            # Singleton: container + kernel + lifecycle
│   └── AdminNotice.php       # Demo HookableInterface component
├── config/
│   └── container.php         # PHP-DI definitions
├── tests/
│   ├── bootstrap.php         # Composer autoload + (in wp-env) WP load
│   ├── Unit/                 # Pure PHP, no Docker
│   └── Integration/          # wp-env Docker, real WP loaded
├── scoper.inc.php            # php-scoper config — extends wordpress-configs base
└── dependencies/             # Generated: scoped framework + PHP-DI (gitignored)
```

## Placeholder Convention

Replace these throughout the codebase when forking:

| Placeholder | Example replacement |
|---|---|
| `dws-plugin-template` (slug) | `wc-locked-payment-methods` |
| `DWS_PLUGIN_TEMPLATE` (constants) | `WC_LPM` |
| `dws_plugin_template_` (functions) | `wc_lpm_` |
| `DeepWebSolutions\PluginTemplate\` (namespace) | `DeepWebSolutions\LockedPaymentMethods\` |
| `DWS_PLUGIN_TEMPLATE_Deps` (scoping prefix) | `WC_LPM_Deps` |
| `Plugin Template` (display) | `WC: Locked Payment Methods` |

The `fill-in-scaffold.yml` workflow (deferred — not yet shipped) will automate this.

## Local Development

Requires:

- PHP 8.5+ with `pcov` extension (homebrew: `pecl install pcov`).
- Node.js 24+ (for `@wordpress/env`).
- Docker (for `@wordpress/env`).

```sh
composer install            # Resolves deps + runs php-scoper → dependencies/
composer test:unit          # Pure PHP unit tests, no Docker

npm install                 # Installs @wordpress/env
npm run wp-env:start        # Boots WordPress in Docker
composer test:integration   # Runs PHPUnit Integration suite inside wp-env
npm run wp-env:stop
```

To activate the plugin in the wp-env browser:

```sh
npm run wp-env -- run cli wp plugin activate dws-plugin-template
```

Open <http://localhost:8888/wp-admin> and you'll see the "DWS Plugin Template is active" admin notice.

## Composer Scripts

| Command | What it does |
|---|---|
| `composer test:unit` | PHPUnit Unit suite (no Docker) |
| `composer test:integration` | PHPUnit Integration suite (requires wp-env) |
| `composer lint:php` | PHPCS + PHPStan |
| `composer format:php` | PHPCBF auto-fix |
| `composer quality-check` | `lint:php` + `test:unit` |
| `composer scope-php-dependencies` | Manual scoping run (auto-runs after `composer install`) |

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
