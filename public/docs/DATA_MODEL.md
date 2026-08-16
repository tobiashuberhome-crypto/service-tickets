# Datenmodell

## Fuehrende Systeme

| Bereich | Fuehrendes System |
| --- | --- |
| Kunden | Dolibarr |
| Maschinentypen | Dolibarr Produkte |
| Serviceleistungen | Dolibarr Produkte/Leistungen |
| Kundenauftraege | Dolibarr |
| Seriennummern konkreter Kundenmaschinen | Ticket-App |
| Ersatzteilkatalog | Ticket-App |
| Ersatzteil-Kompatibilitaeten | Ticket-App |
| PDF-Verknuepfungen | Ticket-App |

## Wichtigste Tabellen

### `tickets`

Speichert den Service-/Reparaturauftrag, Status, Datumsfelder und Dolibarr-Auftragsreferenz.

### `customer_machines`

Verknuepft Dolibarr-Kunde, Dolibarr-Maschinentyp und Seriennummer.

### `spare_parts`

Lokaler Ersatzteilkatalog mit Einkaufs-/Verkaufspreisen, MwSt., Lieferantendaten und einfachem Bestand.

### `machine_spare_part_compatibilities`

Ordnet lokale Ersatzteile einem oder mehreren Dolibarr-Maschinentypen zu.

### `ticket_parts`

Speichert die am Ticket verwendeten Ersatzteile als Snapshot. Dadurch bleiben alte Tickets nachvollziehbar, auch wenn sich spaeter Preise oder Bezeichnungen im Ersatzteilkatalog aendern.

### `ticket_service_lines`

Speichert vorbereitete Dolibarr-Serviceleistungen je Ticket. Beim Abschluss werden diese als echte Dolibarr-Produktpositionen uebertragen.

### `machine_documents`

Speichert PDF-/NextCloud-Links je Dolibarr-Maschinentyp.

### `sync_logs`

Protokolliert Dolibarr-Synchronisationen und Fehler.
