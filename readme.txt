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

Ships with a demo `AdminNotice` component as proof the framework's lifecycle dispatch is wired. See the GitHub README for the placeholder convention + fork reset checklist.

== Installation ==

1. Upload the plugin zip via Plugins → Add New → Upload Plugin.
2. Activate the plugin.

== Changelog ==

<!-- Start changelog -->

## 2.0.0 - unreleased

### Added
- Initial release.
- Per-plugin scoped dependencies (framework + PHP-DI bundled under a plugin-specific namespace prefix).
- Demo `AdminNotice` component implementing `HookableInterface`.

<!-- End changelog -->

[See the full changelog on GitHub.](https://github.com/ahegyes/wordpress-plugin-template/blob/trunk/CHANGELOG.md)
