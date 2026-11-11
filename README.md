# DWS Plugin Template

WordPress plugins on the wp-framework packages start here, from one tested anatomy, so all are built alike and a fix found in one applies to all.

The template is itself a working WooCommerce plugin: it boots the framework's composition, holds a settings tab, a feature gated on one of its settings, a block, migrations, an empty safeguards slot and an uninstall footprint, and every suite and release check passes on it. `docs/ARCHITECTURE.md` describes that anatomy and `docs/recipes.md` the changes most plugins make to it.

## Starting a plugin

Create the plugin's repository from this template with GitHub's **Use this template**, then run the **Fill in scaffold** workflow there (Actions → Fill in scaffold → Run workflow) on the default branch, with these inputs:

| Input | Example | What it sets |
| --- | --- | --- |
| `plugin-name` | `Acme Orders for WooCommerce` | the display name in the plugin header, the strings and the docs |
| `description` | `Acme Orders keeps every order a store takes in one place.` | the entry's `Description` header, `composer.json`'s and `package.json`'s `description` and the README's opening sentence; set the repository's GitHub description to the same sentence |
| `plugin-slug` | `acme-orders-for-woocommerce` | the text domain and the names of the entry file and the release zip |
| `php-namespace` | `AcmeOrders` | the namespace segment after `DeepWebSolutions\` |
| `prefix` | `dws_aowc` | the prefix of every global function, constant, option and hook |
| `wp-env-port` | `8831` | the development site's port; the tests and multisite sites take the next two |

The workflow:
- renames the entry file and replaces the template's names, description, prefix and ports in every tracked file outside `.github/workflows/`, which read the slug from `composer.json` instead, since GitHub refuses the workflow's own token for workflow changes;
- makes `README.scaffold.md` the plugin's README, which lists the template's demo and what removing each part takes;
- deletes the disabled example include, the safeguard recipe's test and any changelog fragments, and refreshes the Composer lock's content hash;
- fails when any name, description or port of the template is left, and otherwise pushes one commit to the branch it ran on.

Then turn on private vulnerability reporting and Dependabot alerts in the new repository's settings: its `SECURITY.md` sends reports through the first and development-tool advisories to the second, and a repository made from a template copies none of the template's settings.

## Naming

- A WooCommerce plugin's display name reads `X for WooCommerce`, as WooCommerce's trademark guidelines ask, and its slug ends in `-for-woocommerce`.
- The prefix is `dws_` and one segment of up to 22 lowercase letters and digits, such as `dws_lpmwc`, so no plugin's `{prefix}_` starts with another's and the template's longest constants still pass PHPCS; the framework's admin notices refuse `dws_network` and `dws_hidden`; a WooCommerce plugin's segment ends in `wc`.
- The namespace is flat: `DeepWebSolutions\<Name>\`, with no segment in between.
- The repository is `wc-<name>` for a WooCommerce plugin and `wp-<name>` otherwise, as the plugin mirrors are named, and the fill-in names the Composer package `<owner>/<repository>` and the npm package `@<owner>/<repository>` after it.
- Each plugin takes its own three consecutive wp-env ports, for its development, tests and multisite sites, apart from 8888 and 8889, which wp-env uses by default, and the template's own 8811, 8812 and 8813.

## Working on the template

Development needs PHP and Composer at the entry file's `Requires PHP` floor or later, Node.js and npm at `package.json`'s `engines`, Docker for `@wordpress/env`, and a POSIX shell for `npm run start`.

Install the dependencies, prefix the framework into `vendor-prefixed/`, build the scripts and start the development site on <http://localhost:8811>:

```sh
npm run setup
```

wp-env publishes the development site on every network interface, with fixed development credentials, so other devices on the local network can reach it.

Run the manifest and changelog checks, PHPCS, PHPStan, and the Unit and Integration suites on the tests site at <http://localhost:8812>:

```sh
composer quality-check
```

Install the browser the end-to-end suite runs in, initially and after a Playwright update that needs a new browser revision, then run the suite:

```sh
npx playwright install chromium
npm run test:e2e
```

Lint the scripts, styles and types:

```sh
npm run lint
```

Update the dependencies within their constraints, and `@wordpress/*` to npm's latest rather than the WordPress floor's versions, since WordPress supplies those packages at runtime and PHPStan and the end-to-end suite on the floor's WordPress keep the plugin to its floor's APIs:

```sh
npm run packages-update:all
```

Every pull request also rehearses the fill-in with sample inputs and gates the filled-in plugin, and rehearses the release on the built zip. The template itself publishes no release, even on a tag, and takes no changelog fragments, which the fill-in deletes; `CHANGELOG.md`'s `1.0.0 - unreleased` block is the plugin's first entry. `tests/README.md` describes the suites and CI, `AGENTS.md` the conventions, `CONTRIBUTING.md` the checks before a pull request, the changelog and releases, and `SECURITY.md` how to report a vulnerability.

## License

GPL-3.0-or-later.
