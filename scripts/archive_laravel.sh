#!/usr/bin/env bash
# Archive common Laravel files and folders into a timestamped backup directory.
# Run this from the repository root: ./scripts/archive_laravel.sh

set -euo pipefail
ts=$(date +%Y%m%d_%H%M%S)
dest="legacy_laravel_backup_$ts"
mkdir -p "$dest"

items=(artisan app bootstrap config database public resources routes storage tests vendor composer.json composer.lock package.json vite.config.js phpunit.xml .env)

for i in "${items[@]}"; do
  if [ -e "$i" ]; then
    mv "$i" "$dest/" || true
  fi
done

echo "Moved Laravel files to $dest"
