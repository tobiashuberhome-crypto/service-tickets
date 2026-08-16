param(
    [string]$DocsPath = "D:\github\exports\outline-code-docs\docs",
    [string]$OutlineUrl = "https://wiki.thss.online",
    [string]$CollectionId = "bf538c26-3c8b-4cf0-9bd0-d9e09f4ac3bb",
    [string]$ParentDocumentId = "",
    [string]$ApiToken,
    [string]$ImportedLogPath = "D:\github\exports\outline-code-docs\imported-files.txt",
    [string]$FailedLogPath = "D:\github\exports\outline-code-docs\failed-files.txt",
    [int]$TimeoutSec = 120,
    [switch]$SkipAlreadyImported
)

$ErrorActionPreference = "Stop"

function Get-RelativePath {
    param(
        [string]$BasePath,
        [string]$FullPath
    )

    $base = [System.IO.Path]::GetFullPath($BasePath).TrimEnd("\", "/")
    $full = [System.IO.Path]::GetFullPath($FullPath)

    return $full.Substring($base.Length).TrimStart("\", "/")
}

function Get-TitleFromFile {
    param(
        [System.IO.FileInfo]$File,
        [string]$DocsPath
    )

    $relativePath = Get-RelativePath -BasePath $DocsPath -FullPath $File.FullName
    $withoutExtension = [System.IO.Path]::ChangeExtension($relativePath, $null)

    # Exportnamen wie app__Http__Controllers__X.php.md lesbarer machen
    $withoutExtension = $withoutExtension -replace "__", "\"
    $withoutExtension = $withoutExtension.TrimEnd(".")

    return "Datei: $withoutExtension"
}

function Write-Ok {
    param([string]$Message)
    Write-Host "OK   $Message" -ForegroundColor Green
}

function Write-Warn {
    param([string]$Message)
    Write-Host "WARN $Message" -ForegroundColor Yellow
}

function Write-Fail {
    param([string]$Message)
    Write-Host "FAIL $Message" -ForegroundColor Red
}

if ([string]::IsNullOrWhiteSpace($ApiToken)) {
    Write-Fail "Bitte echten Outline API Token angeben."
    Write-Host ".\import-all-md-to-outline.ps1 -ApiToken `"ol_api_xxx`""
    exit 1
}

if (-not (Test-Path $DocsPath)) {
    Write-Fail "DocsPath nicht gefunden: $DocsPath"
    exit 1
}

$headers = @{
    "Authorization" = "Bearer $ApiToken"
    "Content-Type"  = "application/json"
}

$files = Get-ChildItem -Path $DocsPath -Filter "*.md" -Recurse | Sort-Object FullName

if ($files.Count -eq 0) {
    Write-Warn "Keine .md-Dateien gefunden in: $DocsPath"
    exit 0
}

$alreadyImported = New-Object System.Collections.Generic.HashSet[string]

if ($SkipAlreadyImported -and (Test-Path $ImportedLogPath)) {
    Get-Content $ImportedLogPath | ForEach-Object {
        $line = $_.Trim()
        if ($line) {
            [void]$alreadyImported.Add($line)
        }
    }
}

Write-Host ""
Write-Host "==> Import vorbereitet" -ForegroundColor Cyan
Write-Host "DocsPath:         $DocsPath"
Write-Host "OutlineUrl:       $OutlineUrl"
Write-Host "CollectionId:     $CollectionId"
Write-Host "ParentDocumentId: $ParentDocumentId"
Write-Host "Dateien:          $($files.Count)"
Write-Host "Direkt publish:   JA"
Write-Host "Skip Log:         $SkipAlreadyImported"
Write-Host "Imported Log:     $ImportedLogPath"
Write-Host "Failed Log:       $FailedLogPath"

$success = 0
$failed = 0
$skipped = 0
$current = 0

foreach ($file in $files) {
    $current++

    $relativePath = Get-RelativePath -BasePath $DocsPath -FullPath $file.FullName
    $title = Get-TitleFromFile -File $file -DocsPath $DocsPath

    Write-Progress `
        -Activity "Importiere Markdown nach Outline" `
        -Status "$current von $($files.Count): $relativePath" `
        -PercentComplete (($current / $files.Count) * 100)

    if ($SkipAlreadyImported -and $alreadyImported.Contains($relativePath)) {
        $skipped++
        Write-Warn "[$current/$($files.Count)] Überspringe bereits importiert: $relativePath"
        continue
    }

    if ($file.Length -eq 0) {
        $skipped++
        Write-Warn "[$current/$($files.Count)] Überspringe leere Datei: $relativePath"
        continue
    }

    try {
        Write-Host ""
        Write-Host "[$current/$($files.Count)] Importiere: $relativePath"
        Write-Host "Titel: $title"

        $text = Get-Content -Path $file.FullName -Raw -Encoding UTF8

        if ([string]::IsNullOrWhiteSpace($text)) {
            $skipped++
            Write-Warn "Leerer Inhalt: $relativePath"
            continue
        }

        $bodyObject = @{
            title        = $title
            text         = $text
            collectionId = $CollectionId
            publish      = $true
        }

        if (-not [string]::IsNullOrWhiteSpace($ParentDocumentId)) {
            $bodyObject.parentDocumentId = $ParentDocumentId
        }

        $body = $bodyObject | ConvertTo-Json -Depth 20

        $response = Invoke-RestMethod `
            -Uri "$OutlineUrl/api/documents.create" `
            -Method Post `
            -Headers $headers `
            -Body $body `
            -TimeoutSec $TimeoutSec

        if ($response.ok -eq $true -and $response.data) {
            $success++

            Add-Content -Path $ImportedLogPath -Value $relativePath

            $docTitle = $response.data.title
            $docUrl = $response.data.url
            $docId = $response.data.id

            Write-Ok "Importiert: $docTitle"
            Write-Host "ID:  $docId"
            if ($docUrl) {
                Write-Host "URL: $OutlineUrl$docUrl"
            }
        }
        else {
            $failed++

            $errorText = $response | ConvertTo-Json -Depth 20
            Add-Content -Path $FailedLogPath -Value "FAILED: $relativePath"
            Add-Content -Path $FailedLogPath -Value $errorText
            Add-Content -Path $FailedLogPath -Value ""

            Write-Fail "Outline hat keine OK-Antwort geliefert: $relativePath"
            Write-Host $errorText
        }
    }
    catch {
        $failed++

        Add-Content -Path $FailedLogPath -Value "FAILED: $relativePath"
        Add-Content -Path $FailedLogPath -Value $_.Exception.Message

        if ($_.ErrorDetails.Message) {
            Add-Content -Path $FailedLogPath -Value $_.ErrorDetails.Message
        }

        Add-Content -Path $FailedLogPath -Value ""

        Write-Fail "Fehler bei: $relativePath"
        Write-Host $_.Exception.Message

        if ($_.ErrorDetails.Message) {
            Write-Host $_.ErrorDetails.Message
        }
    }
}

Write-Progress -Activity "Importiere Markdown nach Outline" -Completed

Write-Host ""
Write-Host "==> Import abgeschlossen" -ForegroundColor Cyan
Write-Host "Erfolgreich:  $success"
Write-Host "Fehler:       $failed"
Write-Host "Übersprungen: $skipped"
Write-Host "Log OK:       $ImportedLogPath"
Write-Host "Log Fehler:   $FailedLogPath"

if ($failed -gt 0) {
    exit 2
}

exit 0
