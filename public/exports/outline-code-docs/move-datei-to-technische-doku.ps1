param(
    [string]$OutlineUrl = "https://wiki.thss.online",
    [string]$CollectionId = "bf538c26-3c8b-4cf0-9bd0-d9e09f4ac3bb",
    [string]$TargetParentDocumentId = "c22f6ded-9e31-4e99-962c-25d9fd9fe983",
    [string]$ApiToken,
    [switch]$DryRun
)

$ErrorActionPreference = "Stop"

if ([string]::IsNullOrWhiteSpace($ApiToken)) {
    Write-Host "ApiToken fehlt" -ForegroundColor Red
    exit 1
}

$headers = @{
    "Authorization" = "Bearer $ApiToken"
    "Content-Type"  = "application/json"
}

function Escape-JsonString {
    param([string]$Value)

    if ($null -eq $Value) {
        return ""
    }

    $Value = $Value.Replace("\", "\\")
    $Value = $Value.Replace('"', '\"')
    $Value = $Value.Replace("`r", "\r")
    $Value = $Value.Replace("`n", "\n")
    $Value = $Value.Replace("`t", "\t")

    return $Value
}

function Invoke-OutlineJson {
    param(
        [string]$Method,
        [string]$JsonBody
    )

    return Invoke-RestMethod `
        -Uri "$OutlineUrl/api/$Method" `
        -Method Post `
        -Headers $headers `
        -Body $JsonBody `
        -TimeoutSec 60
}

Write-Host ""
Write-Host "Lade Dokumente aus Collection..." -ForegroundColor Cyan

$documents = @()
$offset = 0
$limit = 100

while ($true) {
    $body = @"
{
  "collectionId": "$CollectionId",
  "limit": $limit,
  "offset": $offset
}
"@

    $result = Invoke-OutlineJson -Method "documents.list" -JsonBody $body

    if (-not $result.data -or $result.data.Count -eq 0) {
        break
    }

    $documents += $result.data

    if ($result.data.Count -lt $limit) {
        break
    }

    $offset += $limit
}

$matches = $documents | Where-Object {
    $_.title -like "Datei:*" -and $_.parentDocumentId -ne $TargetParentDocumentId
}

Write-Host "Dokumente gesamt: $($documents.Count)"
Write-Host "Treffer mit Titel 'Datei:': $($matches.Count)" -ForegroundColor Cyan
Write-Host "Ziel-Parent: $TargetParentDocumentId"
Write-Host ""

$matches |
    Select-Object title, id, parentDocumentId, url |
    Format-Table -AutoSize

if ($DryRun) {
    Write-Host ""
    Write-Host "DryRun aktiv: Es wurde nichts verschoben." -ForegroundColor Yellow
    exit 0
}

$success = 0
$failed = 0
$index = 0

foreach ($doc in $matches) {
    Write-Host ""
    Write-Host "Verschiebe: $($doc.title)"

    $docId = Escape-JsonString $doc.id
    $targetId = Escape-JsonString $TargetParentDocumentId

    $body = @"
{
  "id": "$docId",
  "parentDocumentId": "$targetId",
  "index": $index
}
"@

    try {
        $response = Invoke-OutlineJson -Method "documents.move" -JsonBody $body

        if ($response.ok -eq $true) {
            $success++
            Write-Host "OK: $($doc.title)" -ForegroundColor Green
        }
        else {
            $failed++
            Write-Host "FEHLER: keine ok=true Antwort" -ForegroundColor Red
            $response | Out-String | Write-Host
        }
    }
    catch {
        $failed++
        Write-Host "FEHLER bei $($doc.title)" -ForegroundColor Red
        Write-Host $_.Exception.Message

        if ($_.ErrorDetails.Message) {
            Write-Host $_.ErrorDetails.Message
        }
    }

    $index++
    Start-Sleep -Seconds 2
}

Write-Host ""
Write-Host "Fertig." -ForegroundColor Cyan
Write-Host "Erfolgreich: $success"
Write-Host "Fehler:      $failed"