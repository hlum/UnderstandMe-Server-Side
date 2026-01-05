#!/bin/sh
set -e

APP="/var/www/html"

# General permissions
find "$APP" -type d -exec chmod 755 {} \;
find "$APP" -type f -exec chmod 644 {} \;

# Secure config
if [ -d "$APP/config" ]; then
  chown -R root:www-data "$APP/config"
  chmod 750 "$APP/config"
  chmod 640 "$APP/config/config.php"
  chmod 600 "$APP/config/service-account.json"
fi

exec "$@"
