# Dolibarr-Anbindung

Die Anbindung ist in `app/Services/Dolibarr/DolibarrClient.php` gekapselt.

## Aktuell genutzte API-Bereiche

- `GET /api/index.php/thirdparties`
- `POST /api/index.php/thirdparties`
- `GET /api/index.php/products`
- `POST /api/index.php/products`
- `POST /api/index.php/orders`
- `GET /api/index.php/orders/{id}`
- `POST /api/index.php/orders/{id}/lines`

## Extrafield `hersteller`

Die App sendet beim Anlegen einer Maschine:

```php
'array_options' => [
    'options_hersteller' => $manufacturer,
]
```

Falls deine Dolibarr-Instanz das Extrafield technisch anders ausliefert, muss nur `DolibarrClient::mapProduct()` angepasst werden.

## Ersatzteile

Ersatzteile werden nicht als Dolibarr-Produkte vorausgesetzt. Beim Abschluss eines Tickets erzeugt die App freie Auftragspositionen mit:

- Ersatzteilnummer
- Bezeichnung
- Beschreibung
- Menge
- Verkaufspreis netto
- MwSt.

Dadurch bleibt die Dolibarr-Produktliste klein.
