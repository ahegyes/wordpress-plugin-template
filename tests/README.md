# Tests

This document describes the plugin's three test suites, the environments they run in and how CI runs them.

## Suites

| Suite | Where | Runs on | Covers |
| --- | --- | --- | --- |
| Unit | `tests/Unit/` | PHP alone, without WordPress | the component tree, the footprint's shape and coverage, and the entry's `WC tested up to` against the locked WooCommerce |
| Integration | `tests/Integration/` | the tests site, with WordPress, WooCommerce and order tables loaded, and the multisite site for its `multisite` group | the boot, every component, the options and their readers, the migrations, deactivation, uninstall, the assets and blocks, the peer getters, a partial deploy: a missing build file or engine, or an include that throws, the safeguard recipe's own test, and the boot and settings on a second site of a network |
| End to end | `tests/EndToEnd/` | the tests site, in a browser through Playwright | the settings tab by keyboard, a rejected and a corrected value, accessibility through axe, the example block inserted from the editor's command palette and published in its Outlined style, and the plugin with WooCommerce deactivated |

PHPUnit runs both PHP suites in random order, so each row sets up what it needs and restores what it changes; its cache lives in `tests/.cache/phpunit/`, and Playwright's output in `tests/.cache/artifacts/`.

## Environments

- `.wp-env.json` is the development site on <http://localhost:8811>, as `local`, where the framework rethrows boot and migration failures.
- `.wp-env.tests.json` is the tests site on <http://localhost:8812>, as `production`, which the Integration and end-to-end suites share. A row whose separate process changes the database restores it there, or flushes the parent's object cache in `#[AfterClass]`, and the end-to-end specs set their own preconditions, because either suite may run first.
- `.wp-env.multisite.json` is a network on <http://localhost:8813>, as `production`, with WooCommerce and the plugin network-activated, where the Integration suite's `multisite` group runs and the default run skips it.

Each starts WordPress on PHP 8.5 with WooCommerce from `vendor/` and its order tables on.

## Running

Run the Unit suite:

```sh
composer test:unit
```

Start the tests site, build the scripts and run the Integration suite:

```sh
composer test:integration
```

Start the multisite site, build the scripts and run the Integration suite's `multisite` group:

```sh
composer test:integration:multisite
```

Run the end-to-end suite, which starts the tests site when it is down and needs the built scripts and Playwright's Chromium, installed initially and after a Playwright update that needs a new browser revision:

```sh
npx playwright install chromium
npm run test:e2e
```

Run every check CI runs on PHP, Integration included:

```sh
composer quality-check
```

## Continuous integration

- **Tests** runs Unit once, and Integration, its `multisite` group and the end-to-end suite once per WordPress branch, from the entry file's `Requires at least` up to the newest release, which WordPress's version API lists, so a new release joins without an edit.
- **Tests (nightly)** runs Integration, its `multisite` group and the end-to-end suite on WordPress trunk every day, outside pull requests.
- **Release** builds the zip on every pull request as a tag would, runs Plugin Check and the archive checks, then activates the zip on a site and runs the end-to-end suite against it; it publishes only on a tag, and never on the template.
- **Quality** runs PHPCS, PHPStan, the PHP 7.4 parse of the entry file, `uninstall.php` and `includes/`, the script, style and type lints, the `block.json` schema and the changelog fragments, and on the template it rehearses the fill-in at the longest prefix it accepts, then lints, unit-tests and builds the filled-in plugin.

## Conventions

Test classes are `final`, named after the class or the behavior they cover with a `Test` suffix, with `test_snake_case` methods, kebab-case data provider keys and no docblocks. They use real instances and WordPress's own APIs, never mocks of the code under test.
