#!/bin/sh

REPO_DIR="/var/www/vhosts/thss.online/service-tickets.thss.online"
APP_URL="https://ticket.thss.online"
BACKUP_ROOT="/var/www/vhosts/thss.online/deploy-backups/service-tickets"
BACKUP_RETENTION_COUNT=6
PLESK_PHP_BIN="/opt/plesk/php/8.2/bin/php"

TIMESTAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="$BACKUP_ROOT/$TIMESTAMP"

log() {
    printf '\n[%s] %s\n' "$(date +%H:%M:%S)" "$*"
}

fail() {
    printf '\nFEHLER: %s\n' "$*" >&2
    exit 1
}

require_cmd() {
    command -v "$1" >/dev/null 2>&1 || fail "Benötigter Befehl fehlt: $1"
}

find_php() {
    if [ -x "$PLESK_PHP_BIN" ]; then
        printf '%s' "$PLESK_PHP_BIN"
        return
    fi
    command -v php || return 1
}

env_value() {
    key="$1"
    file="$2"
    value="$(grep -E "^[[:space:]]*${key}=" "$file" | tail -n 1 | cut -d= -f2- || true)"
    value="$(printf '%s' "$value" | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//')"

    case "$value" in
        \"*\") value="${value#\"}"; value="${value%\"}" ;;
        \'*\') value="${value#\'}"; value="${value%\'}" ;;
    esac

    printf '%s' "$value"
}

rollback() {
    log "Rollback wird versucht ..."
    if [ -f "$BACKUP_DIR/app.tar.gz" ]; then
        rm -rf -- "$REPO_DIR"/*
        tar -C "$REPO_DIR" -xzf "$BACKUP_DIR/app.tar.gz"
    fi
    log "Rollback abgeschlossen."
}

on_error() {
    exit_code=$?
    line_no="${1:-unknown}"

    printf '\n==================================================\n' >&2
    printf 'DEPLOYMENT FEHLGESCHLAGEN\n' >&2
    printf 'Zeile: %s\n' "$line_no" >&2
    printf 'Exit-Code: %s\n' "$exit_code" >&2
    printf 'Backup: %s\n' "$BACKUP_DIR" >&2
    printf '==================================================\n' >&2

    [ -d "$BACKUP_DIR" ] && rollback || true
    exit "$exit_code"
}

trap 'on_error $LINENO' 0 1 2 3 15

echo "=================================================="
echo " Service Tickets - Backup + Deploy"
echo " Zeitpunkt: $TIMESTAMP"
echo "=================================================="

require_cmd tar
require_cmd gzip
require_cmd composer

PHP_BIN="$(find_php)" || fail "Kein PHP-Binary gefunden."

log "Projekt prüfen"
[ -d "$REPO_DIR" ] || fail "Projekt fehlt: $REPO_DIR"
[ -f "$REPO_DIR/.env" ] || fail ".env fehlt: $REPO_DIR/.env"
[ -d "$REPO_DIR/public" ] || fail "public-Verzeichnis fehlt: $REPO_DIR/public"

echo "PHP: $PHP_BIN"
"$PHP_BIN" -v | head -n 1

mkdir -p "$BACKUP_DIR"

log "Datenbank-Konfiguration lesen"
ENV_FILE="$REPO_DIR/.env"
DB_CONNECTION="$(env_value DB_CONNECTION "$ENV_FILE")"
DB_HOST="$(env_value DB_HOST "$ENV_FILE")"
DB_PORT="$(env_value DB_PORT "$ENV_FILE")"
DB_DATABASE="$(env_value DB_DATABASE "$ENV_FILE")"
DB_USERNAME="$(env_value DB_USERNAME "$ENV_FILE")"
DB_PASSWORD="$(env_value DB_PASSWORD "$ENV_FILE")"

DB_CONNECTION="${DB_CONNECTION:-mysql}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"

case "$DB_CONNECTION" in
    mysql|mariadb) ;;
    *) fail "DB-Backup aktuell nur für MySQL/MariaDB unterstützt. DB_CONNECTION=$DB_CONNECTION" ;;
esac

[ -n "$DB_DATABASE" ] || fail "DB_DATABASE ist leer."
[ -n "$DB_USERNAME" ] || fail "DB_USERNAME ist leer."

if command -v mysqldump >/dev/null 2>&1; then
    DB_DUMP_BIN="$(command -v mysqldump)"
elif command -v mariadb-dump >/dev/null 2>&1; then
    DB_DUMP_BIN="$(command -v mariadb-dump)"
else
    fail "Weder mysqldump noch mariadb-dump gefunden."
fi

log "Datenbank sichern: $DB_DATABASE"
MYSQL_PWD="$DB_PASSWORD" "$DB_DUMP_BIN" \
    --host="$DB_HOST" \
    --port="$DB_PORT" \
    --user="$DB_USERNAME" \
    --single-transaction \
    --quick \
    --routines \
    --triggers \
    --events \
    --default-character-set=utf8mb4 \
    "$DB_DATABASE" \
    | gzip -9 > "$BACKUP_DIR/database.sql.gz"

test -s "$BACKUP_DIR/database.sql.gz" || fail "Datenbank-Backup ist leer."

log "Projekt sichern"
tar -C "$REPO_DIR" -czf "$BACKUP_DIR/app.tar.gz" .
cp -a "$REPO_DIR/.env" "$BACKUP_DIR/app.env"

log "Composer-Abhängigkeiten installieren"
cd "$REPO_DIR"
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

log "Laravel optimieren"
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan migrate --force
"$PHP_BIN" artisan storage:link || true
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache

log "Dateirechte setzen"
mkdir -p "$REPO_DIR/storage" "$REPO_DIR/bootstrap/cache"
chmod -R ug+rwX "$REPO_DIR/storage" "$REPO_DIR/bootstrap/cache"

log "Deployment-Metadaten speichern"
{
    echo "timestamp=$TIMESTAMP"
    echo "app_url=$APP_URL"
    echo "php=$("$PHP_BIN" -r 'echo PHP_VERSION;')"
    echo "composer=$(composer --version 2>/dev/null || true)"
} > "$BACKUP_DIR/deploy-meta.txt"

log "Nur die letzten ${BACKUP_RETENTION_COUNT} Backups behalten"
find "$BACKUP_ROOT" -mindepth 1 -maxdepth 1 -type d -printf '%f\n' \
    | sort -r \
    | tail -n "+$((BACKUP_RETENTION_COUNT + 1))" \
    | while IFS= read -r old_backup; do
        [ -n "$old_backup" ] || continue
        log "Altes Backup löschen: $old_backup"
        rm -rf -- "$BACKUP_ROOT/$old_backup"
      done

echo
echo "=================================================="
echo " DEPLOYMENT ERFOLGREICH"
echo "=================================================="
echo "App:    $APP_URL"
echo "Repo:   $REPO_DIR"
echo "Backup: $BACKUP_DIR"
echo "=================================================="
