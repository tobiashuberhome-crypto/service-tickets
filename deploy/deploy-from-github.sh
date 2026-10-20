#!/bin/bash
# Deployment von GitHub (main) auf ticket.thss.online, in einem Aufruf.
#
# Als root auf dem Server ausfuehren:
#   bash /var/www/vhosts/thss.online/deploy-tools/service-tickets-deploy.sh            # deployen
#   bash /var/www/vhosts/thss.online/deploy-tools/service-tickets-deploy.sh --dry-run  # nur zeigen, was kaeme
#
# Ablauf: GitHub-Stand holen -> Backup (DB + Projekt) -> Plesk-Checkout -> Dateien gegen den
# Git-Stand pruefen -> deploy-service-tickets.sh (Composer, Migrationen, Caches) -> Eigentuemer
# zuruecksetzen -> PHP-FPM neu starten -> Smoke-Test.
#
# WICHTIG: Dieses Skript liegt bewusst AUSSERHALB des Projektordners. Der Plesk-Checkout
# ersetzt Dateien im Projekt, ein Skript, das sich dabei selbst ueberschreibt, laeuft unkontrolliert
# weiter. Aenderungen hier im Repo (deploy/) muessen von Hand in den Pfad oben kopiert werden.

set -u

DOMAIN="ticket.thss.online"
REPO_NAME="service-tickets.git"
BARE="/var/www/vhosts/thss.online/git/service-tickets.git"
APP="/var/www/vhosts/thss.online/service-tickets.thss.online"
BACKUP_ROOT="/var/www/vhosts/thss.online/deploy-backups/service-tickets"
MARKER="$BACKUP_ROOT/.deployed-commit"
APP_USER="tobiashuber"
APP_GROUP="psacln"
URL="https://ticket.thss.online"
KEEP_PRE_BACKUPS=5
LOCK="/var/lock/service-tickets-deploy.lock"

DRY_RUN=0
[ "${1:-}" = "--dry-run" ] && DRY_RUN=1

say()  { printf '\n[%s] %s\n' "$(date +%H:%M:%S)" "$*"; }
fail() { printf '\nABBRUCH: %s\n' "$*" >&2; [ -n "${PRE_BACKUP:-}" ] && printf 'Backup vor dem Checkout: %s\n' "$PRE_BACKUP" >&2; exit 1; }

[ "$(id -u)" -eq 0 ] || fail "Bitte als root ausfuehren."
[ -d "$APP" ] && [ -d "$BARE" ] || fail "Projekt oder Bare-Repo fehlt."

# Nie zwei Deploys gleichzeitig (flock statt pgrep: pgrep findet sonst den eigenen Aufruf).
exec 9>"$LOCK"
flock -n 9 || fail "Es laeuft bereits ein Deploy."

say "GitHub-Stand holen"
plesk ext git --fetch -domain "$DOMAIN" -name "$REPO_NAME" >/dev/null 2>&1 || fail "plesk ext git --fetch ist fehlgeschlagen."

# Plesk laeuft als root; ein root-eigenes Objekt im Bare-Repo bricht spaetere Fetches ab.
FOREIGN="$(find "$BARE/objects" ! -user "$APP_USER" 2>/dev/null | wc -l)"
if [ "$FOREIGN" -gt 0 ]; then
    say "$FOREIGN Objekte im Bare-Repo gehoeren nicht $APP_USER - korrigiere"
    chown -R "$APP_USER:$APP_GROUP" "$BARE/objects"
fi

NEW="$(git --git-dir="$BARE" rev-parse main 2>/dev/null)" || fail "Branch main fehlt im Bare-Repo."
REMOTE="$(git --git-dir="$BARE" ls-remote --heads origin main 2>/dev/null | cut -f1)"
[ -z "$REMOTE" ] || [ "$REMOTE" = "$NEW" ] || fail "Bare-Repo ($NEW) ist nicht auf dem Stand von GitHub ($REMOTE). Fetch erneut versuchen."

OLD="$(cat "$MARKER" 2>/dev/null || true)"
echo "Zuletzt deployt: ${OLD:-unbekannt}"
echo "Jetzt auf main:  $NEW"

if [ -n "$OLD" ] && git --git-dir="$BARE" cat-file -e "$OLD^{commit}" 2>/dev/null; then
    if [ "$OLD" = "$NEW" ]; then
        echo "Keine neuen Commits seit dem letzten Deploy."
    else
        echo "Neue Commits:"
        git --git-dir="$BARE" log --format='  %h %ad %s' --date=short "$OLD..$NEW"
        echo "Geaenderte Dateien:"
        git --git-dir="$BARE" diff --stat "$OLD" "$NEW" | tail -n 25
        git --git-dir="$BARE" diff --name-only "$OLD" "$NEW" | grep -q '^database/migrations/' \
            && echo ">> ENTHAELT MIGRATIONEN (laufen im Deploy-Skript, mit Backup davor)"
    fi
fi

if [ "$DRY_RUN" -eq 1 ]; then
    say "Dry-Run: nichts veraendert (ausser dem Fetch ins Bare-Repo)."
    exit 0
fi

PRE_BACKUP="$BACKUP_ROOT/vor-github-deploy-$(date +%Y%m%d-%H%M%S)"
say "Backup vor dem Checkout: $PRE_BACKUP"
mkdir -p "$PRE_BACKUP"
env_value() { grep -E "^[[:space:]]*$1=" "$APP/.env" | tail -n 1 | cut -d= -f2- | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//' -e "s/^[\"']//" -e "s/[\"']\$//"; }
DB_NAME="$(env_value DB_DATABASE)"; DB_USER="$(env_value DB_USERNAME)"; DB_PASS="$(env_value DB_PASSWORD)"
DB_HOST="$(env_value DB_HOST)"; DB_HOST="${DB_HOST:-127.0.0.1}"
[ -n "$DB_NAME" ] && [ -n "$DB_USER" ] || fail "DB-Zugangsdaten in .env nicht gefunden."
MYSQL_PWD="$DB_PASS" mysqldump --host="$DB_HOST" --user="$DB_USER" --single-transaction --quick --routines --triggers \
    --default-character-set=utf8mb4 "$DB_NAME" | gzip -9 > "$PRE_BACKUP/database.sql.gz"
gzip -t "$PRE_BACKUP/database.sql.gz" && [ -s "$PRE_BACKUP/database.sql.gz" ] || fail "Datenbank-Backup ist leer oder defekt."
tar -C "$APP" --exclude=./vendor --exclude=./storage/logs -czf "$PRE_BACKUP/app.tar.gz" . || fail "Projekt-Backup fehlgeschlagen."
tar -tzf "$PRE_BACKUP/app.tar.gz" >/dev/null || fail "Projekt-Backup ist defekt."
echo "Backup ok: $(du -sh "$PRE_BACKUP" | cut -f1)"

say "Plesk-Checkout"
plesk ext git --deploy -domain "$DOMAIN" -name "$REPO_NAME" >/dev/null 2>&1 || fail "plesk ext git --deploy ist fehlgeschlagen."

say "Dateien gegen den Git-Stand pruefen"
MISMATCH=0
CHECK_LIST="$(if [ -n "$OLD" ] && git --git-dir="$BARE" cat-file -e "$OLD^{commit}" 2>/dev/null && [ "$OLD" != "$NEW" ]; then
    git --git-dir="$BARE" diff --name-only --diff-filter=AM "$OLD" "$NEW"
else
    git --git-dir="$BARE" ls-tree -r --name-only "$NEW" | grep -E '^(app|routes|resources|public/css|config|database)/' | head -n 300
fi)"
while IFS= read -r f; do
    [ -n "$f" ] || continue
    want="$(git --git-dir="$BARE" rev-parse "$NEW:$f" 2>/dev/null)" || continue
    have="$(git hash-object -- "$APP/$f" 2>/dev/null || echo fehlt)"
    if [ "$want" != "$have" ]; then echo "  ABWEICHUNG: $f"; MISMATCH=$((MISMATCH + 1)); fi
done <<< "$CHECK_LIST"
[ "$MISMATCH" -eq 0 ] || fail "$MISMATCH Datei(en) stimmen nach dem Checkout nicht mit $NEW ueberein."
echo "Alle geprueften Dateien stimmen mit $NEW ueberein."

say "deploy-service-tickets.sh (Composer, Migrationen, Caches)"
LOG="/tmp/deploy-st-$(date +%Y%m%d-%H%M%S).log"
# In eine Datei, nie durch head: ein vorzeitig schliessendes Pipe-Ende wuerde das Skript abbrechen.
( cd "$APP" && sh deploy-service-tickets.sh > "$LOG" 2>&1 )
RC=$?
tail -n 8 "$LOG"
[ "$RC" -eq 0 ] || fail "deploy-service-tickets.sh endete mit Code $RC. Log: $LOG. Rueckgaengig: tar -C $APP -xzf $PRE_BACKUP/app.tar.gz (DB-Dump: $PRE_BACKUP/database.sql.gz)"

say "Eigentuemer zuruecksetzen (das Skript laeuft als root)"
chown -R "$APP_USER:$APP_GROUP" "$APP/storage" "$APP/bootstrap/cache"
echo "Root-eigene Dateien uebrig: $(find "$APP/storage" "$APP/bootstrap/cache" ! -user "$APP_USER" | wc -l)"

say "PHP-FPM neu starten"
systemctl restart plesk-php84-fpm || fail "PHP-FPM-Neustart fehlgeschlagen."
sleep 2

say "Smoke-Test"
SMOKE_FAIL=0
probe() { # Pfad, erlaubte Codes (Leerzeichen getrennt)
    code="$(curl -s -o /dev/null -w '%{http_code}' --max-time 20 "$URL$1")"
    case " $2 " in *" $code "*) echo "  ok   $1 -> $code" ;; *) echo "  FEHLER $1 -> $code (erwartet: $2)"; SMOKE_FAIL=1 ;; esac
}
probe /admin/login "200"
probe /heute "302"
probe /tickets "302"
probe /css/app.css "200"
PENDING="$(cd "$APP" && php artisan migrate:status 2>/dev/null | grep -ci pending)"
echo "  Ausstehende Migrationen: $PENDING"
[ "$PENDING" = "0" ] || SMOKE_FAIL=1
NEW_ERRORS="$(grep -c "^\[$(date +%Y-%m-%d)" "$APP/storage/logs/laravel.log" 2>/dev/null || true)"
echo "  Fehlereintraege im Laravel-Log heute: ${NEW_ERRORS:-0}"

if [ "$SMOKE_FAIL" -ne 0 ]; then
    printf '\nDEPLOY DURCHGELAUFEN, ABER SMOKE-TEST ROT.\nRueckgaengig: tar -C %s -xzf %s/app.tar.gz  (DB-Dump: %s/database.sql.gz)\n' "$APP" "$PRE_BACKUP" "$PRE_BACKUP" >&2
    exit 2
fi

echo "$NEW" > "$MARKER"

# Nur die letzten KEEP_PRE_BACKUPS Backups dieser Art behalten.
ls -1d "$BACKUP_ROOT"/vor-github-deploy-* 2>/dev/null | sort -r | tail -n "+$((KEEP_PRE_BACKUPS + 1))" | while IFS= read -r old; do rm -rf -- "$old"; done

printf '\n==================================================\n DEPLOYMENT ERFOLGREICH: %s\n Backup: %s\n==================================================\n' "$NEW" "$PRE_BACKUP"
