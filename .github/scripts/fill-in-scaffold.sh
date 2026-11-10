#!/usr/bin/env bash
# Fills in the template's placeholders with a plugin's identity. fill-in-scaffold.yml runs it once in a plugin
# generated from the template, and Quality rehearses it on the template with sample inputs.
set -euo pipefail

: "${PLUGIN_NAME:?}" "${DESCRIPTION:?}" "${SLUG:?}" "${NS:?}" "${PREFIX:?}" "${PORT:?}" "${REPO:?}"

fail() {
	echo "::error::$1"
	exit 1
}

# Each input lands on one line of a PHP, JSON or Markdown file, where a control character breaks it, so the checks
# below match whole values.
for input in "$PLUGIN_NAME" "$DESCRIPTION" "$SLUG" "$NS" "$PREFIX" "$PORT"; do
	[[ $input != *[[:cntrl:]]* ]] || fail 'No input may contain a line break or another control character.'
done

# The slug names the entry file, the text domain and the block namespace, which WordPress refuses unless it starts
# with a letter, and the workflows check the slug they read from composer.json with the same pattern.
[[ $SLUG =~ ^[a-z][a-z0-9]*(-[a-z0-9]+)*$ ]] || fail 'plugin-slug must be kebab-case starting with a letter, with no leading, trailing or double hyphen.'
# A namespace that is a reserved word, such as List, parses from PHP 8.0 only, which the fill-in job's PHP 7.4 syntax
# check refuses.
[[ $NS =~ ^[A-Za-z][A-Za-z0-9]*$ ]] || fail 'php-namespace must match ^[A-Za-z][A-Za-z0-9]*$.'
# One segment after dws_ keeps every prefix in one plugin's family from starting another plugin's; past 23 characters
# the template's longest option constants fail PHPCS's array alignment, which Quality's rehearsal at the cap checks,
# and the framework's admin notices refuse a network or hidden segment.
[[ $PREFIX =~ ^dws_[a-z0-9]{1,23}$ && $PREFIX != dws_network && $PREFIX != dws_hidden ]] || fail 'prefix must match ^dws_[a-z0-9]{1,23}$ and must not be dws_network or dws_hidden.'
# The ports land in JSON numbers, where a leading zero is invalid, and the tests and multisite environments take the
# next two; none may be the template's own 8811, 8812 or 8813, which the leftover check below refuses.
[[ $PORT =~ ^[1-9][0-9]{0,4}$ && $PORT -le 65533 && ( $PORT -lt 8809 || $PORT -gt 8813 ) ]] ||
	fail 'wp-env-port must be 1-65533 with no leading zero, and neither it nor the next two ports may be 8811, 8812 or 8813.'
# WordPress skips a plugin whose name is blank and strips tags from the name it shows, and the name lands in
# single-quoted PHP and JavaScript strings.
name_breakers="['\\\$/<>]"
[[ $PLUGIN_NAME =~ [^[:space:]] && ! $PLUGIN_NAME =~ $name_breakers ]] || fail "plugin-name must not be blank or contain \\, \$, /, ', < or >."
# The description lands in the entry's header docblock, which */ would close.
[[ $DESCRIPTION =~ [^[:space:]] && $DESCRIPTION != *'*/'* ]] || fail 'description must not be blank or contain */.'

export SCREAMING_PREFIX="${PREFIX^^}"
export TESTS_PORT=$(( PORT + 1 ))
export MULTISITE_PORT=$(( PORT + 2 ))
export REPO_NAME="${REPO#*/}"
# Composer and npm accept only lowercase package names, which the sweep takes from the repository.
export REPO="${REPO,,}"
TEMPLATE_DESCRIPTION=$(php -r 'echo json_decode( (string) file_get_contents( "composer.json" ), false, 512, JSON_THROW_ON_ERROR )->description;')
export TEMPLATE_DESCRIPTION

git mv --force README.scaffold.md README.md
# The scaffold README becomes the plugin's README.md, which .distignore already excludes.
perl -ni -e 'print unless /^README\.scaffold\.md$/' .distignore
git rm --quiet includes/_disabled-example.php
# The template's recipe test loads the recipe's safeguard helper, which a plugin that applies the recipe declares
# itself, so only the recipe's own test stays.
git rm --quiet tests/Integration/SafeguardRecipeTest.php
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
	s/\b(881[123])\b/8811 == $1 ? $ENV{PORT} : 8812 == $1 ? $ENV{TESTS_PORT} : $ENV{MULTISITE_PORT}/ge;
'

# The description goes in after the port sweep, which would rewrite a port number in it.
# shellcheck disable=SC2016
perl -pi -e 's/\Q$ENV{TEMPLATE_DESCRIPTION}\E/$ENV{DESCRIPTION}/' "$SLUG.php"
# The manifests hold the description in JSON strings, so their copy escapes quotes and backslashes.
# shellcheck disable=SC2016
perl -pi -e 'BEGIN { ( $description = $ENV{DESCRIPTION} ) =~ s/(["\\])/\\$1/g } s/\Q$ENV{TEMPLATE_DESCRIPTION}\E/$description/' composer.json package.json
# shellcheck disable=SC2016
perl -pi -e 's/^TODO: one sentence saying why this plugin exists.*$/$ENV{DESCRIPTION}/' README.md

find changelog -type f ! -name '.gitkeep' -delete

# The sweep renamed the package, so the lock's content hash follows, or composer validate --strict fails.
composer update --lock --no-install --no-scripts --no-plugins --ignore-platform-req=php+ --no-interaction

composer validate --strict
php -l "$SLUG.php"
php -l uninstall.php
# shellcheck disable=SC2016
for swept_json in .wp-env.json .wp-env.tests.json .wp-env.multisite.json composer.json package.json package-lock.json; do
	php -r 'json_decode( (string) file_get_contents( $argv[1] ), false, 512, JSON_THROW_ON_ERROR );' "$swept_json"
done

# A name or slug shorter than the template's leaves wrapped lines that Prettier joins, so the swept scripts are formatted
# again. Install scripts stay off, since formatting needs none of them.
npm ci --ignore-scripts --no-audit --no-fund
node_modules/.bin/wp-scripts format '{blocks/src,assets/src,tests/EndToEnd}/**/*.{js,jsx,ts,tsx}'

status=0
if git grep -n -I -E 'dws-plugin-template|dws_tpl|DWS_TPL|PluginTemplate|DWS Plugin Template|wordpress-plugin-template' -- . ':!.github/scripts/fill-in-scaffold.sh'; then
	echo "::error::The fill-in left the template's names above."
	status=1
fi
if git grep -n -I -F -e "$TEMPLATE_DESCRIPTION" -- . ':!.github/scripts/fill-in-scaffold.sh'; then
	echo "::error::The fill-in left the template's description above."
	status=1
fi
if git grep -n -I -w -E '881[123]' -- . ':!.github/scripts/fill-in-scaffold.sh' ':!composer.lock' ':!package-lock.json'; then
	echo "::error::The fill-in left the template's ports above."
	status=1
fi
if ! git diff --quiet HEAD -- .github/workflows; then
	echo "::error::The fill-in changed a workflow, which GitHub refuses to push with the job's token."
	status=1
fi
exit "$status"
