# Getting Started

This template **is** a working reference plugin built on the DWS framework. Out of the box it boots through the framework kernel, registers an admin notice, and — when WooCommerce is active — adds a WooCommerce settings tab. Forking it gives you a plugin that already boots; you then rename it and replace the example pieces with your own.

If you only want the map, the [README](../README.md) has the file tree, the placeholder table, and the fork-reset checklist. This guide walks through *how the reference boots* and *how to extend it*.

## 1. Fork and scope

1. **Use this template** on GitHub (or copy the tree).
2. Substitute the placeholders per the [README's placeholder table](../README.md#placeholder-convention) — slug, constants, function prefix, namespace, display name, wp-env port.
3. Install + scope the framework:

   ```sh
   composer packages-install   # resolves deps and runs php-scoper → dependencies/
   ```

   Each fork ships its **own scoped copy** of the framework under `…\PluginTemplate\Scoped\…`, so two DWS plugins on one site can never collide on a framework version. You reference framework classes through that prefix (see any `use` statement in `src/Plugin.php`).

## 2. How it boots

`dws-plugin-template.php` is the entry point. During the plugin include it runs, in order:

1. **Build-artifact preflight** — `composer packages-install` produces both the Composer autoloader (`vendor/autoload.php`) and the scoped framework under `dependencies/`. If either is missing — a fresh fork before its first install, or a source zip shipped without them — the entry registers an `admin_notices` callback that points you at `composer packages-install` and returns. That notice uses no framework class, because the scoped framework is exactly what's absent.
2. **Constants** — defines `DWS_PLUGIN_TEMPLATE_FILE` and `DWS_PLUGIN_TEMPLATE_VERSION`.
3. **Requirements** — loads *only* the scoped `bootstrap` package (PHP 5.6-safe, so it compiles even below the framework's PHP 8.5 floor) and calls `Bootstrap\Requirements\check_requirements()`. It returns `true` or a `WP_Error`; on error it renders an admin notice via `Bootstrap\Notice\output_requirements_error()` and returns. The floors (PHP 8.5 / WP 7.0) are taken together with your plugin header's `Requires` values. Loading `bootstrap` *before* the full autoloader is deliberate: the rest of the scoped framework uses syntax that won't compile on an unsupported PHP, so the requirements notice has to be reachable without it.
4. **Full autoload** — only once requirements pass does the entry require `vendor/autoload.php` (the whole scoped framework) and `functions.php`.
5. **Lifecycle hooks** — `PluginKernel::register_lifecycle_hooks( Plugin::get_instance() )` runs *during the include* (activation/deactivation hooks must be wired before `plugins_loaded`). It points WordPress's activation/deactivation at your `Installer`.
6. **Boot** — `add_action( 'plugins_loaded', 'dws_plugin_template_boot', 15 )`. WooCommerce defines `WC_VERSION` as it loads and hooks its own `plugins_loaded` init at priority -1; booting at 15 runs after that, so the WooCommerce Feature reliably detects WooCommerce.

On `plugins_loaded`, `Plugin::boot()` runs the kernel **once** (it stores the kernel, so a second `boot()` is a no-op). The kernel then:

1. runs the **installer** version check (install on a fresh site, `update()` on a version bump) — and *stops the boot* if it fails, surfacing the failure through the logger;
2. gates each **Feature** on its conditionals *before constructing it*;
3. resolves the surviving Features' **components** from the container;
4. calls `initialize()` on every component, then `register_hooks()` on every component.

## 3. The pieces

| File | Role |
|---|---|
| `src/Plugin.php` | `final class Plugin implements PluginInterface`. The singleton: builds the PHP-DI container, declares the Feature classes, exposes the installer, and (in `boot()`) wires admin-notice rendering and picks the kernel's logger. |
| `src/Feature/GenericFeature.php` | A Feature with **no conditionals** — always boots. Owns the `AdminNotice` demo component. Proof the plugin works even without WooCommerce. |
| `src/Feature/WooCommerceFeature.php` | A Feature **gated** on `WPPluginActiveConditional` + `WooCommerceVersionConditional`. When WooCommerce is absent or too old it is pruned *before construction*, so its WooCommerce-coupled code never loads. Owns `ExampleSettings`. |
| `src/Installer/Installer.php` | `implements InstallerInterface`. Reads/writes the stored version through the `OptionsStore`, records a first-install timestamp marker on `install()`, runs an idempotent `update()` migration, and removes the plugin's whole option/meta footprint on `uninstall()`. |
| `src/Component/AdminNotice.php` | A `HookableInterface` component. Its `register_hooks()` adds an `admin_notices` callback. |
| `src/Component/ExampleSettings.php` | A `HookableInterface` component that builds a `SettingsPage` descriptor (one section, two fields) and registers it through `WooCommerceSettingsBackend`. |
| `src/Settings/ExampleWCSettingsPage.php` | The one empty `DescriptorBackedWCSettingsPage` subclass WooCommerce recovers the tab by. |
| `config/container.php` | The PHP-DI definitions — the composition root. Declares just the classes that need explicit construction: the conditionals, the installer's store, the notice service, the WC backend (step 4 explains when an entry is needed). |

The kernel's optional **logger** is chosen lazily in `boot()`: `WooCommerceLogger` when WooCommerce is active (failures land in WooCommerce's log viewer), otherwise `AdminNoticeLogger` (a failed install/migration surfaces as a persistent admin notice). That is the install-failure UX — wired *before* the kernel runs, so it survives a boot that the installer stops.

## 4. Add your own Feature

1. Create `src/Feature/MyFeature.php` implementing `FeatureInterface`:
   - `public static function get_conditional_classes(): array` — return `array()` for always-on, or class-strings of `ConditionalInterface` implementations to gate it.
   - `public function get_component_classes(): array` — the component class-strings the kernel resolves.
2. Create your component(s) under `src/Component/`. A component that hooks WordPress implements `HookableInterface` (`register_hooks()`); one that needs setup before its hooks run implements `InitializableInterface` (`initialize()`) — the kernel runs every component's `initialize()` before any `register_hooks()`, so a hook callback can safely reach a component in another Feature.
3. Register the Feature in `Plugin::get_feature_classes()`.
4. If a class needs a constructor argument PHP-DI can't autowire (a scalar, a value object, a chosen store), add a definition in `config/container.php`. Everything else autowires.

The `tests/Unit/PluginBootTest.php` boot smoke is the pattern for proving your additions boot.

The framework offers more component interfaces this reference deliberately doesn't demonstrate, there when a feature needs them: `EnabledInterface` (a post-resolution, per-component on/off gate — `is_enabled()`), `CompositeComponentInterface` (a component that owns child components as a kernel-dispatched subtree), and the `RenderableInterface` / `OutputtableInterface` markers (for components that produce markup). The reference stays minimal without them.

## 5. WooCommerce, or not

This reference is **WooCommerce-flavored**. For a generic (non-WooCommerce) plugin, remove every WooCommerce touchpoint:

- the classes — `src/Feature/WooCommerceFeature.php`, `src/Component/ExampleSettings.php`, `src/Settings/ExampleWCSettingsPage.php`;
- the `WooCommerceFeature::class` entry (and its `use` import) in `Plugin::get_feature_classes()`;
- the `WooCommerceLogger` import + its branch in `Plugin::build_logger()`, leaving only the `AdminNoticeLogger` — **required**, not optional: that branch references a scoped WooCommerce class the next step removes;
- the `WPPluginActiveConditional` + `WooCommerceVersionConditional` (the WooCommerce Feature gates on both) and `WooCommerceSettingsBackend` bindings in `config/container.php`, and the `use` imports they leave unused (those three plus `ExampleWCSettingsPage`);
- the deps from `composer.json` — `ahegyes/wp-framework-woocommerce`, `wp-plugin/woocommerce`, `php-stubs/woocommerce-stubs`. Only `ahegyes/wp-framework-woocommerce` has a dedicated `repositories` VCS entry to delete; `wp-plugin/woocommerce` is served by the shared `repo.wp-packages.org` composer repo — keep it, it serves any `wp-plugin/*` / `wp-theme/*` dev dependency — and `php-stubs/woocommerce-stubs` is on Packagist (no entry). Re-scope with `composer packages-update` afterwards;
- the `php-stubs/woocommerce-stubs` `scanFiles` entry in `phpstan.dist.neon`;
- in `.wp-env.json`, the `woocommerce` plugin mapping and the WooCommerce half of `afterStart` (its `wp plugin activate woocommerce` and `wp wc hpos enable`), leaving the `dws-plugin-template` activation;
- the WooCommerce-coupled tests. Delete the two WooCommerce-only files — `tests/Integration/ExampleSettingsTest.php` and `tests/Unit/WooCommerceGateTest.php`. In the rest, drop the WooCommerce `use` imports, the `WooCommerceFeature` / `ExampleSettings` `#[UsesClass]` attributes, and any WooCommerce-only test method — `tests/Unit/PluginBootTest.php` (drop its WooCommerce-active boot test), `tests/Unit/InstallerLifecycleTest.php`, `tests/Unit/GettingStartedTutorialTest.php` (drop the `WooCommerceFeature` / `ExampleSettings` class checks and the `ExampleWCSettingsPage` file assertion), and `tests/Integration/PluginBootTest.php`.

Then confirm a green suite — `composer lint:php`, `composer test:unit`, and `composer test:integration` all pass — proving no dangling WooCommerce reference remains.

## 6. Test it

```sh
composer test:unit          # the mock-WP boot smoke (no Docker)
composer test:integration   # boots in real WP (+ WooCommerce) via wp-env
composer lint:php            # PHPCS + PHPStan
```

The boot smoke proves the kernel boots the reference and that the WooCommerce Feature gates in and out correctly; the integration suite proves the requirements check, the boot, the admin notice, and the WooCommerce settings tab against real WordPress. `tests/Unit/GettingStartedTutorialTest.php` keeps this guide honest — it fails if a class named here is renamed without updating the docs.
