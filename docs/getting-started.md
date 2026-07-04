# Getting Started

This template **is** a working reference plugin built on the DWS framework. Out of the box it boots through the framework kernel, registers a welcome notice and a native WordPress settings page, and — when WooCommerce is active — adds a WooCommerce settings tab. Forking it gives you a plugin that already boots; you then rename it and replace the example pieces with your own.

If you only want the map, the [README](../README.md) has the file tree, the placeholder table, and the fork-reset checklist. This guide walks through *how the reference boots* and *how to extend it*.

## 1. Fork and scope

1. **Use this template** on GitHub (or copy the tree).
2. Run the fork's **Fill in scaffold** workflow (Actions → Fill in scaffold) — it substitutes every placeholder, resets the version and changelog, and pushes the result. Without Actions, substitute the placeholders manually per the [README's placeholder table](../README.md#placeholder-convention) — slug, constants, function prefix, namespace, display name, wp-env port.
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
| `src/Feature/GenericFeature.php` | A Feature with **no conditionals** — always boots. Owns `WelcomeNotice` + `ExampleWPSettings`. Proof the plugin works even without WooCommerce. |
| `src/Feature/WooCommerceFeature.php` | A Feature **gated** on `WPPluginActiveConditional` + `WooCommerceVersionConditional`. When WooCommerce is absent or too old it is pruned *before construction*, so its WooCommerce-coupled code never loads. Owns `ExampleSettings`. |
| `src/Installer/Installer.php` | `implements InstallerInterface`. Reads/writes the stored version through the `OptionsStore`, records a first-install timestamp marker on `install()`, runs an idempotent `update()` migration, and removes the plugin's whole option/meta footprint on `uninstall()`. |
| `src/Component/WelcomeNotice.php` | A `HookableInterface` component. Its `register_hooks()` adds an `admin_notices` callback that renders through core's `wp_admin_notice()`, gated on `manage_options`. |
| `src/Component/ExampleWPSettings.php` | A `HookableInterface` component that builds a `SettingsPage` descriptor (one section: a text field with a custom sanitize seam, a clamped number, a select) and registers it through `WordPressSettingsBackend` — a native options page under **Settings**, stored as one grouped `wp_options` row per section (`dws_plugin_template-general`). |
| `src/Component/ExampleSettings.php` | A `HookableInterface` component that builds a `SettingsPage` descriptor (one section, two fields) and registers it through `WooCommerceSettingsBackend` — the same descriptor shapes, a different backend. |
| `src/Settings/ExampleWCSettingsPage.php` | The one empty `DescriptorBackedWCSettingsPage` subclass WooCommerce recovers the tab by. |
| `config/footprint.php` | The single source of every persistent option/meta key the plugin writes. `config/container.php` wires the installer's uninstall footprint from it, and `uninstall.php`'s no-build fallback deletes exactly its lists — `tests/Unit/UninstallFallbackTest.php` fails if either side drifts. |
| `config/container.php` | The PHP-DI definitions — the composition root. Declares just the classes that need explicit construction: the conditionals, the installer's store, the notice service, the WC backend (step 4 explains when an entry is needed). |

The kernel's optional **logger** is built lazily in `boot()`: always an `AdminNoticeLogger` (a failed install/migration surfaces as a persistent admin notice), wrapped in a `CompositeLogger` together with `WooCommerceLogger` when WooCommerce is active (failures also land in WooCommerce's log viewer). That is the install-failure UX — wired *before* the kernel runs, so it survives a boot that the installer stops.

## 4. Add your own Feature

1. Create `src/Feature/MyFeature.php` implementing `FeatureInterface`:
   - `public static function get_conditional_classes(): array` — return `array()` for always-on, or class-strings of `ConditionalInterface` implementations to gate it.
   - `public function get_component_classes(): array` — the component class-strings the kernel resolves.
2. Create your component(s) under `src/Component/`. A component that hooks WordPress implements `HookableInterface` (`register_hooks()`); one that needs setup before its hooks run implements `InitializableInterface` (`initialize()`) — the kernel runs every component's `initialize()` before any `register_hooks()`, so a hook callback can safely reach a component in another Feature.
3. Register the Feature in `Plugin::get_feature_classes()`.
4. If a class needs a constructor argument PHP-DI can't autowire (a scalar, a value object, a chosen store), add a definition in `config/container.php`. Everything else autowires.
5. If a component persists options or user meta, add the keys to `config/footprint.php` — that one list feeds both the installer's uninstall and the no-build fallback, and `tests/Unit/UninstallFallbackTest.php` fails when the wiring drifts.

The `tests/Unit/PluginBootTest.php` boot smoke is the pattern for proving your additions boot.

The framework offers more component interfaces this reference deliberately doesn't demonstrate, there when a feature needs them: `EnabledInterface` (a post-resolution, per-component on/off gate — `is_enabled()`), `CompositeComponentInterface` (a component that owns child components as a kernel-dispatched subtree), and the `RenderableInterface` / `OutputtableInterface` markers (for components that produce markup). The reference stays minimal without them.

## 5. WooCommerce, or not

The reference demonstrates both settings backends side by side. For a generic (non-WooCommerce) plugin, remove every WooCommerce touchpoint — the native `ExampleWPSettings` demo stays as your settings example:

- the classes — `src/Feature/WooCommerceFeature.php`, `src/Component/ExampleSettings.php`, `src/Settings/ExampleWCSettingsPage.php`;
- in `src/Plugin.php`: the `WooCommerceFeature::class` entry (and its `use` import) in `get_feature_classes()`, and the `wc_get_logger` branch in `build_logger()` — with the `CompositeLogger` + `WooCommerceLogger` imports it leaves unused — so the method just returns the `AdminNoticeLogger`. **Required**, not optional: that branch references a scoped WooCommerce class the next step removes;
- in `config/container.php`: the `WPPluginActiveConditional` + `WooCommerceVersionConditional` (the WooCommerce Feature gates on both) and `WooCommerceSettingsBackend` bindings, and the `use` imports they leave unused — those three plus `ExampleWCSettingsPage` plus `Version`, which only the `WooCommerceVersionConditional` binding consumes;
- in `config/footprint.php`: the WooCommerce per-field option rows (the `$dws_plugin_template_wc_settings_options` list) — the WooCommerce settings demo is what wrote them;
- the deps from `composer.json` — `ahegyes/wp-framework-woocommerce`, `wp-plugin/woocommerce`, `php-stubs/woocommerce-stubs`. Only `ahegyes/wp-framework-woocommerce` has a dedicated `repositories` VCS entry to delete; `wp-plugin/woocommerce` is served by the shared `repo.wp-packages.org` composer repo — keep it, it serves any `wp-plugin/*` / `wp-theme/*` dev dependency — and `php-stubs/woocommerce-stubs` is on Packagist (no entry). Re-scope with `composer packages-update` afterwards;
- the `php-stubs/woocommerce-stubs` `scanFiles` entry in `phpstan.dist.neon`;
- in `.wp-env.json`, the `woocommerce` plugin mapping and the WooCommerce half of `afterStart` (its `wp plugin activate woocommerce` and `wp wc hpos enable`), leaving the `dws-plugin-template` activation — and the same `woocommerce` mapping in `.wp-env.belowfloor.json`;
- the WooCommerce-coupled tests. Delete the two WooCommerce-only files — `tests/Integration/ExampleSettingsTest.php` and `tests/Unit/WooCommerceGateTest.php`. In the rest, drop the WooCommerce `use` imports, the `WooCommerceFeature` / `ExampleSettings` `#[UsesClass]` attributes, the `dws_plugin_template_enable_feature` / `dws_plugin_template_greeting` seeds and assertions in the uninstall tests, and any WooCommerce-only test method — `tests/Unit/PluginBootTest.php` (drop its WooCommerce-active boot test), `tests/Unit/InstallerLifecycleTest.php`, `tests/Integration/UninstallTest.php`, `tests/Unit/GettingStartedTutorialTest.php` (drop the `WooCommerceFeature` / `ExampleSettings` class checks, the `ExampleWCSettingsPage` file assertion, and the `section_5_enumerates_every_woocommerce_touchpoint` test itself), and `tests/Integration/PluginBootTest.php`.

Then confirm a green suite — `composer lint:php`, `composer test:unit`, and `composer test:integration` all pass — proving no dangling WooCommerce reference remains. This list is test-enforced: `section_5_enumerates_every_woocommerce_touchpoint` in `tests/Unit/GettingStartedTutorialTest.php` fails whenever a WooCommerce reference lives in a `src/` or `config/` file this section does not name.

## 5b. WooCommerce-only? Remove the generic settings demo

A WooCommerce-focused fork that keeps `ExampleSettings` as its settings example can drop the native-WordPress demo instead:

- delete `src/Component/ExampleWPSettings.php`;
- in `src/Feature/GenericFeature.php`: the `ExampleWPSettings::class` entry and its `use` import;
- in `config/footprint.php`: the grouped section row (the `$dws_plugin_template_wp_settings_options` list) — the native settings demo is what wrote it;
- the tests — delete `tests/Integration/ExampleWPSettingsTest.php`; in the rest, drop the `ExampleWPSettings` `use` imports and `#[UsesClass]` attributes, its boot assertion in `tests/Unit/PluginBootTest.php`, the `dws_plugin_template-general` seeds and assertions in `tests/Unit/InstallerLifecycleTest.php` + `tests/Integration/UninstallTest.php`, and in `tests/Unit/GettingStartedTutorialTest.php` the `ExampleWPSettings` class check and the `section_5b_enumerates_every_generic_settings_demo_touchpoint` test itself.

This list is test-enforced the same way: `section_5b_enumerates_every_generic_settings_demo_touchpoint` fails whenever a generic-settings-demo reference lives in a `src/` or `config/` file this section does not name.

## 6. Test it

```sh
composer test:unit          # the mock-WP boot smoke (no Docker)
composer test:integration   # boots in real WP (+ WooCommerce) via wp-env
composer lint:php            # PHPCS + PHPStan
```

The boot smoke proves the kernel boots the reference and that the WooCommerce Feature gates in and out correctly; the integration suite proves the requirements check, the boot, the welcome notice, the native settings page, and the WooCommerce settings tab against real WordPress. `tests/Unit/GettingStartedTutorialTest.php` keeps this guide honest — it fails if a class named here is renamed without updating the docs, or if §5 / §5b stop covering a demo touchpoint.

### The Unit test harness

The Unit suite runs with **no WordPress loaded**. `tests/Unit/bootstrap-wp-stubs.php` defines the WordPress functions the boot path calls — each shim guarded by `function_exists()`, so wp-env's real WordPress wins when the same file loads in an Integration run — and each shim records its call into `tests/Unit/WordPressStubState.php`, a static recorder the tests assert against (`has_object_action()`, `option_array()`, …) and `reset()` in `setUp()`.

To unit-test a component that calls a WordPress function the harness doesn't know yet:

1. add a guarded shim to `bootstrap-wp-stubs.php` that writes into `WordPressStubState` (a new static property + a line in `reset()` when it needs state — see `current_user_can()` / `$user_can` for a controllable example);
2. assert on the recorded state, not on output.

Keep shims dumb — a recorder, not a WordPress re-implementation. The moment a test needs real WordPress *behavior* (option autoloading, capability resolution, hook firing order, real sanitizers), write an Integration test instead: it boots actual WordPress in wp-env, where none of this needs faking. The `tests/Unit/entrypoint-wp-stubs.php` sibling carries the extra shims only the entrypoint tests reach (the requirements chain's `WP_Error`, `plugin_basename()`).
