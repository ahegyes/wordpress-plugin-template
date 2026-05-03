# Contributing

## Changelog entries

One fragment per user-visible change so PRs don't conflict on `CHANGELOG.md`:

```bash
composer changelog:add
```

Interactive prompt (Significance + Type + entry); fragment lands in `changelog/<branch-name>`, commit it with the PR. CI validates via `composer changelog:validate`.

## Releases

```bash
composer changelog:write
```

Aggregates fragments → new `CHANGELOG.md` version block (semver computed from fragment significance) → mirrors `<!-- Start/End changelog -->` markers into `readme.txt`'s `== Changelog ==`. Don't remove the markers. Commit + tag.
