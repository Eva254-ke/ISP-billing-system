#!/usr/bin/env bash
set -euo pipefail

echo "== Queue worker process =="
ps aux | grep -E "php artisan queue:work" | grep -v grep || true

echo
echo "== systemd status =="
sudo systemctl status cloudbridge-queue --no-pager || true

echo
echo "== queue backlog =="
php artisan queue:monitor redis:critical redis:high redis:medium redis:low redis:default || true
