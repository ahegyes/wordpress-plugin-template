# wordpress-plugin-template

A WordPress plugin built on the five wp-framework packages (`ahegyes/wp-framework-{bootstrap,shared,core,settings,woocommerce}`), from the template that starts every such plugin. It ships its own copy of the framework, PHP-DI and their dependencies, prefixed under `DeepWebSolutions\PluginTemplate\Scoped` by php-scoper through wordpress-configs into `vendor-prefixed/` on every development install. Its architecture follows the framework's conventions 1–10 in [wordpress-framework's AGENTS.md](https://github.com/ahegyes/wordpress-framework/blob/trunk/AGENTS.md); this file covers what is particular to the plugin. `docs/ARCHITECTURE.md` describes the boot, `docs/recipes.md` the common changes, `tests/README.md` the suites.

## Layout

```
dws-plugin-template.php      the entry: the @api DWS_TPL_FILE, the safeguards, then, in one try, bootstrap load.php, the updater, the floor check, the build gate, the autoloader and functions.php, whose catch shows the build notice
Safeguards.php               the safeguards, as static closures, which the entry requires before the floor check
functions.php                the @api dws_tpl_plugin(), the includes/ loader, then the boot on plugins_loaded 15, the HPOS declaration (false) and deactivation, as static closures
uninstall.php                the framework's uninstall engine over Plugin::FOOTPRINT, with Options::DELETE_DATA's consent
src/                         the plugin's classes, each named for its concept
├── Plugin.php               SLUG, PREFIX (dws_tpl), COMPONENTS, FOOTPRINT, the identity, the logger, the scheduler, the migration runner and the kernel
├── Options.php              the option keys, their defaults in stored form and the typed readers
├── Migrations.php           get_steps() over the protected steps, append-only
├── Settings.php             the WooCommerce settings tab dws_tpl: its registration, the uninstall consent and each feature's fields
├── Blocks.php               the blocks from blocks/build/blocks-manifest.php and the block-editor script
└── Greeting/                the greeting: GreetingFeature, gated on Options::GREETING_ENABLED, its AccountDashboardGreeting child, which owns the default message and the decision whether a customer sees the greeting, and GreetingSettings, its fields
includes/                    more @api functions and constants, grouped by topic, loaded in name order, _-prefixed files skipped
├── greeting.php             whether a customer sees the greeting, the decision another plugin asks about
└── metadata.php             the slug, name and version getters, the plugin's surface for a peer plugin
blocks/src/, assets/src/     TypeScript, SCSS and each block's render.php, built into blocks/build/ and assets/build/ (untracked)
models/, templates/          empty extension points, for the @api model classes and interfaces and for template partials
.github/scripts/fill-in-scaffold.sh  the fill-in's sweep, which fill-in-scaffold.yml runs once in a new plugin and Quality rehearses here
```

## Commands

```sh
composer packages-install    # dependencies, then php-scoper into vendor-prefixed/
composer test:unit
composer test:integration    # starts .wp-env.tests.json, then runs test:integration:run, which builds the scripts first
composer test:integration:multisite  # starts .wp-env.multisite.json, then runs the Integration suite's multisite group, which the default run skips
composer quality-check       # manifest and changelog validation, PHPCS (both profiles), PHPStan (both profiles), Unit and Integration
npm run setup                # both installs, the scripts' build, then the development site
npm run build                # blocks and assets through the local wp-scripts; npm run start watches them in a POSIX shell
npm run lint                 # lint:scripts (ESLint), lint:styles (Stylelint) and lint:types (tsc)
npm run test:e2e             # Playwright on .wp-env.tests.json, starting it if needed; the scripts must be built
npm run quality-check:all    # composer quality-check, then npm run lint and npm run test:e2e
```

Run wp-scripts only through the npm scripts or `node_modules/.bin/wp-scripts`: `npx wp-scripts` can install an unrelated package of that name.

## Environments

- `.wp-env.json`: development, as `local`, on port 8811, behind `npm run setup` and `wp-env:*`. The framework rethrows boot and migration failures there.
- `.wp-env.tests.json`: Integration and end to end, as `production`, on port 8812, behind `wp-env:tests:*`, `composer test:integration` and `npm run test:e2e`. The end-to-end suite deactivates and reactivates WooCommerce there.
- `.wp-env.multisite.json`: a network with WooCommerce and the plugin network-activated, as `production`, on port 8813, behind `wp-env:multisite:*` and `composer test:integration:multisite`, for the Integration suite's `multisite` group.

Each runs PHP 8.5 with WooCommerce from `vendor/` and its order tables on; CI runs Integration and end to end on every WordPress branch from the entry's `Requires at least` up, and nightly on WordPress trunk.

## Conventions

1. **Placement and surface.** The global namespace holds only the plugin's contract, each global `@api`, which `GlobalsTest` checks, and tested: the entry's `DWS_TPL_FILE`, the functions and constants `functions.php` and `includes/` declare, among them the accessor, whose composition root is internal, and the classes and interfaces of `models/`; `functions.php` registers its hooks as static closures. The globals load before the boot, so they stay defined whether or not it succeeds, and until it has, the `src/` code an `@api` function calls reaches nothing the boot checks, such as WooCommerce, and does something neutral in its place. Integrators touch only those globals, the plugin's hooks and WordPress's and WooCommerce's APIs on the contract's literal names, never `src/` or `Safeguards.php`. A symbol enters without a named caller when it exposes a noun or verb the plugin's screens give an admin, a decision the plugin makes, an event when it changes its data or the inverse of an operation it exposes; when the literal's direct use would need something the plugin enforces, such as a lock, a per-site key, a list's read-modify-write or paired keys; when the plugin's own code runs the same implementation, an exception taking the owner's ruling; and when its signature holds only scalars, WordPress and WooCommerce types and the plugin's models. One model serves each noun, a new capability of a concept being a method on its model; an open set of interchangeable behaviours is a `models/` interface behind register, get-one and get-all functions, never a function per variant; a setting gets no reader, and the decision it feeds is exposed instead; each state change gets an action; and nothing lock-bound or php-scoper-typed enters. Every other symbol is a member of a final class in `src/` named for its concept, `protected` wherever only its class uses it: the composition root, which holds the footprint, a component, `Options`, `Migrations` or another class that registers no hooks. No namespace or folder is named "Internal". Container definitions are composition and stay in `Plugin::get_definitions()`; a component takes the plugin's identity, or a service such as `Assets`, by constructor.
2. **Variants.** A component that exists only in one build variant is a child in a branch of its parent's `get_child_component_classes()`, never a second entry in `COMPONENTS`.
3. **Request context.** `is_admin()` is false on REST requests and true in admin-ajax ones, so an admin-only feature decides the request context in its callbacks, never in `should_load()`.
4. **Add-ons.** An add-on extends a base plugin only through the base plugin's contract. It reads a template-built base plugin's slug, name and version through that plugin's `{prefix}_get_plugin_*()` getters, asks its decision functions, such as `dws_tpl_should_show_greeting()`, and otherwise reads its options through `get_option()` on their contract names. It calls the base plugin's functions no earlier than `plugins_loaded`, or than the hook a function's docblock names, since the add-on may load first, and behind `function_exists()`, since a base plugin below its floors or with a partial build may stay active without defining them, and registers its extensions on the base plugin's `{root}/boot/succeeded`, which fires only once that plugin has booted, adding that callback before `plugins_loaded` reaches priority 15, where the base plugin boots, such as from its `functions.php`.
5. **Request reads.** An integer or boolean is read as `filter_var( wp_unslash( $_POST['key'] ?? '' ), FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE ) ?? $default` (`FILTER_VALIDATE_BOOL` for a boolean, where an absent key reads false, as an unchecked checkbox posts nothing), and a string through an `is_string()` guard and the field's sanitizer; never `filter_input()`, the `default` filter option or a cast on request input.
6. **Options and data.** Each setting is one autoloaded option, its key on `Options` and its default in stored form in `DEFAULTS`, which the first migration seeds, a release that adds settings seeds again, and its field reads; a feature's fields live in its folder's fields class. Outside the safeguards and the migration steps, options are read only through `Options`' typed readers, which translate nothing; a translated default belongs to the component that shows it, as the greeting's message does. Everything stored lives under `dws_tpl_`, or `_dws_tpl_` for hidden meta, but for scheduled jobs, whose hooks take the hook root as every hook does and whose Action Scheduler group is the bare `dws_tpl`. The footprint covers the prefix's options, transients and user meta; object meta needs a `meta_prefixes` entry per type, and anything else, such as a scheduled hook, its own entry.
7. **Safeguards** live in `Safeguards.php`, a final class whose `register()` hooks each safeguard as a static closure, which no code can remove by name, so a safeguard relaxes only through its own filter, through the protected `is_relaxed()` (`docs/recipes.md`); only what other classes read is public. The entry requires the class by path before the floor check, so it parses on PHP 7.4 and call only what their hook hands them, WordPress APIs present at the oldest WordPress the bootstrap package runs on and WooCommerce APIs present since 3.6, guarding anything newer with `function_exists()` or `method_exists()`. WordPress loads the plugin while WooCommerce is inactive, so a safeguard that calls WooCommerce is hooked from one closure, which `register()` runs on `woocommerce_loaded`, or at once where that has fired already, as on the request that activates the plugin; WooCommerce fires it, since 3.6, once every plugin has loaded. `phpstan.safeguards.neon` reads the class and the entry, with WooCommerce's stubs loaded, and checks the WordPress floor; review checks the WooCommerce floor, which no tool reads.
8. **Migrations** are append-only: a step converges from any partial state, verifies its writes and throws when one fails. A step names each stored name it touches, such as an option or meta key or a scheduled hook, and each default as a literal and reads stored rows directly, never through `Options` or a constant another class declares, so a later rename leaves what it does as it shipped; the seeding step alone reads `Options::DEFAULTS`.
9. **WordPress floor.** The plugin calls only APIs its `Requires at least` has, PHP's and JavaScript's alike: PHPStan's wp-compat reads the floor from that header, and CI runs the end-to-end suite on the floor's WordPress, which supplies the `@wordpress/*` scripts at runtime.

## Contract

The plugin's contract is the elements tagged `@api`, the hooks it fires, its REST routes and the names it stores, checks or documents: option, meta, user-option and capability names, comment types and statuses, admin-post actions and nonces, and constants a site defines. Each stored or checked name is written whole as a constant on the `src/` class that owns it, never as a global constant or an enum, and its literal is the contract. Breaking the contract ships as the next major. Every other function, constant and class, `src/` included, is internal and changes in any release.

## Style

- Every PHP file opens with `<?php declare( strict_types=1 );`, uses `array()`, `final` classes, `protected` over `private` and `#[\Override]` on overrides, and groups class members in `// region NAME` blocks outside `tests/`.
- Docblocks open with one third-person sentence naming the role, such as "Returns …", plus a second paragraph only for a non-obvious why; property and constant docblocks open with "The …", constructors read "Constructor.", overrides read `{@inheritDoc}`. Every other function docblock ends with `@return`, `@return  void` included; tag values align at column 10, each `@param` stays on one line, and `@throws` reads "Thrown when …". Every public `do_action()` and `apply_filters()` carries a hook docblock.
- A hook the plugin fires is named `dws_plugin_template/area/name`, its root the slug with underscores for its dashes, as the framework roots its own, with `/` between levels and `_` between words, and its last level says what kind of hook it is: a past participle for an event, `before_` or `after_` for a position, an imperative for a scheduled hook (`dws_plugin_template/records/prune`), a predicate (`is_`, `has_`, `can_`, `should_`) for a boolean filter (`dws_plugin_template/data_export/should_restrict`), and the noun of the value for any other filter, plural for a list.
- Inline comments state a non-obvious why in one capitalized sentence, and every file describes the present, never the change; a `phpcs:ignore` names its sniff and gives a reason.
- Markdown keeps one line per paragraph, sentence-case headings and an impersonal, present-tense voice.
- Commit subjects are capitalized imperatives of 72 characters or fewer, with no `type(scope):` prefix and no trailing period. The body says what was wrong and why before what changed, and an agent-assisted commit ends with `Assisted-by: <agent>:<model-id>`, never `Co-Authored-By`.

## Limitations

- The fill-in pushes with the workflow's own token, which GitHub refuses for workflow changes, so it never edits `.github/workflows/`; the workflows read the slug from `composer.json`'s `extra.text-domain`.
- Integration and end to end share the tests site, so a row that changes shared data in a separate process restores it there, or flushes the parent's object cache in `#[AfterClass]`.
- The release zip carries the TypeScript and SCSS sources beside the built files, since a plugin's repository may be private and the zip is then the only place its users find them.
