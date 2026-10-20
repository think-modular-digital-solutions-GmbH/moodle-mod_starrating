#!/usr/bin/env bash
# Tag the release named in version.php and create its GitHub Release.
#
# Run by .github/workflows/release.yml on the commit CI passed on (SHA), with
# GH_TOKEN allowed to write contents. Writes tag=<tag> and created=true|false to
# $GITHUB_OUTPUT. Does nothing when the release is tagged already, and fails,
# without tagging, when the release is not ready:
#   - $plugin->release is not a SemVer version,
#   - $plugin->version is not above the one of the last release, which the
#     Marketplace would refuse as a duplicate build number,
#   - CHANGES.md has no section for the release.
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
        echo $plugin->{$argv[1]} ?? "";
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
