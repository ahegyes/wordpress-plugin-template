# Contributing

## Before opening a pull request

Install the dependencies on the PHP, Node and npm versions the README lists, then run the local equivalents of the CI checks:

```sh
npm run setup
npm run quality-check:all
composer test:integration:multisite
npm run audit
actionlint
zizmor .github/
```

## Changelog entries

Each user-visible change adds one fragment, so pull requests never conflict on `CHANGELOG.md`:

```sh
composer changelog:add
```

The command asks for the change's significance, its type and its entry, and writes the fragment to `changelog/<branch-name>`, which the pull request commits. CI validates the fragments with `composer changelog:validate`.

## Releases

A release writes the fragments into a new version block of `CHANGELOG.md`, whose version the fragments' significance sets:

```sh
composer changelog:write
```

The first release has no earlier version to count from, so it names its own:

```sh
composer changelog:write -- --use-version=1.0.0
```

The release commit also sets the entry file's `Version:` header to that version, and `readme.txt`'s `Stable tag:` where the plugin has one, because the release refuses a tag that either differs from. It sets each `blocks/src/*/block.json`'s `version` too, which WordPress puts in the block's stylesheet URL and the Unit suite holds to the header.

Land the release commit on trunk and wait for its Quality and Tests runs to pass, since the release publishes only a commit whose runs passed. To rehearse, run the Release workflow on trunk from the Actions tab, which checks those runs, builds and tests the zip and publishes nothing. Then sign a tag on the commit and push it, which starts the release:

```sh
git tag -s v<version> -m v<version>
git push origin v<version>
```
