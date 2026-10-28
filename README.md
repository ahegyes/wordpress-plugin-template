# DWS Plugin Template

WordPress plugins on the wp-framework packages start here, from one tested anatomy, so all are built alike and a fix found in one applies to all.

The template is itself a working WooCommerce plugin: it boots the framework's composition, holds a settings tab, a feature gated on one of its settings, a block, migrations, safeguards and an uninstall footprint, and every suite and release check passes on it. `docs/ARCHITECTURE.md` describes that anatomy and `docs/recipes.md` the changes most plugins make to it.

## Starting a plugin

Create the plugin's repository from this template with GitHub's **Use this template**, then run the **Fill in scaffold** workflow there (Actions → Fill in scaffold → Run workflow) on the default branch, with these inputs:

| Input | Example | What it sets |
| --- | --- | --- |
| `plugin-name` | `Acme Orders for WooCommerce` | the display name in the plugin header, the strings and the docs |
| `plugin-slug` | `acme-orders-for-woocommerce` | the text domain and the names of the entry file and the release zip |
| `php-namespace` | `AcmeOrders` | the namespace segment after `DeepWebSolutions\` |
| `prefix` | `dws_aowc` | the prefix of every global function, constant, option and hook |
| `wp-env-port` | `8821` | the development site's port; the tests site takes the next one |

The workflow:
- renames the entry file and replaces the template's names, prefix and ports in every tracked file outside `.github/workflows/`, which read the slug from `composer.json` instead, since GitHub refuses the workflow's own token for workflow changes;
- resets the version to 0.1.0 and the changelog to one unreleased entry;
- makes `README.scaffold.md` the plugin's README, which lists the template's demo and what removing each part takes;
- deletes the disabled example include and refreshes the Composer lock's content hash;
- fails when any name or port of the template is left, and otherwise pushes one commit to the branch it ran on.

## Naming

- A WooCommerce plugin's display name reads `X for WooCommerce`, as WooCommerce's trademark guidelines ask, and its slug ends in `-for-woocommerce`.
- The prefix is `dws_` and one segment of lowercase letters and digits, such as `dws_lpmwc`, so no plugin's prefix starts with another's; a WooCommerce plugin's segment ends in `wc`.
- The namespace is flat: `DeepWebSolutions\<Name>\`, with no segment in between.
- The Composer package is `ahegyes/wc-<name>` for a WooCommerce plugin and `ahegyes/wp-<name>` otherwise, while the repository keeps the plugin's slug; the fill-in sets `ahegyes/<slug>`, which the author changes by hand.
- Each plugin takes its own pair of wp-env ports, apart from 8888 and 8889, which wp-env uses by default.

## Working on the template

Development needs PHP and Composer at the entry file's `Requires PHP` floor or later, Node.js and npm at `package.json`'s `engines`, Docker for `@wordpress/env`, and a POSIX shell for `npm run start`.

Install the dependencies, prefix the framework into `vendor-prefixed/`, build the scripts and start the development site on <http://localhost:8811>:

```sh
npm run setup
```

Run the manifest and changelog checks, PHPCS, PHPStan, and the Unit and Integration suites on the tests site at <http://localhost:8812>:

```sh
composer quality-check
```

Run the end-to-end suite:

```sh
npm run test:e2e
```

Lint the scripts, styles and types:

```sh
npm run lint:scripts && npm run lint:styles && npm run lint:types
```

Every pull request also rehearses the fill-in with sample inputs and gates the filled-in plugin, and rehearses the release on the built zip. `tests/README.md` describes the suites and CI, `AGENTS.md` the conventions, `CONTRIBUTING.md` the changelog, and `SECURITY.md` how to report a vulnerability.

## License

GPL-2.0-or-later.
