# DWS Plugin Template

TODO: one sentence saying why this plugin exists, its philosophy rather than a list of what it holds. The same sentence is `composer.json`'s and `package.json`'s `description`, the entry file's `Description` header and the repository's GitHub description.

## Requirements

The entry file's headers state the floors: `Requires PHP`, `Requires at least` for WordPress, and `Requires Plugins` with `WC requires at least` for WooCommerce. On a site below them, WordPress shows its own notice and none of the plugin's features load.

## Installation

Download `dws-plugin-template.zip` from the repository's latest release, then upload it under Plugins → Add New Plugin → Upload Plugin. Later releases arrive through WordPress's own update check, from the repository the entry file's `Update URI` names, while that repository is public, since the check calls GitHub's API without authentication.

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
| The greeting | `src/Greeting/`, the Greeting section of `src/Settings.php`, and the `DWS_TPL_OPTION_GREETING_ENABLED`, `DWS_TPL_OPTION_GREETING` and `DWS_TPL_OPTION_GREETING_ORDERS` keys in `includes/options.php` | Delete `src/Greeting/` and its row in `Plugin::COMPONENTS`, the Greeting section of `src/Settings.php`, `assets/src/greeting.scss` and its `greeting=` entry in `package.json`'s `build:assets` and `start:assets`, the three keys with their defaults, text and validator, their lines in `dws_tpl_add_settings()`, and `tests/Integration/Greeting/`, then the rows naming them in `OptionsTest`, `MigrationsTest` and `SettingsTest`, and `PluginBootTest`'s expectation that the greeting is skipped. `tests/EndToEnd/settings.spec.ts` drives the greeting's validated field, so point it at a validated field of the plugin's own. |
| The example block | `blocks/src/example-notice/`, registered by `src/Blocks.php` from `blocks/build/blocks-manifest.php` and rendered by its `render.php` | Delete `blocks/`, `build:blocks` and `start:blocks` with their calls in `build` and `start`, `register_blocks()` and its `init` hook in `src/Blocks.php`, `BlocksTest`'s block rows, and the editor script's command, which inserts the block, with `tests/EndToEnd/editor.spec.ts`. |
| The editor script | `assets/src/editor.ts`, a command in the editor's command palette that inserts the example block, enqueued in the block editor by `src/Blocks.php` | Delete it, its `editor=` entry in `build:assets` and `start:assets`, `@wordpress/commands` and `@wordpress/data` from `package.json`'s `devDependencies`, `enqueue_editor_script()` and its hook in `src/Blocks.php`, `BlocksTest`'s editor row and `tests/EndToEnd/editor.spec.ts`. Once `assets/src/` holds nothing, delete `build:assets` and `start:assets` with their calls in `build` and `start`. Once the plugin registers no blocks either, delete `src/Blocks.php`, its row in `Plugin::COMPONENTS` and `BlocksTest`. |
| `models/` and `templates/` | Empty extension points, for classes on Composer's classmap and for template partials | Delete the folders the plugin never fills, and `models/` from `composer.json`'s `autoload.classmap` and `phpstan.dist.neon`'s `paths`, since Composer and PHPStan both fail on a missing folder. |
| The fill-in | `.github/workflows/fill-in-scaffold.yml`, `.github/scripts/fill-in-scaffold.sh` and Quality's `scaffold` job | Delete all three: GitHub refuses the fill-in's own token for workflow changes, so the fill-in cannot remove them, and the job skips itself outside the template. |

Whichever parts go, four settings follow what is left, since a plugin removes them in any order:

- The entry's build gate checks `blocks/build/blocks-manifest.php` and `assets/build/editor.asset.php`, and the plugin stays off until both exist, so it checks only files the remaining build writes, and drops its built-file check once nothing is built.
- `lint:scripts` and `format:scripts` name only folders that still hold a script, since ESLint fails on a path that holds none.
- `lint:styles` names only folders that still hold SCSS. Once none does, it keeps one pattern, such as `'assets/src/**/*.scss'`, and takes `--allow-empty-input`, since Stylelint fails when nothing matches, Quality runs it, and without a pattern wp-scripts lints every stylesheet in the project.
- `build` stays, even once it runs nothing, since `npm run setup` and the Integration run call it.

Still to decide by hand:

- WooCommerce's order tables: `dws_tpl_declare_compatibility()` in `functions.php` declares the plugin incompatible until its order code is verified on both storages.
- `SECURITY.md`'s scope and the issue forms in `.github/ISSUE_TEMPLATE/`, whose wording describes this plugin's demo.
