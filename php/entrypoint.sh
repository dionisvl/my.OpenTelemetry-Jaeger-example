#!/usr/bin/env sh
set -e

composer install --no-interaction --no-progress --prefer-dist --no-dev --optimize-autoloader

php -S 0.0.0.0:8080 \
    -d display_errors=1 \
    -d error_reporting=E_ALL \
    index.php
