# Architecture

This document describes how the plugin loads, boots, stores its data and leaves a site, and where each kind of code lives.

## Loading

`dws-plugin-template.php` is the only file WordPress includes, and it parses on PHP 7.4 so that a site below the floors gets a notice instead of a fatal error; `phpstan.safeguards.neon` analyzes it with `Safeguards.php` at PHP 7.4 and the oldest WordPress the bootstrap package runs on:

1. It defines `DWS_TPL_FILE` and registers `languages/`, which WordPress skips for a network-activated plugin.
2. It requires `Safeguards.php` and registers its safeguards, whose restrictions hold on any runtime that parses the plugin: below the floors, on a partial deploy and when the boot fails.
3. Inside one `try`, it requires `vendor-prefixed/ahegyes/wp-framework-bootstrap/load.php`, registers the GitHub updater, then checks the `Requires PHP` and `Requires at least` headers. The updater comes first, so a release that lowers the floors still reaches a site below them. On a site below a floor, WordPress's own notice shows and nothing past the safeguards loads.
4. It requires the autoloader and `functions.php` once the built blocks and assets are present. A missing bootstrap package, build tree or required file throws, which local and development environments rethrow, while every other environment shows users who can activate plugins a notice that the build is incomplete.

`functions.php` defines the `dws_tpl_plugin()` accessor, requires every `includes/*.php` in name order except those whose names start with `_`, then, from static closures, boots the plugin on `plugins_loaded` at priority 15, after WooCommerce and Action Scheduler have loaded, declares the plugin's WooCommerce feature compatibility and registers the deactivation callback.

## Booting

`src/Plugin.php` is the composition root. Its constructor builds the plugin's identity from its entry file, slug and prefix, then the logger, the scheduler and the framework's `PluginBoot`, which each take the identity, and `PluginBoot` runs the composition and reports its outcome:

- The scheduler registers its hooks first, so the plugin's recurring jobs keep their schedules while the plugin is blocked.
- `WooCommerceRequirement` checks WooCommerce against the `WC requires at least` header; without it, the boot is `blocked`, `dws_plugin_template/boot/blocked` fires, and users who can activate plugins see why the plugin stays off.
- The migration runner registers, and runs pending steps on `wp_loaded`.
- The kernel builds each class in `Plugin::COMPONENTS` through a PHP-DI container and calls its `register_hooks()`; a composite adds its children, and a conditional component loads only when its static `should_load()` returns true.
- Once every component has registered, the boot is `booted` and `dws_plugin_template/boot/succeeded` fires. A throw anywhere in the composition makes it `failed`: `dws_plugin_template/boot/failed` fires, the failure is logged, and administrators see a notice, while local and development environments rethrow it.

The request that activates the plugin does not boot it, because WordPress includes the plugin's file after `plugins_loaded`; its post types, routes and migrations first run on the next request.

## Where code lives

- `src/` holds the plugin's classes, each final and named for its concept: the composition root; the components, which implement the framework's `ComponentInterface` and add their hooks in `register_hooks()`; and classes that register no hooks, `Options` and `Migrations`. A component takes the plugin's identity and ungated services in its constructor, such as the logger, the scheduler, `AdminNotices` or `Assets`, which `Plugin::get_definitions()` provides; a component that builds a settings page, product data tab or form passes the identity to it.
- `includes/` extends `functions.php` with more of the plugin's `@api` functions and constants, grouped by topic: the slug, name and version getters a peer plugin reads (`metadata.php`). The global namespace holds nothing else, so the plugin supports only what it tags as API.
- `blocks/src/` and `assets/src/` hold the TypeScript and SCSS sources, which `npm run build` compiles into `blocks/build/` and `assets/build/`, and each block's `render.php`, which it copies beside the block's build and WordPress runs on each request, so the block's text follows the visitor's language; the framework's `Assets` reads the `*.asset.php` file wp-scripts writes beside each build, and `src/Blocks.php` registers the blocks from `blocks/build/blocks-manifest.php`.
- `models/` and `templates/` are empty extension points, for classes on Composer's classmap and for template partials.

## Data

- **Options** are `Options`' constants, read only through its typed readers, which guard each stored value, fall back to the key's default and translate nothing. A translated default belongs to the component that shows it, after `init`: the greeting's field shows its default message as the placeholder and stores an empty value, which the greeting reads as that message in each visitor's language, so no save stores the admin's language.
- **Migrations** are the append-only steps `Migrations::get_steps()` returns, each a protected static of the class. The framework's runner runs each pending step once per site on `wp_loaded`, behind a lock, and records the revision reached in `dws_tpl_revision`. A step converges from any partial state and throws when a write fails, so a failed step runs again on a later request.
- **The footprint**, `Plugin::FOOTPRINT`, names every kind of data the plugin stores: option and user meta prefixes, network options, scheduled hooks and Action Scheduler groups. Options, transients and user meta under the `dws_tpl_` prefix are covered; meta on posts, terms, comments or orders needs a `meta_prefixes` entry per object type, and data under another name its own entry, which no check enforces beyond the option constants `FootprintTest` covers.
- **Deactivation** unschedules every hook in the footprint's `cron_hooks` and `as_hooks` on the current site.
- **Uninstall** runs `uninstall.php`, which calls the framework's uninstall engine over the footprint, with WooCommerce's order meta types, on every site whose `dws_tpl_delete_data` setting consents; a site that never consented keeps its data. Below the floors or with an incomplete build, a web request deletes the files, keeps the data and logs why, while WP-CLI fails and keeps both for a rerun. When the engine cannot remove everything, a web request ends with its reason before WordPress deletes the files, and the consent stays for the next uninstall.

## Prefixing and distribution

Every development install runs php-scoper through wordpress-configs, which copies the framework, PHP-DI and their dependencies into `vendor-prefixed/` under `DeepWebSolutions\PluginTemplate\Scoped\`, so each plugin carries its own copy and never meets another plugin's. The release workflow builds `dws-plugin-template.zip` from a production install, checks it with Plugin Check, the archive and header checks, activates it on a site and runs the end-to-end suite against it. A pull request rehearses the same build without publishing. A tag publishes a GitHub release, which the bundled updater offers through WordPress's own update check while the `Update URI` header points at the repository and the repository is public, since the check calls GitHub's API without authentication.
