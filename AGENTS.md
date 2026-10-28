# wordpress-plugin-template

GitHub template for WordPress plugins built on the five wp-framework packages (`ahegyes/wp-framework-{bootstrap,shared,core,settings,woocommerce}`). Each plugin ships its own copy of the framework, PHP-DI and their dependencies, prefixed under `DeepWebSolutions\PluginTemplate\Scoped` by php-scoper through wordpress-configs on every development install, into `vendor-prefixed/`. This file covers what an agent needs beyond the README.

## Layout

- `dws-plugin-template.php`: the entry. It requires `vendor-prefixed/ahegyes/wp-framework-bootstrap/load.php`, registers the GitHub updater, checks the PHP and WordPress floors, loads `includes/safeguards.php`, then the autoloader and `functions.php`; a missing or partial build, unbuilt scripts included, shows a notice instead of booting.
- `functions.php`: the `dws_tpl_plugin()` accessor, the boot on `plugins_loaded` at priority 15, the deactivation callback, the HPOS declaration (`false` until a plugin's order code is verified) and the loader that requires every `includes/*.php` in name order, except names starting with `_`.
- `src/Plugin.php`: the composition root, with `SLUG`, `PREFIX` (`dws_tpl`) and `COMPONENTS` (the scheduler, admin notices, `Settings`, `Blocks` and `Feature\Feature`). It builds the logger and the scheduler, then, once WooCommerce passes its check, the migration runner over `dws_tpl_get_migration_steps()` and the kernel over a PHP-DI container. `src/` holds only the root and its components; code that registers no hooks lives in `includes/`.
- `src/Settings.php`: an always-on component that adds the WooCommerce settings tab `dws_tpl`, holding the greeting's settings and the uninstall consent.
- `src/Blocks.php`: registers the blocks from `blocks/build/blocks-manifest.php` on `init`, and enqueues `assets/build/editor.js` in the block editor.
- `src/Feature/`: the greeting, a composite gated on `DWS_TPL_OPTION_FEATURE`, whose child greets customers on their account dashboard once their orders reach the set number.
- `includes/footprint.php`: `dws_tpl_get_footprint()`, every kind of data the plugin stores. Uninstall removes it where the site consents, and deactivation unschedules its jobs.
- `includes/options.php`: the `DWS_TPL_OPTION_*` keys, their `DWS_TPL_OPTION_DEFAULTS`, and `dws_tpl_get_bool_option()`, `dws_tpl_get_int_option()`, `dws_tpl_get_string_option()` and `dws_tpl_get_list_option()`, which guard each stored value; `dws_tpl_get_option_text()`, the translated defaults, read after `init`; and the greeting's order-count validator.
- `includes/migrations.php`: `dws_tpl_get_migration_steps()`, the append-only migration steps, whose first stores the settings' untranslated defaults, autoloaded.
- `includes/assets.php`: `dws_tpl_get_asset()`, which reads the `*.asset.php` payload wp-scripts generates beside a built file, and `dws_tpl_register_script()` and `dws_tpl_register_style()`, which register a built file with its dependencies and version.
- `blocks/src/` and `assets/src/`: the TypeScript and SCSS sources of the blocks (`example-notice`), the editor script and the greeting's stylesheet. `npm run build` compiles them into `blocks/build/` and `assets/build/`, which git ignores and the release builds.
- `models/` and `templates/`: empty extension points, for namespace-less classes on Composer's classmap and for template partials.
- `uninstall.php`: runs the framework's uninstall engine over the footprint, with consent from `DWS_TPL_OPTION_DELETE_DATA`, once the floors are met.

## Commands

```sh
composer packages-install    # dependencies, then php-scoper into vendor-prefixed/
composer test:unit
composer test:integration    # starts .wp-env.tests.json, then runs test:integration:run, which builds the scripts first
composer quality-check       # manifest and changelog validation, PHPCS (both profiles), PHPStan, Unit and Integration
npm run setup                # packages-install for Composer and npm, the scripts' build, then the development environment
npm run build                # blocks and assets, through wp-scripts; npm run start watches them
npm run lint:scripts         # ESLint, with lint:styles (Stylelint) and lint:types (tsc) beside it
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
