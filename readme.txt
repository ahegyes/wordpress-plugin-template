=== DWS Plugin Template ===
Contributors: ahegyes
Tags: dws, framework, template
Tested up to: 7.0
Stable tag: 2.0.0
Requires at least: 7.0
Requires PHP: 8.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Starting point for WordPress plugins built on the DWS framework.

== Description ==

Scaffold for WordPress plugins built on the DWS framework. Each fork ships its own scoped copy of the framework + PHP-DI so plugins can't collide on framework versions.

Ships as a working reference: a two-Feature graph (always-on + WooCommerce-gated), a versioned installer with a single-sourced uninstall footprint, a welcome notice, a native WordPress settings page, and a WooCommerce settings tab. See the GitHub README for the placeholder convention + fork setup.

== Installation ==

1. Upload the plugin zip via Plugins → Add New → Upload Plugin.
2. Activate the plugin.

The plugin boots only with its scoped framework present under `dependencies/`. A distribution zip must bundle that directory; a source checkout does not have it (`dependencies/` is gitignored), so run `composer packages-install` (see the GitHub README) before activating, or the plugin shows a setup notice instead of booting.

== Changelog ==

<!-- Start changelog -->

## 2.0.0 - unreleased

### Added
- Initial release.
- Per-plugin scoped dependencies (framework + PHP-DI bundled under a plugin-specific namespace prefix).
- Reference plugin: a two-Feature graph (always-on `GenericFeature` + gated `WooCommerceFeature`), a versioned `Installer` with a single-sourced uninstall footprint, a `WelcomeNotice` component, a native WordPress settings page (`ExampleWPSettings`), and a WooCommerce settings tab (`ExampleSettings`).

<!-- End changelog -->

[See the full changelog on GitHub.](https://github.com/ahegyes/wordpress-plugin-template/blob/trunk/CHANGELOG.md)
