#!/bin/sh
set -e

APP_DIR="/var/www/html"

echo "🔐 Setting permissions at container startup..."

# Directories
find "$APP_DIR" -type d -exec chmod 755 {} \;

# Files
find "$APP_DIR" -type f -exec chmod 644 {} \;

# Config files (read-only, stricter)
if [ -d "$APP_DIR/config" ]; then
  chmod 750 "$APP_DIR/config"
  find "$APP_DIR/config" -type f -exec chmod 640 {} \;
fi

# Service account JSON (extra strict)
if [ -f "$APP_DIR/config/service-account.json" ]; then
  chmod 600 "$APP_DIR/config/service-account.json"
fi

echo "✅ Permissions applied"

exec "$@"
