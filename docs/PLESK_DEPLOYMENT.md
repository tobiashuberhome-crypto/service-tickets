# Plesk-Deployment für Service Tickets

Diese Anleitung beschreibt die Bereitstellung der Laravel-App `service-tickets` auf Plesk. Das Repository liegt im Verzeichnis `service-tickets.thss.online`; die eigentlichen Laravel-Schritte erfolgen danach per SSH oder Terminal im Plesk-Panel.

## 1. Voraussetzungen

- PHP 8.2+
- Composer 2
- MySQL/MariaDB
- Erweiterungen: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `curl`, `fileinfo`
- Domain/Subdomain mit aktivem HTTPS
- Zugriffsrechte auf das Webspace-Verzeichnis

## 2. Plesk-Vorbereitung

### 2.1 Website anlegen

- In Plesk: Websites & Domains
- Domain/Subdomain für die App anlegen, z. B. `ticket.thss.online`
- PHP-Version auf 8.2 setzen
- PHP-FPM aktivieren, falls erforderlich
- Dokumentroot auf das Laravel-`public`-Verzeichnis setzen, z. B.:

```text
/var/www/vhosts/thss.online/service-tickets.thss.online/public
```

Falls das Projekt in einem Unterordner liegt, dann auf den jeweiligen `public`-Ordner zeigen, nicht auf das Root-Verzeichnis des Projekts.

### 2.2 Datenbank erstellen

- In Plesk: Datenbanken
- Neue MySQL-Datenbank + Benutzer anlegen
- Zugangsdaten notieren:
  - DB_NAME
  - DB_USER
  - DB_PASSWORD
  - DB_HOST (typischerweise `localhost`)

## 3. Projekt auf den Server bringen

Das Projekt liegt im Verzeichnis:

```text
/var/www/vhosts/thss.online/service-tickets.thss.online
```

Die App ist danach unter `https://ticket.thss.online` erreichbar.

Das Domain-DocumentRoot muss auf `public` zeigen.

## 4. Laravel-Umgebung konfigurieren

Im Projektroot `.env` anlegen (aus `.env.example` kopieren):

```bash
cp .env.example .env
```

Danach die wichtigsten Werte anpassen:

```env
APP_NAME="Service Tickets"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ticket.thss.online
APP_TIMEZONE=Europe/Berlin

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

DOLIBARR_BASE_URL=https://dolibarr.example.com
DOLIBARR_API_KEY=YOUR_DOLIBARR_API_KEY
DOLIBARR_TIMEOUT=20

MAIL_MAILER=smtp
MAIL_HOST=mail.example.com
MAIL_PORT=587
MAIL_USERNAME=service@example.com
MAIL_PASSWORD=YOUR_MAIL_PASSWORD
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=service@example.com
MAIL_FROM_NAME="Service Tickets"

QUEUE_CONNECTION=sync
CACHE_STORE=file
SESSION_DRIVER=file

APP_BASIC_AUTH_USER=
APP_BASIC_AUTH_PASSWORD=

INTERNAL_API_TOKEN=YOUR_INTERNAL_API_TOKEN
```

Zusätzliche Konfigurationen für Rechnungen/Portal-Mails können ebenfalls aus `.env.example` übernommen werden.

## 5. Abhängigkeiten installieren

Im Projektroot ausführen:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan storage:link
```

Wenn Composer in Plesk nicht direkt verfügbar ist, ggf. über SSH oder Plesk Terminal ausführen.

## 6. Datenbank migrieren

```bash
php artisan migrate --force
```

Falls die Datenbank schon ein Schema aus einer älteren Exportbasis enthält, kann es nötig sein, gezielt vorhandene Migrations zu prüfen und nur die fehlenden Änderungen anzuwenden. In der Regel reicht aber `php artisan migrate --force`.

## 7. Cache und Performance optimieren

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 8. Dateirechte setzen

Der Plesk-PHP-Benutzer muss auf `storage`, `bootstrap/cache` und ggf. `public/uploads` schreiben können.

Typische Plesk-Benutzer sind `psacln` oder ein Site-User. Das genaue Kommando hängt vom Webspace ab.

Beispiel:

```bash
chown -R psacln:psaserv storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;
```

Wenn die Website unter einem anderen Benutzer läuft, den passenden Account verwenden.

## 9. Smoke Test nach dem Deployment

Nach dem Upload und der Konfiguration folgende Checks durchführen:

1. `https://ticket.thss.online/admin/login` aufrufen
2. `https://ticket.thss.online/tickets` prüfen
3. Test-Ticket anlegen
4. Dolibarr-Verbindung prüfen
5. Wenn relevante PDF/Portal-Features aktiv sind:
   - Ticket-Übersicht testen
   - Monatsrechnung testen
   - Geiser-/Cibena-Portal testen
6. Logs prüfen:

```bash
php artisan log:clear
```

und danach die App-Logs unter `storage/logs` kontrollieren.

## 10. Wichtige Hinweise

### Laravel in Plesk

- DocumentRoot immer auf `/public` setzen
- Keine `index.php` im Root-Verzeichnis aufrufen lassen
- `mod_rewrite` / URL-Rewriting muss aktiv sein
- `APP_KEY` darf nicht leer sein

### Dolibarr

Die App erwartet eine funktionierende Dolibarr-API. Vor dem Go-Live sicherstellen:

- REST API aktiv
- API-Key gültig
- Kunden-/Maschinen-/Auftragsdaten vorhanden
- Produkttypen und Service-Referenzen korrekt hinterlegt

### E-Mail

Wenn Mails für Rechnungen oder Portal-Links versendet werden sollen, muss in Plesk ein SMTP- oder Mail-Relay korrekt konfiguriert sein. Bei Test-/Staging-Deployments kann `MAIL_MAILER=log` verwendet werden, damit E-Mails nicht sofort versendet werden.

## 11. Checkliste vor Live-Start

- [ ] PHP 8.2 aktiv
- [ ] Composer-Dependencies installiert
- [ ] `.env` gesetzt
- [ ] Datenbank erstellt und migriert
- [ ] `APP_KEY` gesetzt
- [ ] `storage` und `bootstrap/cache` beschreibbar
- [ ] `public` als DocumentRoot gesetzt
- [ ] HTTPS aktiv
- [ ] Dolibarr-API erreichbar
- [ ] Testticket erfolgreich angelegt
- [ ] PDF-Downloads funktionieren
- [ ] Login/Portal funktionieren

## 12. Empfohlener Ablauf im Plesk-Workflow

1. Projekt in Webspace ziehen oder manuell aktualisieren
2. Plesk-PHP auf 8.2 setzen
3. `.env` anpassen
4. `composer install --no-dev --optimize-autoloader`
5. `php artisan migrate --force`
6. `php artisan config:cache`
7. `php artisan route:cache`
8. `php artisan view:cache`
9. Rechte prüfen
10. Browser-Smoke-Tests

Das ist der Standard-Deployment-Flow für `service-tickets` auf Plesk.
