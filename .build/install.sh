#!/usr/bin/env bash
set -e

[ -n "$PICO_BUILD_ENV" ] || { echo "No Pico build environment specified" >&2; exit 1; }

# setup build system
"$PICO_TOOLS_DIR/setup/$PICO_BUILD_ENV.sh" --phpcs

# install dependencies
echo "Running \`composer install\`..."
composer install --no-suggest
