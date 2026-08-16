param(
    [string]$DocsPath = "D:\github\exports\outline-code-docs\docs",
    [string]$OutlineUrl = "https://wiki.thss.online",
    [string]$CollectionId = "bf538c26-3c8b-4cf0-9bd0-d9e09f4ac3bb",
    [string]$ApiToken,
    [switch]$Publish
)

if (-not $ApiToken) {
    Write-Error "Bitte ApiToken angeben: -ApiToken 'ol_api_6JiZGcWNn4S8RqsQvkbRjCPhfGf0DUBfeV3kTM'"
    exit 1
}

if (-not (Test-Path $DocsPath)) {
    Write-Error "DocsPath nicht gefunden: $DocsPath"
    exit 1
}

$headers = @{
    "Authorization" = "Bearer $ApiToken"
    "Content-Type"  = "application/json"
}

$files = Get-ChildItem -Path $DocsPath -Filter "*.md" -Recurse | Sort-Object FullName

if ($files.Count -eq 0) {
    Write-Host "Keine .md-Dateien gefunden in: $DocsPath"
    exit 0
}

Write-Host "Gefundene Markdown-Dateien: $($files.Count)"
Write-Host "Import nach Outline Collection: $CollectionId"
Write-Host "Publish: $($Publish.IsPresent)"
Write-Host ""

$success = 0
$failed = 0

foreach ($file in $files) {
    try {
        $relativePath = $file.FullName.Substring($DocsPath.Length).TrimStart("\", "/")
        $title = [System.IO.Path]::GetFileNameWithoutExtension($file.Name)

        # Optional: Ordnerpfad in Titel aufnehmen, damit gleichnamige Dateien unterscheidbar bleiben
        $folder = Split-Path $relativePath -Parent
        if ($folder -and $folder -ne ".") {
            $title = "$folder / $title"
        }

        $text = Get-Content -Path $file.FullName -Raw -Encoding UTF8

        if ([string]::IsNullOrWhiteSpace($text)) {
            Write-Warning "Überspringe leere Datei: $relativePath"
            continue
        }

        $body = @{
            title        = $title
            text         = $text
            collectionId = $CollectionId
            publish      = $Publish.IsPresent
        } | ConvertTo-Json -Depth 10

        Write-Host "Importiere: $relativePath"

        $response = Invoke-RestMethod `
            -Uri "$OutlineUrl/api/documents.create" `
            -Method Post `
            -Headers $headers `
            -Body $body

        if ($response.ok -eq $true) {
            $success++
            $docTitle = $response.data.title
            $docUrl = $response.data.url
            Write-Host "  OK: $docTitle"
            if ($docUrl) {
                Write-Host "  URL: $docUrl"
            }
        } else {
            $failed++
            Write-Warning "  Fehler bei $relativePath"
            Write-Warning ($response | ConvertTo-Json -Depth 10)
        }
    }
    catch {
        $failed++
        Write-Warning "Fehler bei Datei: $($file.FullName)"
        Write-Warning $_.Exception.Message

        if ($_.ErrorDetails.Message) {
            Write-Warning $_.ErrorDetails.Message
        }
    }
}

Write-Host ""
Write-Host "Fertig."
Write-Host "Erfolgreich: $success"
Write-Host "Fehler:      $failed"