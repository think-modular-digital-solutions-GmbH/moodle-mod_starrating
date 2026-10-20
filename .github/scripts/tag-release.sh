#!/usr/bin/env bash
# Tag the release named in version.php, create its GitHub Release, and move the
# stable branch of the Moodle versions it supports to it.
#
# Run by .github/workflows/release.yml on the commit CI passed on (SHA), with
# GH_TOKEN allowed to write contents. Writes tag=<tag> and created=true|false to
# $GITHUB_OUTPUT. Does nothing when the release is tagged already, and fails,
# without tagging, when the release is not ready:
#   - $plugin->release is not a SemVer version,
#   - $plugin->version is not above the one of the last release, which the
#     Marketplace would refuse as a duplicate build number,
#   - CHANGES.md has no section for the release.
#
# The stable branch follows Moodle's convention, MOODLE_XY_STABLE, named after
# the highest version in $plugin->supported: one branch for every range of
# Moodle versions that share the same code. It only ever moves forward; when it
# cannot, the release stands and a warning says so.
set -euo pipefail

output="${GITHUB_OUTPUT:-/dev/stdout}"

# Read a field of $plugin from a version.php given on stdin.
plugin_field() {
    # The single quotes keep the PHP variables away from the shell.
    # shellcheck disable=SC2016
    php -r '
        define("MOODLE_INTERNAL", true);
        // As in lib/setuplib.php, which version.php expects to be loaded.
        define("MATURITY_ALPHA", 50);
        define("MATURITY_BETA", 100);
        define("MATURITY_RC", 150);
        define("MATURITY_STABLE", 200);
        $plugin = new stdClass();
        $file = tempnam(sys_get_temp_dir(), "version");
        file_put_contents($file, stream_get_contents(STDIN));
        include($file);
        $value = $plugin->{$argv[1]} ?? "";
        // $plugin->supported is [lowest, highest]; the highest names the branch.
        echo is_array($value) ? max($value) : $value;
    ' "$1"
}

component="$(plugin_field component < version.php)"
release="$(plugin_field release < version.php)"
version="$(plugin_field version < version.php)"
tag="v${release}"

echo "tag=${tag}" >> "$output"
echo "${component} ${release} (${version}) at ${SHA}"

if [[ ! "$release" =~ ^[0-9]+\.[0-9]+\.[0-9]+(-[0-9A-Za-z.-]+)?$ ]]; then
    echo "::error file=version.php::\$plugin->release '${release}' is not a SemVer version such as 2.1.0."
    exit 1
fi

if git rev-parse -q --verify "refs/tags/${tag}" > /dev/null; then
    echo "${tag} exists already, nothing to release."
    echo "created=false" >> "$output"
    exit 0
fi

last="$(git tag -l 'v*' --sort=-v:refname | head -n 1)"
if [[ -n "$last" ]]; then
    lastversion="$(git show "${last}:version.php" | plugin_field version)"
    if (( version <= lastversion )); then
        echo "::error file=version.php::\$plugin->release changed to ${release}, but \$plugin->version ${version} is not above ${lastversion} of ${last}. Bump \$plugin->version as well."
        exit 1
    fi
fi

notes="$(awk -v release="$release" '
    index($0, "## [" release "]") == 1 { found = 1; next }
    found && /^## \[/ { exit }
    found { print }
' CHANGES.md)"
if [[ -z "${notes//[[:space:]]/}" ]]; then
    echo "::error file=CHANGES.md::CHANGES.md has no section '## [${release}]'. Move the Unreleased entries under it."
    exit 1
fi

# The ZIP has the plugin directory as its root, which GitHub's own source
# archives do not, so it installs through Site administration as it is.
dirname="${component#*_}"
zip="${component}-${release}.zip"
git archive --format=zip --prefix="${dirname}/" -o "$zip" "$SHA"

printf '%s\n' "$notes" > release-notes.md
gh release create "$tag" "$zip" --target "$SHA" --title "${release}" --notes-file release-notes.md

echo "created=true" >> "$output"
echo "Released ${tag}."

supported="$(plugin_field supported < version.php)"
if [[ ! "$supported" =~ ^[0-9]{3,}$ ]]; then
    echo "::warning file=version.php::No \$plugin->supported, so no MOODLE_XY_STABLE branch was moved."
    exit 0
fi

branch="MOODLE_${supported}_STABLE"
if gh api "repos/${GITHUB_REPOSITORY}/git/ref/heads/${branch}" > /dev/null 2>&1; then
    # force=false: GitHub refuses anything but a fast-forward.
    if gh api -X PATCH "repos/${GITHUB_REPOSITORY}/git/refs/heads/${branch}" -f sha="$SHA" -F force=false > /dev/null; then
        echo "Moved ${branch} to ${tag}."
    else
        echo "::warning::${branch} was not moved to ${tag}: it has commits ${tag} does not, or the workflow token may not touch workflow files. Merge the commits into main, or move the branch by hand: git push origin ${tag}^{commit}:refs/heads/${branch}"
    fi
elif gh api -X POST "repos/${GITHUB_REPOSITORY}/git/refs" -f ref="refs/heads/${branch}" -f sha="$SHA" > /dev/null; then
    echo "Created ${branch} at ${tag}."
else
    echo "::warning::${branch} could not be created. Create it by hand: git push origin ${tag}^{commit}:refs/heads/${branch}"
fi
