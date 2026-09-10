#!/usr/bin/env bash
#
# Runs the test suite on PHP 7.4, the version the OBB apps that embed this
# package run on.
#
# PHPUnit is a PHAR rather than a dev dependency on purpose. This package has
# no vendor/ of its own - it is always installed as a dependency of an app -
# and adding one here would put a second, unrelated dependency graph next to
# the one each app resolves for it.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHAR="$ROOT/tools/phpunit.phar"
PHAR_URL="https://phar.phpunit.de/phpunit-9.phar"
PHAR_SHA256="d9552a130747f02f9d7fc2427b143189c638e273c502c8faa88ab6b04c5f2662"
PHP_IMAGE="php:7.4-cli"

sha256() {
    if command -v shasum >/dev/null 2>&1; then
        shasum -a 256 "$1" | cut -d' ' -f1
    else
        sha256sum "$1" | cut -d' ' -f1
    fi
}

if [ ! -f "$PHAR" ]; then
    echo "Downloading PHPUnit 9.6 ..."
    mkdir -p "$ROOT/tools"
    curl -sSL -o "$PHAR" "$PHAR_URL"
fi

ACTUAL="$(sha256 "$PHAR")"
if [ "$ACTUAL" != "$PHAR_SHA256" ]; then
    echo "phpunit.phar checksum mismatch: expected $PHAR_SHA256, got $ACTUAL" >&2
    exit 1
fi

if ! command -v docker >/dev/null 2>&1; then
    echo "Docker not found - running on the local PHP ($(php -r 'echo PHP_VERSION;'))." >&2
    echo "Weaker evidence than the normal run: the PHP version does not match the apps'." >&2
    exec php "$PHAR" "$@"
fi

exec docker run --rm -v "$ROOT:/srv" -w /srv "$PHP_IMAGE" php tools/phpunit.phar "$@"
