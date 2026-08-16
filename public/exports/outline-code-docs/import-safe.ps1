param(
    [string]$DocsPath = "D:\github\exports\outline-code-docs\docs",
    [string]$OutlineUrl = "https://wiki.thss.online",
    [string]$CollectionId = "bf538c26-3c8b-4cf0-9bd0-d9e09f4ac3bb",
    [string]$ParentDocumentId = "",
    [string]$ApiToken,
    [switch]$SkipAlreadyImported,
    [int]$DelaySeconds = 5
)

$ErrorActionPreference = "Stop"

$ImportedLogPath = "D:\github\exports\outline-code-docs\imported-safe.txt"
$FailedLogPath = "D:\github\exports\outline-code-docs\failed-safe.txt"

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

function Build-OutlineJson {
    param(
        [string]$Title,
        [string]$Text,
        [string]$CollectionId,
        [string]$ParentDocumentId
    )

    $titleJson = Escape-JsonString $Title
    $textJson = Escape-JsonString $Text
    $collectionJson = Escape-JsonString $CollectionId

    if ([string]::IsNullOrWhiteSpace($ParentDocumentId)) {
        return @"
{
  "title": "$titleJson",
  "text": "$textJson",
  "collectionId": "$collectionJson",
  "publish": true
}
"@
    }

    $parentJson = Escape-JsonString $ParentDocumentId

    return @"
{
  "title": "$titleJson",
  "text": "$textJson",
  "collectionId": "$collectionJson",
  "parentDocumentId": "$parentJson",
  "publish": true
}
"@
}

if ([string]::IsNullOrWhiteSpace($ApiToken)) {
    Write-Host "ApiToken fehlt" -ForegroundColor Red
    exit 1
}

if (-not (Test-Path $DocsPath)) {
    Write-Host "DocsPath nicht gefunden: $DocsPath" -ForegroundColor Red
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
Write-Host "Delay:    $DelaySeconds Sekunden"
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

        $requestBody = Build-OutlineJson `
            -Title $title `
            -Text $text `
            -CollectionId $CollectionId `
            -ParentDocumentId $ParentDocumentId

        Write-Host "Body-Länge: $($requestBody.Length)"

        $response = Invoke-RestMethod `
            -Uri "$OutlineUrl/api/documents.create" `
            -Method Post `
            -Headers $headers `
            -Body $requestBody `
            -TimeoutSec 120

        if ($response.ok -eq $true) {
            $success++
            Add-Content -Path $ImportedLogPath -Value $relative
            Write-Host "OK: $($response.data.title)" -ForegroundColor Green
            Write-Host "URL: $OutlineUrl$($response.data.url)"
        }
        else {
            $failed++
            Add-Content -Path $FailedLogPath -Value "FAILED: $relative"
            Add-Content -Path $FailedLogPath -Value ($response | Out-String)
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
            Write-Host "RATE LIMIT: warte 90 Sekunden und versuche dieselbe Datei erneut..." -ForegroundColor Yellow
            Start-Sleep -Seconds 90

            try {
                $response = Invoke-RestMethod `
                    -Uri "$OutlineUrl/api/documents.create" `
                    -Method Post `
                    -Headers $headers `
                    -Body $requestBody `
                    -TimeoutSec 120

                if ($response.ok -eq $true) {
                    $success++
                    Add-Content -Path $ImportedLogPath -Value $relative
                    Write-Host "OK nach Retry: $($response.data.title)" -ForegroundColor Green
                    Write-Host "URL: $OutlineUrl$($response.data.url)"
                }
                else {
                    $failed++
                    Add-Content -Path $FailedLogPath -Value "FAILED AFTER RETRY: $relative"
                    Add-Content -Path $FailedLogPath -Value ($response | Out-String)
                    Add-Content -Path $FailedLogPath -Value ""
                }
            }
            catch {
                $failed++
                Add-Content -Path $FailedLogPath -Value "FAILED AFTER RETRY: $relative"
                Add-Content -Path $FailedLogPath -Value $_.Exception.Message
                if ($_.ErrorDetails.Message) {
                    Add-Content -Path $FailedLogPath -Value $_.ErrorDetails.Message
                }
                Add-Content -Path $FailedLogPath -Value ""
                Write-Host "FEHLER auch nach Retry: $($_.Exception.Message)" -ForegroundColor Red
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

    Start-Sleep -Seconds $DelaySeconds
}

Write-Host ""
Write-Host "Fertig" -ForegroundColor Cyan
Write-Host "Erfolgreich:  $success"
Write-Host "Fehler:       $failed"
Write-Host "Übersprungen: $skipped"
Write-Host "OK-Log:       $ImportedLogPath"
Write-Host "Fehler-Log:   $FailedLogPath"
