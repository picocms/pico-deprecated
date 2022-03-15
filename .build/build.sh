#!/bin/bash
# PicoDeprecated -- build.sh
# Builds a new PicoDeprecated release and creates release archives.
#
# This file is part of Pico, a stupidly simple, blazing fast, flat file CMS.
# Visit us at https://picocms.org/ for more info.
#
# Copyright (c) 2019  Daniel Rudolf <https://www.daniel-rudolf.de>
#
# This work is licensed under the terms of the MIT license.
# For a copy, see LICENSE file or <https://opensource.org/licenses/MIT>.
#
# SPDX-License-Identifier: MIT
# License-Filename: LICENSE

set -eu -o pipefail
export LC_ALL=C

# env variables
PHP="${PHP:-php}"
export -n PHP

COMPOSER="${COMPOSER:-composer}"
export -n COMPOSER

if ! which "$PHP" > /dev/null; then
    echo "Missing script dependency: php" >&2
    exit 1
elif ! which "$COMPOSER" > /dev/null; then
    echo "Missing script dependency: composer" >&2
    exit 1
elif ! which "git" > /dev/null; then
    echo "Missing script dependency: git" >&2
    exit 1
elif ! which "gh" > /dev/null; then
    echo "Missing script dependency: gh" >&2
    exit 1
elif ! which "rsync" > /dev/null; then
    echo "Missing script dependency: rsync" >&2
    exit 1
elif ! which "jq" > /dev/null; then
    echo "Missing script dependency: jq" >&2
    exit 1
fi

# parameters
BUILD_NAME="pico-deprecated"
BUILD_PROJECT="picocms/pico-deprecated"
BUILD_VERSION=

PICO_PROJECT="picocms/pico"

PHP_VERSION="7.2"

# options
VERSION=
PUBLISH=
NOCHECK=
NOCLEAN=

while [ $# -gt 0 ]; do
    if [ "$1" == "--help" ]; then
        echo "Usage:"
        echo "  build.sh [OPTIONS]... [VERSION]"
        echo
        echo "Builds a new PicoDeprecated release and creates release archives."
        echo
        echo "Help options:"
        echo "  --help      display this help and exit"
        echo
        echo "Application options:"
        echo "  --publish   create GitHub release and upload artifacts"
        echo "  --no-check  skip version checks for dev builds"
        echo "  --no-clean  don't remove build dir on exit"
        echo
        echo "You must run this script from within PicoDeprecated's source directory."
        echo "Please note that this script will perform a large number of strict sanity"
        echo "checks before building a new non-development version of PicoDeprecated."
        echo "VERSION must start with 'v'."
        exit 0
    elif [ "$1" == "--publish" ]; then
        PUBLISH="y"
        shift
    elif [ "$1" == "--no-check" ]; then
        NOCHECK="y"
        shift
    elif [ "$1" == "--no-clean" ]; then
        NOCLEAN="y"
        shift
    elif [ -z "$VERSION" ] && [ "${1:0:1}" == "v" ]; then
        VERSION="$1"
        shift
    else
        echo "Unknown option: $1" >&2
        exit 1
    fi
done

# check options and current working dir
if [ ! -f "./composer.json" ] || [ ! -f "./PicoDeprecated.php" ]; then
    echo "You must run this from within PicoDeprecated's source directory" >&2
    exit 1
elif [ "$(git rev-parse --is-inside-work-tree 2> /dev/null)" != "true" ]; then
    echo "You must run this from within a non-bare Git repository" >&2
    exit 1
fi

# parse version
function parse_version {
    VERSION_FULL="${1#v}"

    if ! [[ "$VERSION_FULL" =~ ^([0-9]+)\.([0-9]+)\.([0-9]+)(-([0-9A-Za-z\.\-]+))?(\+([0-9A-Za-z\.\-]+))?$ ]]; then
        return 1
    fi

    VERSION_MAJOR="${BASH_REMATCH[1]}"
    VERSION_MINOR="${BASH_REMATCH[2]}"
    VERSION_PATCH="${BASH_REMATCH[3]}"
    VERSION_SUFFIX="${BASH_REMATCH[5]}"

    VERSION_STABILITY="stable"
    if [[ "$VERSION_SUFFIX" =~ ^(dev|a|alpha|b|beta|rc)?([.-]?[0-9]+)?([.-](dev))?$ ]]; then
        if [ "${BASH_REMATCH[1]}" == "dev" ] || [ "${BASH_REMATCH[4]}" == "dev" ]; then
            VERSION_STABILITY="dev"
        elif [ "${BASH_REMATCH[1]}" == "a" ] || [ "${BASH_REMATCH[1]}" == "alpha" ]; then
            VERSION_STABILITY="alpha"
        elif [ "${BASH_REMATCH[1]}" == "b" ] || [ "${BASH_REMATCH[1]}" == "beta" ]; then
            VERSION_STABILITY="beta"
        elif [ "${BASH_REMATCH[1]}" == "rc" ]; then
            VERSION_STABILITY="rc"
        fi
    fi
}

export COMPOSER_ROOT_VERSION=

BUILD_VERSION="v$("$PHP" -r 'class AbstractPicoPlugin {} require("./PicoDeprecated.php"); echo PicoDeprecated::VERSION;')"

if ! parse_version "$BUILD_VERSION"; then
     echo "Unable to build PicoDeprecated: Invalid PicoDeprecated version '$BUILD_VERSION'" >&2
     exit 1
fi

if [ -z "$VERSION" ]; then
    GIT_LOCAL_HEAD="$(git rev-parse HEAD)"
    GIT_LOCAL_BRANCH="$(git rev-parse --abbrev-ref HEAD)"
    DATETIME="$(date -u +'%Y%m%dT%H%M%SZ')"

    VERSION="v$VERSION_MAJOR.$VERSION_MINOR.$VERSION_PATCH"
    [ -z "$VERSION_SUFFIX" ] || VERSION+="-$VERSION_SUFFIX"
    [ "$VERSION_STABILITY" == "dev" ] || VERSION+="-dev"
    VERSION+="+git.$GIT_LOCAL_HEAD.$DATETIME"

    if ! parse_version "$VERSION"; then
         echo "Unable to build PicoDeprecated: Invalid build version '$VERSION'" >&2
         exit 1
    fi

    COMPOSER_ROOT_VERSION="dev-$GIT_LOCAL_BRANCH"
else
    if ! parse_version "$VERSION"; then
         echo "Unable to build PicoDeprecated: Invalid build version '$VERSION'" >&2
         exit 1
    fi

    if [ "$VERSION_STABILITY" == "dev" ]; then
         COMPOSER_ROOT_VERSION="$(jq -r --arg ALIAS "$VERSION_MAJOR.$VERSION_MINOR.x-dev" \
            '.extra."branch-alias"|to_entries|map(select(.value==$ALIAS).key)[0]//empty' \
            "composer.json")"
    fi

    if [ -z "$COMPOSER_ROOT_VERSION" ]; then
        COMPOSER_ROOT_VERSION="$VERSION_MAJOR.$VERSION_MINOR.$VERSION_PATCH"
        [ -z "$VERSION_SUFFIX" ] || COMPOSER_ROOT_VERSION+="-$VERSION_SUFFIX"
        [ "$VERSION_STABILITY" == "stable" ] || COMPOSER_ROOT_VERSION+="@$VERSION_STABILITY"
    fi
fi

# build checks
if [ "$VERSION_STABILITY" != "dev" ]; then
    GIT_LOCAL_CLEAN="$(git status --porcelain)"
    GIT_LOCAL_HEAD="$(git rev-parse HEAD)"
    GIT_LOCAL_TAG="$(git rev-parse --verify "refs/tags/$VERSION" 2> /dev/null || true)"
    GIT_REMOTE="$(git 'for-each-ref' --format='%(upstream:remotename)' "$(git symbolic-ref -q HEAD)")"
    GIT_REMOTE_TAG="$(git ls-remote "${GIT_REMOTE:-origin}" "refs/tags/$VERSION" 2> /dev/null | cut -f 1 || true)"
    PHP_VERSION_LOCAL="$("$PHP" -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')"

    if [ "$VERSION" != "$BUILD_VERSION" ]; then
        echo "Unable to build PicoDeprecated: Building $VERSION, but PicoDeprecated indicates $BUILD_VERSION" >&2
        exit 1
    elif [ -n "$GIT_LOCAL_CLEAN" ]; then
        echo "Unable to build PicoDeprecated: Building $VERSION, but the working tree is not clean" >&2
        exit 1
    elif [ -z "$GIT_LOCAL_TAG" ]; then
        echo "Unable to build PicoDeprecated: Building $VERSION, but no matching local Git tag was found" >&2
        exit 1
    elif [ "$GIT_LOCAL_HEAD" != "$GIT_LOCAL_TAG" ]; then
        echo "Unable to build PicoDeprecated: Building $VERSION, but the matching Git tag is not checked out" >&2
        exit 1
    elif [ -z "$GIT_REMOTE_TAG" ]; then
        echo "Unable to build PicoDeprecated: Building $VERSION, but no matching remote Git tag was found" >&2
        exit 1
    elif [ "$GIT_LOCAL_TAG" != "$GIT_REMOTE_TAG" ]; then
        echo "Unable to build PicoDeprecated: Building $VERSION, but the matching local and remote Git tags differ" >&2
        exit 1
    elif [ "$PHP_VERSION_LOCAL" != "$PHP_VERSION" ]; then
        echo "Unable to build PicoDeprecated: Refusing to build PicoDeprecated with PHP $PHP_VERSION_LOCAL, expecting PHP $PHP_VERSION" >&2
        exit 1
    fi
else
    if [ -z "$NOCHECK" ] && [[ "$VERSION" != "$BUILD_VERSION"* ]]; then
        echo "Unable to build PicoDeprecated: Building $VERSION, but PicoDeprecated indicates $BUILD_VERSION" >&2
        exit 1
    elif [ -n "$PUBLISH" ]; then
        echo "Unable to build PicoDeprecated: Refusing to publish a dev version" >&2
        exit 1
    fi
fi

# build in progress...
APP_DIR="$PWD"

BUILD_DIR="$(mktemp -d)"
[ -n "$NOCLEAN" ] || trap "rm -rf ${BUILD_DIR@Q}" ERR EXIT

echo "Building Pico $BUILD_VERSION ($VERSION)..."
[ -z "$NOCLEAN" ] || echo "Build directory: $BUILD_DIR"
echo

# copy PicoDeprecated for building
# the working tree is always clean for non-dev versions, see build checks above
echo "Creating clean working tree copy of '$BUILD_PROJECT'..."
rsync -a \
    --exclude="/.build" \
    --exclude="/.git" \
    --exclude="/.github" \
    --exclude="/.gitattributes" \
    --exclude="/.gitignore" \
    --exclude="/.phpcs.xml" \
    --exclude-from=<(git ls-files --exclude-standard -oi --directory) \
    ./ "$BUILD_DIR/"

echo

# switch to build dir...
cd "$BUILD_DIR"

# remove picocms/pico dependency
echo "Removing '$PICO_PROJECT' dependency..."
"$COMPOSER" remove --no-update "$PICO_PROJECT"
echo

# set minimum stability
if [ "$VERSION_STABILITY" != "stable" ]; then
    echo "Setting minimum stability to '$VERSION_STABILITY'..."
    "$COMPOSER" config "minimum-stability" "$VERSION_STABILITY"
    "$COMPOSER" config "prefer-stable" "true"
    echo
fi

# install dependencies
echo "Installing Composer dependencies..."
"$COMPOSER" install --no-dev --optimize-autoloader
echo

# prepare release
echo "Removing '.git' directory..."
rm -rf .git

echo "Removing '.git' directories of dependencies..."
find vendor/ -type d -path 'vendor/*/*/.git' -print0 | xargs -0 rm -rf

echo

# restore picocms/pico dependency
echo "Restoring '$PICO_PROJECT' dependency..."
"$COMPOSER" require --no-update "$PICO_PROJECT self.version"
echo

# create release archives
ARCHIVE_FILENAME="$BUILD_NAME-release-$VERSION"

echo "Creating release archive '$ARCHIVE_FILENAME.tar.gz'..."
find . -mindepth 1 -maxdepth 1 -printf '%f\0' \
    | xargs -0 -- tar -czf "$APP_DIR/$ARCHIVE_FILENAME.tar.gz" --

echo "Creating release archive '$ARCHIVE_FILENAME.zip'..."
zip -q -r "$APP_DIR/$ARCHIVE_FILENAME.zip" .

echo

# publish release
if [ -n "$PUBLISH" ]; then
    # switch to app dir
    cd "$APP_DIR"

    # create GitHub release and upload release archives
    echo "Creating GitHub release and uploading release archives..."

    GITHUB_PRERELEASE=
    [ "$VERSION_STABILITY" == "stable" ] || GITHUB_PRERELEASE="--prerelease"

    gh release create "$VERSION" \
        --title "Version $VERSION_FULL" \
        --generate-notes \
        "$GITHUB_PRERELEASE" \
        "$APP_DIR/$ARCHIVE_FILENAME.tar.gz" \
        "$APP_DIR/$ARCHIVE_FILENAME.zip"
fi
