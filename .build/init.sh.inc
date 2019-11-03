if [ -z "$PICO_BUILD_ENV" ]; then
    echo "No Pico build environment specified" >&2
    exit 1
fi

# add project build dir to $PATH
export PATH="$PICO_PROJECT_DIR/.build:$PATH"
