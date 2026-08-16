param(
    [string]$ProjectRoot = (Get-Location).Path,
    [string]$OutputDir = "",
    [string[]]$IncludeDirs = @("app", "bootstrap", "config", "database", "public", "resources", "routes", "tests"),
    [switch]$DryRun
)

$ErrorActionPreference = "Stop"

function Write-Utf8File {
    param(
        [Parameter(Mandatory = $true)][string]$Path,
        [Parameter(Mandatory = $true)][string]$Content
    )

    $directory = Split-Path -Parent $Path
    if (-not [string]::IsNullOrWhiteSpace($directory) -and -not (Test-Path -LiteralPath $directory)) {
        New-Item -ItemType Directory -Path $directory -Force | Out-Null
    }

    $utf8NoBom = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllText($Path, $Content, $utf8NoBom)
}

function Get-CodeFenceLanguage {
    param([Parameter(Mandatory = $true)][string]$RelativePath)

    if ($RelativePath -like "*.blade.php") { return "blade" }
    if ($RelativePath -like "*.php") { return "php" }
    if ($RelativePath -like "*.js") { return "javascript" }
    if ($RelativePath -like "*.css") { return "css" }
    if ($RelativePath -like "*.json") { return "json" }
    if ($RelativePath -like "*.sql") { return "sql" }
    if ($RelativePath -like "*.py") { return "python" }
    if ($RelativePath -like "*.yml" -or $RelativePath -like "*.yaml") { return "yaml" }
    if ($RelativePath -like "*.xml") { return "xml" }
    if ($RelativePath -like "*.md") { return "markdown" }
    if ($RelativePath -like "*.htaccess") { return "apache" }
    if ($RelativePath -like "*.env") { return "dotenv" }
    return "text"
}

function Get-SafeDocName {
    param([Parameter(Mandatory = $true)][string]$RelativePath)

    $safe = $RelativePath -replace '[\\\/:\*\?"<>|]', "__"
    $safe = $safe -replace "\s+", "_"
    return "$safe.md"
}

$ProjectRoot = [System.IO.Path]::GetFullPath($ProjectRoot)
if (-not (Test-Path -LiteralPath $ProjectRoot)) {
    throw "ProjectRoot nicht gefunden: $ProjectRoot"
}

if ([string]::IsNullOrWhiteSpace($OutputDir)) {
    $OutputDir = Join-Path $ProjectRoot "exports\outline-code-docs"
}
$OutputDir = [System.IO.Path]::GetFullPath($OutputDir)
$DocsDir = Join-Path $OutputDir "docs"

$excludeFragments = @(
    "\vendor\",
    "\node_modules\",
    "\.git\",
    "\storage\logs\",
    "\storage\framework\",
    "\bootstrap\cache\",
    "\public\build\",
    "\public\storage\"
)

$allowedExtensions = @(
    ".php", ".js", ".css", ".json", ".env", ".sql", ".py",
    ".xml", ".yml", ".yaml", ".md", ".txt", ".htaccess"
)

$allFiles = @()
foreach ($dir in $IncludeDirs) {
    $fullDir = Join-Path $ProjectRoot $dir
    if (-not (Test-Path -LiteralPath $fullDir)) {
        continue
    }

    $allFiles += Get-ChildItem -Path $fullDir -Recurse -File
}

$files = $allFiles | Where-Object {
    $fullName = $_.FullName
    $ext = $_.Extension.ToLowerInvariant()
    $isAllowedExtension = $allowedExtensions -contains $ext -or $_.Name -eq ".env" -or $_.Name -eq ".htaccess"
    if (-not $isAllowedExtension) { return $false }

    foreach ($fragment in $excludeFragments) {
        if ($fullName -like "*$fragment*") {
            return $false
        }
    }
    return $true
} | Sort-Object FullName

if ($DryRun) {
    Write-Output ("ProjectRoot: {0}" -f $ProjectRoot)
    Write-Output ("OutputDir:   {0}" -f $OutputDir)
    Write-Output ("Dateien:     {0}" -f $files.Count)
    $files | Select-Object -First 20 | ForEach-Object {
        $relative = $_.FullName.Substring($ProjectRoot.Length).TrimStart('\')
        Write-Output (" - {0}" -f $relative)
    }
    exit 0
}

if (Test-Path -LiteralPath $OutputDir) {
    Remove-Item -Path $OutputDir -Recurse -Force
}
New-Item -ItemType Directory -Path $DocsDir -Force | Out-Null

$indexLines = @()
$indexLines += "# Code-Export fuer Outline"
$indexLines += ""
$indexLines += "Dieses Verzeichnis wurde automatisch erzeugt."
$indexLines += ""
$indexLines += ("Exportzeit: {0}" -f (Get-Date -Format "yyyy-MM-dd HH:mm:ss"))
$indexLines += ('Projektpfad: `{0}`' -f $ProjectRoot)
$indexLines += ("Dateien: {0}" -f $files.Count)
$indexLines += ""
$indexLines += "## Dokumente"
$indexLines += ""

foreach ($file in $files) {
    $relativePath = $file.FullName.Substring($ProjectRoot.Length).TrimStart('\')
    $language = Get-CodeFenceLanguage -RelativePath $relativePath
    $docName = Get-SafeDocName -RelativePath $relativePath
    $docPath = Join-Path $DocsDir $docName
    $lastModified = $file.LastWriteTime.ToString("yyyy-MM-dd HH:mm:ss")

    $content = Get-Content -LiteralPath $file.FullName -Raw

    $doc = @(
        "# Datei: $relativePath",
        "",
        "> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.",
        "",
        ('- **Quelle:** `{0}`' -f $relativePath),
        "- **Stand:** $lastModified",
        "- **Typ:** $language",
        "",
        "## Code",
        "",
        ('```' + $language),
        $content,
        '```',
        ""
    ) -join [Environment]::NewLine

    Write-Utf8File -Path $docPath -Content $doc
    $indexLines += ("- [{0}](docs/{1})" -f $relativePath, $docName)
}

Write-Utf8File -Path (Join-Path $OutputDir "INDEX.md") -Content (($indexLines -join [Environment]::NewLine) + [Environment]::NewLine)

$readme = @(
    "# Verwendung in Outline",
    "",
    '1. Inhalt aus `docs/` in Outline importieren oder pro Datei kopieren.',
    '2. `INDEX.md` als Navigationsseite nutzen.',
    "3. Bei Bedarf den Export erneut ausfuehren, um den aktuellen Stand zu dokumentieren.",
    "",
    "Beispielaufruf:",
    "",
    '```powershell',
    "powershell -ExecutionPolicy Bypass -File .\scripts\export-outline-code-docs.ps1",
    '```',
    "",
    "Nur Vorschau (ohne Schreiben):",
    "",
    '```powershell',
    "powershell -ExecutionPolicy Bypass -File .\scripts\export-outline-code-docs.ps1 -DryRun",
    '```',
    ""
) -join [Environment]::NewLine

Write-Utf8File -Path (Join-Path $OutputDir "README.md") -Content $readme

Write-Output ("Export abgeschlossen: {0}" -f $OutputDir)
Write-Output ("Dokumente erstellt:   {0}" -f $files.Count)
