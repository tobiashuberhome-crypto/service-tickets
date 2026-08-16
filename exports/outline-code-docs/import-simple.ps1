param(
    [string]$DocsPath = "D:\github\exports\outline-code-docs\docs",
    [string]$OutlineUrl = "https://wiki.thss.online",
    [string]$CollectionId = "bf538c26-3c8b-4cf0-9bd0-d9e09f4ac3bb",
    [string]$ParentDocumentId = "45f7216d-67bb-4363-a50d-63cb7d7015b8",
    [string]$ApiToken,
    [switch]$SkipAlreadyImported
)

$ErrorActionPreference = "Continue"

function ConvertTo-JsonString {
    param([string]$Value)

    if ($null -eq $Value) {
        return ""
    }

    $Value = $Value -replace "\\", "\\"
    $Value = $Value -replace '"', '\"'
    $Value = $Value -replace "`r", "\r"
    $Value = $Value -replace "`n", "\n"
    $Value = $Value -replace "`t", "\t"

    return $Value
}

$ImportedLogPath = "D:\github\exports\outline-code-docs\imported-simple.txt"
$FailedLogPath = "D:\github\exports\outline-code-docs\failed-simple.txt"

if ([string]::IsNullOrWhiteSpace($ApiToken)) {
    Write-Host "ApiToken fehlt" -ForegroundColor Red
    exit 1
}

$headers = @{
    "Authorization" = "Bearer $ApiToken"
    "Content-Type"  = "application/json"
}

$files = Get-ChildItem -Path $DocsPath -Filter "*.md" -Recurse | Sort-Object FullName

Write-Host ""
Write-Host "Gefundene Dateien: $($files.Count)" -ForegroundColor Cyan
Write-Host "DocsPath: $DocsPath"
Write-Host "Parent:   $ParentDocumentId"
Write-Host ""

$alreadyImported = @{}

if ($SkipAlreadyImported -and (Test-Path $ImportedLogPath)) {
    Get-Content $ImportedLogPath | ForEach-Object {
        if (-not [string]::IsNullOrWhiteSpace($_)) {
            $alreadyImported[$_] = $true
        }
    }
}

$current = 0
$success = 0
$failed = 0
$skipped = 0

foreach ($file in $files) {
    $current++

    $relative = $file.FullName.Substring($DocsPath.Length).TrimStart("\", "/")

    if ($SkipAlreadyImported -and $alreadyImported.ContainsKey($relative)) {
        $skipped++
        Write-Host "[$current/$($files.Count)] SKIP $relative" -ForegroundColor Yellow
        continue
    }

    if ($file.Length -eq 0) {
        $skipped++
        Write-Host "[$current/$($files.Count)] SKIP leer: $relative" -ForegroundColor Yellow
        continue
    }

    $titleBase = [System.IO.Path]::ChangeExtension($relative, $null)
    $titleBase = $titleBase -replace "__", "\"
    $titleBase = $titleBase.TrimEnd(".")
    $title = "Datei: $titleBase"

    Write-Host ""
    Write-Host "[$current/$($files.Count)] Importiere: $relative"
    Write-Host "Titel: $title"

    try {
        $text = Get-Content -Path $file.FullName -Raw -Encoding UTF8

        $titleJson = ConvertTo-JsonString $title
$textJson = ConvertTo-JsonString $text
$collectionIdJson = ConvertTo-JsonString $CollectionId
$parentDocumentIdJson = ConvertTo-JsonString $ParentDocumentId

$body = @"
{
  "title": "$titleJson",
  "text": "$textJson",
  "collectionId": "$collectionIdJson",
  "parentDocumentId": "$parentDocumentIdJson",
  "publish": true
}
"@

        $body = $serializer.Serialize($bodyObject)

        $response = Invoke-RestMethod `
            -Uri "$OutlineUrl/api/documents.create" `
            -Method Post `
            -Headers $headers `
            -Body $body `
            -TimeoutSec 60

        if ($response.ok -eq $true) {
            $success++
            Add-Content -Path $ImportedLogPath -Value $relative
            Write-Host "OK: $($response.data.url)" -ForegroundColor Green
        }
        else {
            $failed++
            Add-Content -Path $FailedLogPath -Value "FAILED: $relative"
            Add-Content -Path $FailedLogPath -Value ($response | ConvertTo-Json -Depth 10)
            Add-Content -Path $FailedLogPath -Value ""
            Write-Host "FEHLER: keine ok=true Antwort" -ForegroundColor Red
        }
    }
    catch {
    $statusCode = $null

    if ($_.Exception.Response -ne $null) {
        try {
            $statusCode = [int]$_.Exception.Response.StatusCode
        } catch {}
    }

    if ($statusCode -eq 429) {
        Write-Host "RATE LIMIT: warte 60 Sekunden und versuche Datei erneut..." -ForegroundColor Yellow
        Start-Sleep -Seconds 60

        try {
            $response = Invoke-RestMethod `
                -Uri "$OutlineUrl/api/documents.create" `
                -Method Post `
                -Headers $headers `
                -Body $body `
                -TimeoutSec 120

            if ($response.ok -eq $true) {
                $success++
                Add-Content -Path $ImportedLogPath -Value $relative
                Write-Host "OK nach Retry: $($response.data.url)" -ForegroundColor Green
            }
            else {
                $failed++
                Add-Content -Path $FailedLogPath -Value "FAILED: $relative"
                Add-Content -Path $FailedLogPath -Value ($response | Out-String)
                Add-Content -Path $FailedLogPath -Value ""
                Write-Host "FEHLER nach Retry: keine ok=true Antwort" -ForegroundColor Red
            }
        }
        catch {
            $failed++
            Add-Content -Path $FailedLogPath -Value "FAILED: $relative"
            Add-Content -Path $FailedLogPath -Value $_.Exception.Message
            if ($_.ErrorDetails.Message) {
                Add-Content -Path $FailedLogPath -Value $_.ErrorDetails.Message
            }
            Add-Content -Path $FailedLogPath -Value ""
            Write-Host "FEHLER auch nach Retry:" -ForegroundColor Red
            Write-Host $_.Exception.Message
            if ($_.ErrorDetails.Message) {
                Write-Host $_.ErrorDetails.Message
            }
        }
    }
    else {
        $failed++
        Add-Content -Path $FailedLogPath -Value "FAILED: $relative"
        Add-Content -Path $FailedLogPath -Value $_.Exception.Message

        if ($_.ErrorDetails.Message) {
            Add-Content -Path $FailedLogPath -Value $_.ErrorDetails.Message
        }

        Add-Content -Path $FailedLogPath -Value ""

        Write-Host "FEHLER:" -ForegroundColor Red
        Write-Host $_.Exception.Message

        if ($_.ErrorDetails.Message) {
            Write-Host $_.ErrorDetails.Message
        }
    }
}

    Start-Sleep -Seconds 3
}

Write-Host ""
Write-Host "Fertig" -ForegroundColor Cyan
Write-Host "Erfolgreich:  $success"
Write-Host "Fehler:       $failed"
Write-Host "Übersprungen: $skipped"
Write-Host "OK-Log:       $ImportedLogPath"
Write-Host "Fehler-Log:   $FailedLogPath"

if ($failed -gt 0) {
    exit 2
}

exit 0
