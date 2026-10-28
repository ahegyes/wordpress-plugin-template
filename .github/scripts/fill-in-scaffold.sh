#!/usr/bin/env bash
# Fills in the template's placeholders with a plugin's identity. fill-in-scaffold.yml runs it once in a plugin
# generated from the template, and Quality rehearses it on the template with sample inputs.
set -euo pipefail

: "${PLUGIN_NAME:?}" "${SLUG:?}" "${NS:?}" "${PREFIX:?}" "${PORT:?}" "${REPO:?}"

# The slug names the entry file and the text domain, so it stays clean kebab-case.
grep -Eq '^[a-z][a-z0-9]*(-[a-z0-9]+)*$' <<< "$SLUG" || { echo "::error::plugin-slug must be kebab-case starting with a letter, with no leading, trailing or double hyphen."; exit 1; }
grep -Eq '^[A-Za-z][A-Za-z0-9]*$' <<< "$NS" || { echo "::error::php-namespace must match ^[A-Za-z][A-Za-z0-9]*$."; exit 1; }
# One segment after dws_ keeps every prefix in one plugin's family from starting another plugin's.
grep -Eq '^dws_[a-z0-9]+$' <<< "$PREFIX" || { echo "::error::prefix must match ^dws_[a-z0-9]+$."; exit 1; }
# Both ports land in JSON numbers, where a leading zero is invalid, and the tests environment takes the next one.
{ grep -Eq '^[1-9][0-9]{0,4}$' <<< "$PORT" && [ "$PORT" -le 65534 ]; } || { echo "::error::wp-env-port must be 1-65534 with no leading zero."; exit 1; }
# The display name lands in single-quoted PHP and JavaScript strings.
if printf '%s' "$PLUGIN_NAME" | grep -q "['\\\$/]"; then echo "::error::plugin-name must not contain \\, \$, / or '."; exit 1; fi

export SCREAMING_PREFIX="${PREFIX^^}"
export TESTS_PORT=$(( PORT + 1 ))
export REPO_NAME="${REPO#*/}"

git mv --force README.scaffold.md README.md
git rm --quiet includes/_disabled-example.php
git mv dws-plugin-template.php "$SLUG.php"

# GitHub refuses a GITHUB_TOKEN push that changes a workflow, so the workflows read the slug from composer.json
# instead. The BEGIN filter drops symlinks (CLAUDE.md -> AGENTS.md), which in-place editing would turn into files.
# shellcheck disable=SC2016
git ls-files -z -- . ':!.github/workflows/' ':!.github/scripts/fill-in-scaffold.sh' | xargs -0 perl -pi -e '
	BEGIN { @ARGV = grep { ! -l $_ } @ARGV }
	s{ahegyes/wordpress-plugin-template}{$ENV{REPO}}g;
	s/^# wordpress-plugin-template$/# $ENV{REPO_NAME}/;
	s/dws-plugin-template/$ENV{SLUG}/g;
	s/dws_tpl/$ENV{PREFIX}/g;
	s/DWS_TPL/$ENV{SCREAMING_PREFIX}/g;
	s/PluginTemplate/$ENV{NS}/g;
	s/DWS Plugin Template/$ENV{PLUGIN_NAME}/g;
'

# The locks' integrity hashes and version strings belong to other packages.
# shellcheck disable=SC2016
git ls-files -z -- . ':!.github/workflows/' ':!.github/scripts/fill-in-scaffold.sh' ':!composer.lock' ':!package-lock.json' | xargs -0 perl -pi -e '
	BEGIN { @ARGV = grep { ! -l $_ } @ARGV }
	s/\b8811\b/$ENV{PORT}/g;
	s/\b8812\b/$ENV{TESTS_PORT}/g;
	s/^(\s*\*\s*\@(?:since|version)\s+)2\.0\.0$/${1}0.1.0/;
'
# shellcheck disable=SC2016
perl -pi -e 's/^(\s*\*\s*Version:\s*)2\.0\.0$/${1}0.1.0/' "$SLUG.php"
perl -pi -e 's/^## 2\.0\.0 - unreleased$/## 0.1.0 - unreleased/' CHANGELOG.md
find changelog -type f ! -name '.gitkeep' -delete

# The sweep renamed the package, so the lock's content hash follows, or composer validate --strict fails.
composer update --lock --no-install --no-scripts --no-plugins --ignore-platform-req=php+ --no-interaction

composer validate --strict
php -l "$SLUG.php"
php -l uninstall.php
# shellcheck disable=SC2016
for swept_json in .wp-env.json .wp-env.tests.json composer.json package.json package-lock.json; do
	php -r 'json_decode( (string) file_get_contents( $argv[1] ), false, 512, JSON_THROW_ON_ERROR );' "$swept_json"
done

status=0
if git grep -n -I -E 'dws-plugin-template|dws_tpl|DWS_TPL|PluginTemplate|DWS Plugin Template|wordpress-plugin-template' -- . ':!.github/scripts/fill-in-scaffold.sh'; then
	echo "::error::The fill-in left the template's names above."
	status=1
fi
if git grep -n -I -E '\b881[12]\b' -- . ':!.github/scripts/fill-in-scaffold.sh' ':!composer.lock' ':!package-lock.json'; then
	echo "::error::The fill-in left the template's ports above."
	status=1
fi
if ! git diff --quiet HEAD -- .github/workflows; then
	echo "::error::The fill-in changed a workflow, which GitHub refuses to push with the job's token."
	status=1
fi
exit "$status"
