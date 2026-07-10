# Deployment ohne Docker

Diese Anleitung geht von einem klassischen V-Server mit Webserver, PHP-FPM/Apache-PHP, Composer und MySQL/MariaDB aus.

## 1. Voraussetzungen

- PHP 8.2 oder neuer
- Composer 2
- MySQL 8 oder MariaDB 10.6+
- PHP-Erweiterungen: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `curl`, `fileinfo`
- Webserver: Apache oder Nginx
- Dolibarr 22.0.4 mit aktivierter REST API

## 2. Datenbank anlegen

```sql
CREATE DATABASE ticket_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ticket_user'@'localhost' IDENTIFIED BY 'BITTE_AENDERN';
GRANT ALL PRIVILEGES ON ticket_app.* TO 'ticket_user'@'localhost';
FLUSH PRIVILEGES;
```

Optional kannst du die Tabellen direkt mit dem fertigen SQL-Skript anlegen:

```bash
mysql -u ticket_user -p ticket_app < database/sql/ticket_app_schema.sql
```

Wenn du dieses SQL-Skript importierst, ist `php artisan migrate --seed` nicht mehr noetig. Die Tabelle `migrations` wird im SQL-Skript bereits gefuellt, damit Laravel die Tabellen nicht erneut anlegen will.

## 3. Dateien auf den Server kopieren

Beispielpfad auf deinem Server:

```bash
/var/www/ticket-system
```

Der Webserver muss auf diesen Ordner zeigen:

```bash
/var/www/ticket-system/public
```

## 4. Installation

```bash
cd /var/www/ticket-system
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

Dann `.env` bearbeiten:

```env
APP_URL=https://tickets.example.com

DB_DATABASE=ticket_app
DB_USERNAME=ticket_user
DB_PASSWORD=BITTE_AENDERN

DOLIBARR_BASE_URL=https://dolibarr.example.com
DOLIBARR_API_KEY=DEIN_DOLIBARR_API_KEY

APP_BASIC_AUTH_USER=service
APP_BASIC_AUTH_PASSWORD=BITTE_AENDERN
```

Anschliessend:

```bash
php artisan migrate --seed
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Wenn du vorher `database/sql/ticket_app_schema.sql` importiert hast, lasse `php artisan migrate --seed` weg.

Fuer eine bestehende Installation mit dem ersten Schema kannst du alternativ nur das Update fuer Lagerplaetze, Scanner und Bestandsbewegungen einspielen:

```bash
mysql -u ticket_user -p ticket_app < database/sql/2026_06_19_update_storage_scan.sql
```

## 5. Dateirechte

Der Webserver-Benutzer braucht Schreibrechte auf:

```bash
storage
bootstrap/cache
```

Beispiel:

```bash
chown -R www-data:www-data storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;
```

## 6. Nginx-Beispiel

```nginx
server {
    listen 80;
    server_name tickets.example.com;
    root /var/www/ticket-system/public;

    index index.php index.html;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

TLS/HTTPS sollte ueber Certbot oder deinen bestehenden Reverse Proxy aktiviert werden.

## 7. Apache-Beispiel

```apache
<VirtualHost *:80>
    ServerName tickets.example.com
    DocumentRoot /var/www/ticket-system/public

    <Directory /var/www/ticket-system/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/service-tickets-error.log
    CustomLog ${APACHE_LOG_DIR}/service-tickets-access.log combined
</VirtualHost>
```

`mod_rewrite` muss aktiv sein.

```bash
a2enmod rewrite
systemctl reload apache2
```

## 8. Dolibarr vorbereiten

In Dolibarr muessen vorhanden sein:

- REST API aktiviert
- API-Key mit Rechten fuer Kunden, Produkte und Auftraege
- Produkt-Extrafield `hersteller`
- Serviceprodukte mit den Referenzen:
  - `NM-Klein`
  - `NM-Service`
  - `VDE`

## 9. Erste Pruefung

1. `/tickets` im Browser oeffnen.
2. Ersatzteil unter `/spare-parts` anlegen.
3. PDF-Link unter `/machine-documents` fuer eine Dolibarr-Maschinen-Produkt-ID anlegen.
4. Neues Ticket erstellen.
5. Kunde in Dolibarr suchen oder anlegen.
6. Maschine in Dolibarr suchen oder anlegen.
7. Ticket speichern.
8. Pruefen, ob Dolibarr-Auftrag als Entwurf angelegt wurde.
9. Ersatzteil hinzufuegen.
10. Ticket als erledigt speichern.
11. Pruefen, ob Serviceleistungen und Ersatzteilpositionen im Dolibarr-Auftrag stehen.

## 10. Betrieb

Fuer diese erste Version ist `QUEUE_CONNECTION=sync` vorgesehen. Spaeter kann fuer langsamere Dolibarr- oder NextCloud-Aktionen auf `database` oder Redis umgestellt werden.

Backups sollten mindestens enthalten:

- MySQL-Datenbank `ticket_app`
- `.env`
- Anwendungscode

Das Ersatzteil- und Kompatibilitaetswissen liegt in der neuen Datenbank und sollte daher regelmaessig gesichert werden.
