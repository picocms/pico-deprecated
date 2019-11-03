#!/usr/bin/env bash
set -e

[ -n "$PICO_BUILD_ENV" ] || { echo "No Pico build environment specified" >&2; exit 1; }

# parameters
VERSION="${1:-$PROJECT_REPO_TAG}"       # version to create a release for
ARCHIVE_DIR="${2:-$PICO_PROJECT_DIR}"   # directory to create release archives in

# print parameters
echo "Creating new release..."
printf 'VERSION="%s"\n' "$VERSION"
echo

# set archive name
if [ -n "$VERSION" ]; then
    ARCHIVE_NAME="pico-deprecated-release-v${VERSION#v}"
else
    ARCHIVE_NAME="pico-deprecated-release-dev-${PROJECT_REPO_BRANCH:-master}"
fi

# copy project
rsync -a \
    --exclude="/.git" \
    --exclude="/.build" \
    --exclude="/.github" \
    --exclude="/.gitattributes" \
    --exclude="/.gitignore" \
    --exclude="/.phpcs.xml" \
    --exclude="/.travis.yml" \
    --exclude="/pico-deprecated-release-v*.tar.gz" \
    --exclude="/pico-deprecated-release-v*.zip" \
    "$PICO_PROJECT_DIR/" \
    "$PICO_BUILD_DIR/"

cd "$PICO_BUILD_DIR"

# remove picocms/Pico dependency
echo "Removing 'picocms/pico' dependency..."
composer remove --no-update "picocms/pico"
echo

# install dependencies
echo "Running \`composer install\`..."
composer install --no-suggest --prefer-dist --no-dev --optimize-autoloader
echo

# create release archives
create-release.sh "$PICO_BUILD_DIR" "$ARCHIVE_DIR" "$ARCHIVE_NAME"
