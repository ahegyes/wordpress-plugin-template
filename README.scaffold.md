# DWS Plugin Template

TODO: one sentence saying why this plugin exists, its philosophy rather than a list of what it holds. The same sentence is `composer.json`'s and `package.json`'s `description`, the entry file's `Description` header and the repository's GitHub description.

## Requirements

The entry file's headers state the floors: `Requires PHP`, `Requires at least` for WordPress, and `Requires Plugins` with `WC requires at least` for WooCommerce. WordPress shows its own notice below the PHP or WordPress floor or without WooCommerce, and the plugin shows its own below WooCommerce's version floor; either way, none of the plugin's features load.

## Installation

Download `dws-plugin-template.zip` from the repository's latest release, then upload it under Plugins → Add New Plugin → Upload Plugin. Later releases arrive through WordPress's own update check, from the repository the entry file's `Update URI` names, while that repository is public, since the check calls GitHub's API without authentication.

## Extending

Other plugins integrate through the plugin's functions, its hooks and WordPress's and WooCommerce's APIs on the names below, never through its classes. They call its functions no earlier than `plugins_loaded`, or than the hook a function's docblock names, and behind `function_exists()`, since the plugin may load later or stay active below its floors without defining them, and register their extensions on `dws_plugin_template/boot/succeeded`, hooked before `plugins_loaded` reaches priority 15, where the plugin boots.

- `dws_tpl_should_show_greeting( $user_id )` answers, from `wp_loaded` on and where WooCommerce is active, whether a customer sees the account dashboard greeting, and the `dws_plugin_template/greeting/should_show` filter changes that answer, receiving it and the customer's `WP_User`.
- `dws_tpl_get_plugin_slug()`, `dws_tpl_get_plugin_name()` and `dws_tpl_get_plugin_version()` read the plugin's header.
- `dws_plugin_template/boot/succeeded`, `dws_plugin_template/boot/blocked` and `dws_plugin_template/boot/failed` fire once the plugin's boot has an outcome.
- The settings are the options `dws_tpl_greeting_enabled`, `dws_tpl_greeting`, `dws_tpl_greeting_orders` and `dws_tpl_delete_data`, which `get_option()` reads.

## Development

Development needs PHP and Composer at the `Requires PHP` floor or later, Node.js and npm at `package.json`'s `engines`, Docker for `@wordpress/env`, and a POSIX shell for `npm run start`.

Install the dependencies, prefix the framework into `vendor-prefixed/`, build the scripts and start the development site on <http://localhost:8811>:

```sh
npm run setup
```

wp-env publishes the development site on every network interface, with fixed development credentials, so other devices on the local network can reach it.

Rebuild the blocks and assets on every change:

```sh
npm run start
```

Run the manifest and changelog checks, PHPCS, PHPStan, and the Unit and Integration suites, starting the tests site on <http://localhost:8812> as needed:

```sh
composer quality-check
```

Install the browser the end-to-end suite runs in, initially and after a Playwright update that needs a new browser revision, then run the suite against the tests site:

```sh
npx playwright install chromium
npm run test:e2e
```

Lint the scripts, styles and types:

```sh
npm run lint
```

`docs/ARCHITECTURE.md` describes how the plugin loads and boots, `docs/recipes.md` holds the step-by-step changes most plugins need, `tests/README.md` describes the test suites, and `AGENTS.md` holds the conventions for people and agents working on the code.

## After the fill-in

The fill-in renamed the plugin and kept the template's demo, which boots and passes every suite. Replace or delete each part as the plugin grows its own:

| Part | Where it lives | Removing it |
| --- | --- | --- |
| The greeting | `src/Greeting/`, its fields in `GreetingSettings`, which `src/Settings.php` spreads into the tab, the `GREETING_ENABLED`, `GREETING` and `GREETING_ORDERS` keys in `src/Options.php`, and `includes/greeting.php`, the function through which another plugin asks whether a customer sees it | Delete `src/Greeting/` and its row in `Plugin::COMPONENTS`, `includes/greeting.php`, the greeting's lines under Extending, the `GreetingSettings` spread in `src/Settings.php`, `assets/src/greeting.scss` and its `greeting=` entry in `package.json`'s `build:assets` and `start:assets`, the three keys with their defaults, and `tests/Integration/Greeting/`, then the rows naming them in `OptionsTest`, the Integration `MigrationsTest` and `SettingsTest`, `PluginBootTest`'s expectation that the greeting is skipped, and the fingerprint in the Unit `MigrationsTest`, which becomes the one its failure prints, since no release has seeded the old keys. `tests/EndToEnd/settings.spec.ts` drives the greeting's number field, so point it at a field of the plugin's own. |
| The example block | `blocks/src/example-notice/`, registered by `src/Blocks.php` from `blocks/build/blocks-manifest.php` and rendered by its `render.php` | Delete `blocks/`, `build:blocks` and `start:blocks` with their calls in `build` and `start`, `register_blocks()` and its `init` hook in `src/Blocks.php`, `BlocksTest`'s block rows, and the editor script's command, which inserts the block, with `tests/EndToEnd/editor.spec.ts`. |
| The editor script | `assets/src/editor.ts`, a command in the editor's command palette that inserts the example block, enqueued in the block editor by `src/Blocks.php` | Delete it, its `editor=` entry in `build:assets` and `start:assets`, `@wordpress/commands` and `@wordpress/data` from `package.json`'s `devDependencies`, `enqueue_editor_script()` and its hook in `src/Blocks.php`, `BlocksTest`'s editor row and `tests/EndToEnd/editor.spec.ts`. Once `assets/src/` holds nothing, delete `build:assets` and `start:assets` with their calls in `build` and `start`. Once the plugin registers no blocks either, delete `src/Blocks.php`, its row in `Plugin::COMPONENTS` and `BlocksTest`. |
| `models/` and `templates/` | Empty extension points, for classes on Composer's classmap and for template partials | Delete the folders the plugin never fills, and `models/` from `composer.json`'s `autoload.classmap` and `phpstan.dist.neon`'s `paths`, since Composer and PHPStan both fail on a missing folder. |
| The fill-in | `.github/workflows/fill-in-scaffold.yml`, `.github/scripts/fill-in-scaffold.sh` and Quality's `scaffold` job | Delete all three: GitHub refuses the fill-in's own token for workflow changes, so the fill-in cannot remove them, and the job skips itself outside the template. |

Whichever parts go, four settings follow what is left, since a plugin removes them in any order:

- The entry's build gate checks `blocks/build/blocks-manifest.php` and `assets/build/editor.asset.php`, and the plugin stays off until both exist, so it checks only files the remaining build writes, and drops its built-file check once nothing is built. `PartialDeployTest` hides each file the gate checks in a row of its own, so its rows follow the gate.
- `lint:scripts` and `format:scripts` name only folders that still hold a script, since ESLint fails on a path that holds none.
- `lint:styles` names only folders that still hold SCSS. Once none does, it keeps one pattern, such as `'assets/src/**/*.scss'`, and takes `--allow-empty-input`, since Stylelint fails when nothing matches, Quality runs it, and without a pattern wp-scripts lints every stylesheet in the project.
- `build` stays, even once it runs nothing, since `npm run setup` and the Integration run call it.

Still to decide by hand:

- WooCommerce's order tables: the `before_woocommerce_init` closure in `functions.php` declares the plugin incompatible until its order code is verified on both storages.
- `SECURITY.md`'s scope, whose first out-of-scope line names the plugins generated from the template.
