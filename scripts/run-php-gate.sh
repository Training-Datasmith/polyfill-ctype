#!/usr/bin/env bash
# Run the PHPUnit gate inside a digest-pinned official PHP CLI image.
# Usage: run-php-gate.sh <php-image@digest> [update|install]
set -euo pipefail

IMAGE="${1:?php image digest reference required}"
COMPOSER_MODE="${2:-install}"

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
INSTALLER="${REPO_ROOT}/.cache/composer-installer/composer-setup.php"
INSTALLER_SIG="${REPO_ROOT}/.cache/composer-installer/installer.sig"

mkdir -p "$(dirname "$INSTALLER")"
if [[ ! -f "$INSTALLER" ]]; then
  curl -fsSL -o "$INSTALLER" https://getcomposer.org/installer
  curl -fsSL -o "$INSTALLER_SIG" https://composer.github.io/installer.sig
fi
if command -v php >/dev/null; then
  php -r '
$h = hash_file("sha384", $argv[1]);
$s = trim(file_get_contents($argv[2]));
if ($h !== $s) { fwrite(STDERR, "Composer installer signature mismatch\n"); exit(1); }
' "$INSTALLER" "$INSTALLER_SIG"
else
  python3 -c '
import hashlib, sys
h = hashlib.sha384(open(sys.argv[1], "rb").read()).hexdigest()
s = open(sys.argv[2]).read().strip()
if h != s:
    print("Composer installer signature mismatch", file=sys.stderr)
    sys.exit(1)
' "$INSTALLER" "$INSTALLER_SIG"
fi

DOCKER="${DOCKER:-docker}"
if ! $DOCKER info >/dev/null 2>&1; then
  DOCKER="sudo docker"
fi

$DOCKER run --rm --platform linux/amd64 \
  -e "COMPOSER_MODE=${COMPOSER_MODE}" \
  -v "$REPO_ROOT:/app" \
  -v "$INSTALLER:/tmp/composer-setup.php:ro" \
  -w /app \
  "$IMAGE" \
  bash -lc '
set -euo pipefail
php -v | head -1
if php -r "exit(version_compare(PHP_VERSION, \"8.0.0\", \"<\") ? 0 : 1);"; then
  sed -i -e "s/deb.debian.org/archive.debian.org/g" -e "s|security.debian.org/debian-security|archive.debian.org/debian-security|g" /etc/apt/sources.list
fi
apt-get update -qq
apt-get install -y -qq git unzip zlib1g-dev libzip-dev >/dev/null
docker-php-ext-install zip >/dev/null
php /tmp/composer-setup.php --2.2 --install-dir=/usr/local/bin --filename=composer
if [[ "$COMPOSER_MODE" == update ]]; then
  composer update --no-interaction --prefer-dist
else
  composer install --no-interaction --prefer-dist
fi
for i in 1 2; do echo "=== default run $i ==="; vendor/bin/phpunit; done
for i in 1 2; do echo "=== random run $i ==="; vendor/bin/phpunit --order-by=random --random-order-seed=20261008; done
'
