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

`dws-plugin-template.php` is the entry point and does only four things, in order:

1. **Guards** — `ABSPATH`, then bail if `vendor/autoload.php` is missing.
2. **Requirements** — `Bootstrap\Requirements\check_requirements()` returns `true` or a `WP_Error`; on error it renders an admin notice via `Bootstrap\Notice\output_requirements_error()` and returns. The framework floors (PHP 8.5 / WP 7.0) are taken together with your plugin header's `Requires` values.
3. **Lifecycle hooks** — `PluginKernel::register_lifecycle_hooks( Plugin::get_instance() )` runs *during the include* (activation/deactivation hooks must be wired before `plugins_loaded`). It points WordPress's activation/deactivation at your `Installer`.
4. **Boot** — `add_action( 'plugins_loaded', 'dws_plugin_template_boot', 15 )`. Priority 15 runs after WooCommerce's default-priority init, so WC detection works.

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
| `config/container.php` | The PHP-DI definitions — the composition root. PHP-DI autowires constructor types, so only the classes needing a scalar / value-object / chosen identifier are declared here (the conditionals, the installer's store, the notice service, the WC backend). |

The kernel's optional **logger** is chosen lazily in `boot()`: `WooCommerceLogger` when WooCommerce is active (failures land in WooCommerce's log viewer), otherwise `AdminNoticeLogger` (a failed install/migration surfaces as a persistent admin notice). That is the install-failure UX — wired *before* the kernel runs, so it survives a boot that the installer stops.

## 4. Add your own Feature

1. Create `src/Feature/MyFeature.php` implementing `FeatureInterface`:
   - `public static function get_conditional_classes(): array` — return `array()` for always-on, or class-strings of `ConditionalInterface` implementations to gate it.
   - `public function get_component_classes(): array` — the component class-strings the kernel resolves.
2. Create your component(s) under `src/Component/`. A component that hooks WordPress implements `HookableInterface` (`register_hooks()`); one that needs setup before hooks implements `InitializableInterface` (`initialize()`). The framework also offers `EnabledInterface` (a post-resolution per-component on/off gate) and `CompositeComponentInterface` (a component that owns child components as a kernel-dispatched subtree) — unused in this reference, but there when you need them.
3. Register the Feature in `Plugin::get_feature_classes()`.
4. If a class needs a constructor argument PHP-DI can't autowire (a scalar, a value object, a chosen store), add a definition in `config/container.php`. Everything else autowires.

The `tests/Unit/PluginBootTest.php` boot smoke is the pattern for proving your additions boot.

## 5. WooCommerce, or not

This reference is **WooCommerce-flavored**. For a generic (non-WooCommerce) plugin, remove every WooCommerce touchpoint:

- the classes — `src/Feature/WooCommerceFeature.php`, `src/Component/ExampleSettings.php`, `src/Settings/ExampleWCSettingsPage.php`;
- the `WooCommerceFeature::class` entry in `Plugin::get_feature_classes()`;
- the `WooCommerceLogger` import + its branch in `Plugin::build_logger()`, leaving only the `AdminNoticeLogger` — **required**, not optional: that branch references a scoped WooCommerce class the next step removes;
- the `WPPluginActiveConditional`, `WooCommerceVersionConditional`, and `WooCommerceSettingsBackend` bindings in `config/container.php`;
- the deps — `ahegyes/wp-framework-woocommerce`, `wp-plugin/woocommerce`, and `php-stubs/woocommerce-stubs` from `composer.json` (and their `repositories` entries), then re-scope with `composer packages-update`;
- the `php-stubs/woocommerce-stubs` `scanFiles` entry in `phpstan.dist.neon`;
- the `woocommerce` mapping + activation in `.wp-env.tests.json`;
- the test — `tests/Integration/ExampleSettingsTest.php`.

## 6. Test it

```sh
composer test:unit          # the mock-WP boot smoke (no Docker)
composer test:integration   # boots in real WP (+ WooCommerce) via wp-env
composer lint:php            # PHPCS + PHPStan
```

The boot smoke proves the kernel boots the reference and that the WooCommerce Feature gates in and out correctly; the integration suite proves the requirements check, the boot, the admin notice, and the WooCommerce settings tab against real WordPress. `tests/Unit/GettingStartedTutorialTest.php` keeps this guide honest — it fails if a class named here is renamed without updating the docs.
