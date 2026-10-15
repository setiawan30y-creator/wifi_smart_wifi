#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/wifi_smartbilling}"
PHP_BIN="${PHP_BIN:-php}"
BRANCH="${BRANCH:-main}"

cd "$APP_DIR"

echo "Pulling latest code..."
git fetch --all --prune
git checkout "$BRANCH"
git pull origin "$BRANCH"

echo "Installing PHP dependencies..."
composer install --no-dev --prefer-dist --optimize-autoloader

if [ -f package.json ]; then
  echo "Building frontend assets..."
  npm ci
  npm run build
fi

echo "Running migrations..."
"$PHP_BIN" artisan migrate --force

echo "Preparing storage..."
"$PHP_BIN" artisan storage:link || true

echo "Optimizing Laravel..."
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache
"$PHP_BIN" artisan event:cache || true

echo "Restarting queues..."
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart wifi-smartbilling-worker:* || true

echo "Installing and enabling Laravel scheduler..."
sudo cp deploy/systemd/wifi-smartbilling-scheduler.service /etc/systemd/system/wifi-smartbilling-scheduler.service
sudo cp deploy/systemd/wifi-smartbilling-scheduler.timer /etc/systemd/system/wifi-smartbilling-scheduler.timer
sudo systemctl daemon-reload
sudo systemctl enable --now wifi-smartbilling-scheduler.timer
sudo systemctl restart wifi-smartbilling-scheduler.timer

echo "Reloading services..."
sudo systemctl reload php8.4-fpm || true
sudo systemctl reload nginx

echo "Deployment complete."
