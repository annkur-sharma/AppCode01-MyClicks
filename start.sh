#!/bin/sh

set -e
set -euo pipefail

PHOTOS_PUBLIC="/app/photos/public"
PHOTOS_STATIC="/app/photos/static"
LOG_DIR="/app/logs"
GUID_FILE="/app/guid.txt"
GUID_LOG="$LOG_DIR/guid-log.log"

mkdir -p "$PHOTOS_PUBLIC" "$PHOTOS_STATIC" "$LOG_DIR"
chmod -R 777 "$PHOTOS_PUBLIC" "$PHOTOS_STATIC"
touch "$LOG_DIR/upload.log" "$LOG_DIR/upload-errors.log" "$GUID_LOG"
chmod 666 "$LOG_DIR"/*.log 2>/dev/null || true

generate_guid() {
  uuidgen
}

# GUID logic
if [ ! -f "$GUID_FILE" ]; then
  NEW_GUID=$(generate_guid)
  echo "$NEW_GUID" > "$GUID_FILE"
  echo "[$(date '+%Y-%m-%d %H:%M:%S')] First GUID: $NEW_GUID" >> "$GUID_LOG"
else
  OLD_GUID=$(cat "$GUID_FILE")
  NEW_GUID=$(generate_guid)
  if [ "$OLD_GUID" != "$NEW_GUID" ]; then
    echo "$NEW_GUID" > "$GUID_FILE"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] GUID changed: $OLD_GUID → $NEW_GUID" >> "$GUID_LOG"
  fi
fi

php-fpm &
nginx -g 'daemon off;'
