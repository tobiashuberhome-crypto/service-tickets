param(
    [string]$OutlineUrl = "https://wiki.thss.online",
    [string]$CollectionId = "bf538c26-3c8b-4cf0-9bd0-d9e09f4ac3bb",
    [string]$TargetParentDocumentId,
    [string]$ApiToken,
    [string]$TitlePrefix = "Datei:",
    [switch]$DryRun
)

$ErrorActionPreference = "Stop"

if ([string]::IsNullOrWhiteSpace($ApiToken)) {
    Write-Host "ApiToken fehlt" -ForegroundColor Red
    exit 1
}

if ([string]::IsNullOrWhiteSpace($TargetParentDocumentId)) {
    Write-Host "TargetParentDocumentId fehlt" -ForegroundColor Red
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

function Build-Json {
    param([hashtable]$Values)

    $parts = @()

    foreach ($key in $Values.Keys) {
        $k = Escape-JsonString $key
        $v = Escape-JsonString ([string]$Values[$key])
        $parts += "`"$k`": `"$v`""
    }

    return "{ " + ($parts -join ", ") + " }"
}

function Invoke-Outline {
    param(
        [string]$Method,
        [hashtable]$Payload
    )

    $body = Build-Json $Payload

    return Invoke-RestMethod `
        -Uri "$OutlineUrl/api/$Method" `
        -Method Post `
        -Headers $headers `
        -Body $body `
        -TimeoutSec 60
}

Write-Host "Lade Dokumente aus Collection..." -ForegroundColor Cyan

$documents = @()
$offset = 0
$limit = 100

while ($true) {
    $payload = @{
        collectionId = $CollectionId
        limit = "$limit"
        offset = "$offset"
    }

    $result = Invoke-Outline -Method "documents.list" -Payload $payload

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
    $_.title -like "$TitlePrefix*" -and $_.parentDocumentId -ne $TargetParentDocumentId
} | Select-Object -First 1

Write-Host "Dokumente gesamt: $($documents.Count)"
Write-Host "Treffer zum Verschieben: $($matches.Count)" -ForegroundColor Cyan

$matches |
    Select-Object title, id, parentDocumentId, url |
    Format-Table -AutoSize

if ($DryRun) {
    Write-Host ""
    Write-Host "DryRun aktiv: Es wurde nichts verschoben." -ForegroundColor Yellow
    exit 0
}

foreach ($doc in $matches) {
    Write-Host ""
    Write-Host "Verschiebe: $($doc.title)"

    try {
        $payload = @{
            id = $doc.id
            collectionId = $CollectionId
            parentDocumentId = $TargetParentDocumentId
        }

        $response = Invoke-Outline -Method "documents.update" -Payload $payload

        if ($response.ok -eq $true) {
            Write-Host "OK: $($doc.title)" -ForegroundColor Green
        }
        else {
            Write-Host "FEHLER: keine ok=true Antwort" -ForegroundColor Red
            $response | Out-String | Write-Host
        }
    }
    catch {
        Write-Host "FEHLER bei $($doc.title)" -ForegroundColor Red
        Write-Host $_.Exception.Message

        if ($_.ErrorDetails.Message) {
            Write-Host $_.ErrorDetails.Message
        }
    }

    Start-Sleep -Seconds 2
}

Write-Host ""
Write-Host "Fertig." -ForegroundColor Cyan
