# Service Ticket App

Eine schlanke Laravel-Anwendung fuer Service- und Reparaturtickets mit Dolibarr-Anbindung und eigener Ersatzteilverwaltung.

## Zielbild

- Dolibarr bleibt fuehrend fuer Kunden, Maschinentypen, Leistungen und Kundenauftraege.
- Die Ticket-App speichert Tickets, konkrete Kundenmaschinen mit Seriennummer, Ersatzteile, Kompatibilitaeten, PDF-Verknuepfungen und Status.
- Ersatzteile werden lokal gepflegt und beim Abschluss als freie Auftragspositionen an Dolibarr uebergeben.
- Die Dolibarr-Auftraege werden erst beim ersten Speichern eines Tickets angelegt.

## Technischer Stack

- PHP 8.2 oder neuer
- Laravel 12
- MySQL oder MariaDB
- Klassischer Betrieb ohne Docker
- Blade-Frontend ohne Node/Vite-Build

## Lokale/Server-Installation

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan config:cache
php artisan route:cache
```

Der Webserver muss auf den Ordner `public/` zeigen.

## Wichtige Umgebungsvariablen

```env
APP_URL=https://tickets.example.com

DB_DATABASE=ticket_app
DB_USERNAME=ticket_user
DB_PASSWORD=secret

DOLIBARR_BASE_URL=https://dolibarr.example.com
DOLIBARR_API_KEY=...

APP_BASIC_AUTH_USER=service
APP_BASIC_AUTH_PASSWORD=...
```

Wenn `APP_BASIC_AUTH_USER` und `APP_BASIC_AUTH_PASSWORD` leer bleiben, ist der eingebaute Basic-Auth-Schutz deaktiviert. In Produktion sollte die App entweder per Basic Auth, VPN oder Reverse-Proxy-Auth geschuetzt werden.

## Deployment ohne Docker

Siehe [DEPLOYMENT.md](DEPLOYMENT.md).

Beispielpfad fuer deinen Server:

```bash
/var/www/ticket-system
```

## Kundenportal

Diese Version enthaelt einen einfachen Kundenportal-Prozess:

- Oeffentliche Seite `/kundenportal` fuer Registrierungs-/Identifikationsanfragen.
- Oeffentliche Seite `/kundenportal/login` zum Anfordern eines Magic Links.
- Interne Ansicht `/kundenanfragen` zur Pruefung neuer Kundenanfragen.
- Interne Verknuepfung einer Anfrage mit einem bestehenden Dolibarr-Kunden.
- Optionales Anlegen eines neuen Dolibarr-Kunden aus einer Anfrage.
- Automatische Erstellung/Aktualisierung eines Portalzugangs nach interner Verknuepfung.
- Magic Links sind 30 Minuten gueltig und koennen nur einmal verwendet werden.
- Eingeloggte Kunden koennen ueber `/kundenportal/uebersicht` eigene Tickets erstellen und sehen.

### Neue Migrationen

Nach dem Einspielen ausfuehren:

```bash
php artisan migrate
```

Dabei werden folgende Tabellen/Felder ergaenzt:

- `customer_portal_requests`
- `customer_portal_accounts`
- `customer_portal_magic_links`
- Portal-Felder in `tickets`, z. B. `created_via_customer_portal` und `customer_portal_account_id`

### Mail-Konfiguration fuer Magic Links

Standardmaessig ist `MAIL_MAILER=log` gesetzt, damit Links in der Laravel-Logdatei landen. Fuer den echten Versand muss SMTP oder ein anderer Mailer konfiguriert werden, z. B. ueber:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=service@example.com
MAIL_FROM_NAME="Service Tickets"
```

Die Kundenportal-Seiten sind vom Basic-Auth-Schutz ausgenommen. Die interne Pruefansicht `/kundenanfragen` bleibt durch den bestehenden Basic-Auth-Schutz geschuetzt.
