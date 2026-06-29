# Security policy

## Reporting a vulnerability

Report privately via [GitHub's security advisory form](https://github.com/ahegyes/wordpress-plugin-template/security/advisories/new). Do not open a public issue. Initial response within 7 days.

## Scope

In scope:
- This template's PHP source, scoping pipeline (`scoper.inc.php`), CI configuration.

Out of scope:
- Plugins forked from this template — report to the fork's maintainer.
- WordPress core, WooCommerce, or upstream Composer / npm dependencies — report upstream. The transitive `roave/security-advisories` constraint already fails `composer packages-install` on any known CVE in the dep graph.
