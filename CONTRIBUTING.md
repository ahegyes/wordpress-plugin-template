# Contributing

## Changelog entries

Each user-visible change adds one fragment, so pull requests never conflict on `CHANGELOG.md`:

```bash
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

The release commit also sets the entry file's `Version:` header to that version, and `readme.txt`'s `Stable tag:` where the plugin has one, because the release refuses a tag either differs from. It sets each `blocks/src/*/block.json`'s `version` too, which WordPress puts in the block's stylesheet URL and the Unit suite holds to the header. Pushing the tag `v<version>` on that commit starts the release.
