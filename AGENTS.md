# wordpress-plugin-template

GitHub template for WordPress plugins built on the five wp-framework packages (`ahegyes/wp-framework-{bootstrap,shared,core,settings,woocommerce}`). Each plugin ships its own copy of the framework, PHP-DI and their dependencies, prefixed under `DeepWebSolutions\PluginTemplate\Scoped` by php-scoper through wordpress-configs on every development install, into `vendor-prefixed/`. This file covers what an agent needs beyond the README.

## Layout

- `dws-plugin-template.php`: the entry. It requires `vendor-prefixed/ahegyes/wp-framework-bootstrap/load.php`, registers the GitHub updater, checks the PHP and WordPress floors, loads `includes/safeguards.php`, then the autoloader and `functions.php`; a missing or partial build shows a notice instead of booting.
- `functions.php`: the `dws_tpl_plugin()` accessor, the boot on `plugins_loaded` at priority 15, the deactivation callback, the HPOS declaration (`false` until a plugin's order code is verified) and the loader that requires every `includes/*.php` in name order, except names starting with `_`.
- `src/Plugin.php`: the composition root, with `SLUG`, `PREFIX` (`dws_tpl`) and `COMPONENTS` (the scheduler, admin notices, `Settings` and `Feature\Feature`). It builds the logger and the scheduler, then, once WooCommerce passes its check, the migration runner over `dws_tpl_get_migration_steps()` and the kernel over a PHP-DI container. `src/` holds only the root and its components; code that registers no hooks lives in `includes/`.
- `src/Settings.php`: an always-on component that adds the WooCommerce settings tab `dws_tpl`, holding the greeting's settings and the uninstall consent.
- `src/Feature/`: the greeting, a composite gated on `DWS_TPL_OPTION_FEATURE`, whose child greets customers on their account dashboard once their orders reach the set number.
- `includes/footprint.php`: `dws_tpl_get_footprint()`, every kind of data the plugin stores. Uninstall removes it where the site consents, and deactivation unschedules its jobs.
- `includes/options.php`: the `DWS_TPL_OPTION_*` keys, their `DWS_TPL_OPTION_DEFAULTS`, and `dws_tpl_get_bool_option()`, `dws_tpl_get_int_option()`, `dws_tpl_get_string_option()` and `dws_tpl_get_list_option()`, which guard each stored value; `dws_tpl_get_option_text()`, the translated defaults, read after `init`; and the greeting's order-count validator.
- `includes/migrations.php`: `dws_tpl_get_migration_steps()`, the append-only migration steps, whose first stores the settings' untranslated defaults, autoloaded.
- `uninstall.php`: runs the framework's uninstall engine over the footprint, with consent from `DWS_TPL_OPTION_DELETE_DATA`, once the floors are met.

## Commands

```sh
composer packages-install    # dependencies, then php-scoper into vendor-prefixed/
composer test:unit
composer test:integration    # starts .wp-env.tests.json, then runs test:integration:run
composer quality-check       # manifest and changelog validation, PHPCS (both profiles), PHPStan, Unit and Integration
npm run setup                # packages-install for Composer and npm, then the development environment
```

## Environments

- `.wp-env.json`: development, as `local`, on port 8811, behind `npm run setup` and `wp-env:*`. The framework rethrows boot and migration failures there.
- `.wp-env.tests.json`: Integration, as production, on port 8812, behind `wp-env:tests:*` and `composer test:integration`.

Both run WordPress 7.1.3 on PHP 8.5, with WooCommerce from `vendor/` and HPOS on.

## Cache layout

All generated test/build cache under `tests/.cache/`:
- `tests/.cache/phpunit/` — PHPUnit's `cacheDirectory`
- `tests/.cache/artifacts/` — Playwright outputs (storage states, screenshots, test-results)

`.gitignore` has `tests/.cache` in the custom block above the toptal section.
