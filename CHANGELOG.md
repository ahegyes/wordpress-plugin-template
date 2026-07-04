# Changelog

All notable changes to this plugin are documented in this file. Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), versioning follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Pending entries live in [`changelog/`](./changelog) — add via `composer changelog:add`. Aggregate into a release with `composer changelog:write`, which also syncs `readme.txt`.

<!-- Start changelog -->

## 2.0.0 - unreleased

### Added
- Initial release.
- Per-plugin scoped dependencies (framework + PHP-DI bundled under a plugin-specific namespace prefix).
- Reference plugin: a two-Feature graph (always-on `GenericFeature` + gated `WooCommerceFeature`), a versioned `Installer` with a single-sourced uninstall footprint, a `WelcomeNotice` component, a native WordPress settings page (`ExampleWPSettings`), and a WooCommerce settings tab (`ExampleSettings`).

<!-- End changelog -->
