=== DWS Plugin Template ===
Contributors: ahegyes
Tags: dws, framework, template
Tested up to: 7.1
Stable tag: 2.0.0
Requires at least: 7.1
Requires PHP: 8.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WordPress plugins on the wp-framework packages start here, from one tested anatomy, so all are built alike and a fix found in one applies to all.

== Description ==

WordPress plugins on the wp-framework packages start here, from one tested anatomy, so all are built alike and a fix found in one applies to all.

== Installation ==

1. Upload the plugin zip via Plugins → Add New → Upload Plugin.
2. Activate the plugin.

The plugin boots only with its scoped framework present under `vendor-prefixed/`. A distribution zip must bundle that directory; a source checkout does not have it (`vendor-prefixed/` is gitignored), so run `composer packages-install` (see the GitHub README) before activating, or the plugin shows a notice that its files are missing instead of booting.

== Changelog ==

<!-- Start changelog -->

## 2.0.0 - unreleased

### Added
- Initial release.

<!-- End changelog -->

[See the full changelog on GitHub.](https://github.com/ahegyes/wordpress-plugin-template/blob/trunk/CHANGELOG.md)
