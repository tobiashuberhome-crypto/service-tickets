# Verwendung in Outline

1. Inhalt aus `docs/` in Outline importieren oder pro Datei kopieren.
2. `INDEX.md` als Navigationsseite nutzen.
3. Bei Bedarf den Export erneut ausfuehren, um den aktuellen Stand zu dokumentieren.

Beispielaufruf:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\export-outline-code-docs.ps1
```

Nur Vorschau (ohne Schreiben):

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\export-outline-code-docs.ps1 -DryRun
```
