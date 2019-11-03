#!/usr/bin/env bash
set -e

[ -n "$PICO_BUILD_ENV" ] || { echo "No Pico build environment specified" >&2; exit 1; }

# parameters
ARCHIVE_DIR="${1:-$PICO_PROJECT_DIR}"   # directory to create release archives in

# print parameters
echo "Cleaning up build environment..."
printf 'PICO_BUILD_DIR="%s"\n' "$PICO_BUILD_DIR"
printf 'ARCHIVE_DIR="%s"\n' "$ARCHIVE_DIR"
echo

echo "Removing build directory..."
[ ! -d "$PICO_BUILD_DIR" ] || rm -rf "$PICO_BUILD_DIR"

echo "Removing release archives..."
find "$ARCHIVE_DIR" -mindepth 1 -maxdepth 1 \
    \( -name 'pico-deprecated-release-*.tar.gz' -o -name 'pico-deprecated-release-*.zip' \) \
    -delete
