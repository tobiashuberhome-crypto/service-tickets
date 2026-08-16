# Datei: resources\views\partials\alerts.blade.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `resources\views\partials\alerts.blade.php`
- **Stand:** 2026-06-27 13:25:18
- **Typ:** blade

## Code

```blade
@if (session('status'))
    <div class="alert success">{{ session('status') }}</div>
@endif

@if (session('warning'))
    <div class="alert warning">{{ session('warning') }}</div>
@endif

@if ($errors->any())
    <div class="errors">
        <strong>Bitte pruefen:</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

```
