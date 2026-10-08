#!/usr/bin/env bash
# Full PHPUnit gate on digest-pinned Alpine images without the ctype extension.
# The committed PHPUnit suite stays offline; only this harness uses Docker/network.
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
INSTALLER="${REPO_ROOT}/.cache/composer-installer/composer-setup.php"
INSTALLER_SIG="${REPO_ROOT}/.cache/composer-installer/installer.sig"

DOCKER="${DOCKER:-docker}"
if ! $DOCKER info >/dev/null 2>&1; then
  DOCKER="sudo docker"
fi

verify_composer_installer() {
  mkdir -p "$(dirname "$INSTALLER")"
  if [[ ! -f "$INSTALLER" ]]; then
    curl -fsSL -o "$INSTALLER" https://getcomposer.org/installer
    curl -fsSL -o "$INSTALLER_SIG" https://composer.github.io/installer.sig
  fi
  if command -v php >/dev/null 2>&1; then
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
}

run_alpine_gate() {
  local image="$1"
  local apk_php="$2"
  local apk_json="$3"
  local php_bin="$4"
  local composer_setup_flags="$5"

  echo "======== Alpine gate: $image ($php_bin) ========"
  $DOCKER run --rm --platform linux/amd64 \
    -v "$REPO_ROOT:/app" \
    -v "$INSTALLER:/tmp/composer-setup.php:ro" \
    -w /app \
    "$image" \
    sh -lc "
set -e
apk add --no-cache $apk_php $apk_json \
  ${apk_php/-cli/-phar} ${apk_php/-cli/-mbstring} ${apk_php/-cli/-openssl} ${apk_php/-cli/-tokenizer} \
  ${apk_php/-cli/-dom} ${apk_php/-cli/-xml} ${apk_php/-cli/-xmlwriter} \
  git unzip >/dev/null
$php_bin -v | head -1
$php_bin -r 'if (extension_loaded(\"ctype\")) { fwrite(STDERR, \"ctype must not be loaded\\n\"); exit(1); }'
$php_bin /tmp/composer-setup.php $composer_setup_flags --install-dir=/usr/local/bin --filename=composer
ln -sf /usr/bin/$php_bin /usr/local/bin/php
composer install --no-interaction --prefer-dist
for i in 1 2; do echo \"=== default run \$i ===\"; vendor/bin/phpunit; done
for i in 1 2; do echo \"=== random run \$i ===\"; vendor/bin/phpunit --order-by=random --random-order-seed=20261008; done
"
}

verify_composer_installer

run_alpine_gate \
  "alpine:3.13@sha256:469b6e04ee185740477efa44ed5bdd64a07bbdd6c7e5f5d169e540889597b911" \
  php7-cli php7-json php7 \
  "--2.2"

run_alpine_gate \
  "alpine:3.16@sha256:452e7292acee0ee16c332324d7de05fa2c99f9994ecc9f0779c602916a672ae4" \
  php81-cli php81-json php81 \
  ""
