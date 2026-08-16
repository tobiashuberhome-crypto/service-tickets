# Datei: resources\views\pdf\layouts\base.blade.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `resources\views\pdf\layouts\base.blade.php`
- **Stand:** 2026-06-27 13:25:18
- **Typ:** blade

## Code

```blade
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'PDF-Dokument')</title>
    <style>
        @page { margin: 24mm 14mm 20mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 12px; line-height: 1.45; }
        .header { border-bottom: 2px solid {{ $pdfLayout['headline_color'] ?? '#1d4ed8' }}; padding-bottom: 10px; margin-bottom: 14px; }
        .brand { font-size: 22px; font-weight: bold; color: {{ $pdfLayout['headline_color'] ?? '#1d4ed8' }}; }
        .muted { color: {{ $pdfLayout['muted_color'] ?? '#667085' }}; }
        h1, h2, h3 { margin: 0 0 8px 0; }
        h1 { font-size: 20px; }
        h2 { font-size: 15px; margin-top: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #e5e7eb; padding: 6px 8px; vertical-align: top; }
        th { background: #f8fafc; text-align: left; font-weight: 700; }
        .grid { width: 100%; }
        .grid td { border: 0; padding: 2px 0; }
        .align-right { text-align: right; }
        .footer { position: fixed; bottom: -8mm; left: 0; right: 0; font-size: 10px; color: {{ $pdfLayout['muted_color'] ?? '#667085' }}; border-top: 1px solid #e5e7eb; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        @if (! empty($pdfLayout['logo_url']))
            <img src="{{ $pdfLayout['logo_url'] }}" alt="Logo" style="max-height: 48px; margin-bottom: 6px;">
        @endif
        <div class="brand">{{ $pdfLayout['company_name'] ?? config('app.name') }}</div>
        <div class="muted">@yield('subtitle')</div>
    </div>

    @yield('content')

    @if (($pdfLayout['show_footer'] ?? true) === true)
        <div class="footer">
            {{ $pdfLayout['footer_text'] ?? '' }} Â· {{ now()->format('d.m.Y H:i') }}
        </div>
    @endif
</body>
</html>

```
