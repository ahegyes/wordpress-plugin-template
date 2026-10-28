# Security policy

## Reporting a vulnerability

Report it privately through [GitHub's security advisory form](https://github.com/ahegyes/wordpress-plugin-template/security/advisories/new), never in a public issue. The first response comes within 7 days.

## Scope

In scope: the plugin's PHP, JavaScript and CSS, its prefixing configuration (`scoper.inc.php`) and its CI workflows.

Out of scope, reported upstream instead:
- plugins generated from this repository, to their maintainers;
- the wp-framework packages, to `ahegyes/wordpress-framework`;
- WordPress, WooCommerce and the other Composer and npm dependencies, to their projects. Composer refuses to resolve a dependency version with a known advisory through `roave/security-advisories`, and the Supply-Chain Audit workflow fails on one.
