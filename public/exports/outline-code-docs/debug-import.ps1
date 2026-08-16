param(
    [string]$DocsPath = "D:\github\exports\outline-code-docs\docs",
    [string]$OutlineUrl = "https://wiki.thss.online",
    [string]$CollectionId = "bf538c26-3c8b-4cf0-9bd0-d9e09f4ac3bb",
    [string]$ParentDocumentId = "45f7216d-67bb-4363-a50d-63cb7d7015b8",
    [string]$ApiToken
)

$ErrorActionPreference = "Continue"

Add-Type -AssemblyName System.Web.Extensions
$serializer = New-Object System.Web.Script.Serialization.JavaScriptSerializer
$serializer.MaxJsonLength = 67108864

$LogPath = "D:\github\exports\outline-code-docs\debug-import.log"

"START $(Get-Date)" | Set-Content $LogPath

if ([string]::IsNullOrWhiteSpace($ApiToken)) {
    "FEHLER: ApiToken fehlt $(Get-Date)" | Add-Content $LogPath
    Write-Host "ApiToken fehlt" -ForegroundColor Red
    exit 1
}

"Token vorhanden $(Get-Date)" | Add-Content $LogPath

$headers = @{
    "Authorization" = "Bearer $ApiToken"
    "Content-Type"  = "application/json"
}

"Headers gebaut $(Get-Date)" | Add-Content $LogPath

$files = Get-ChildItem -Path $DocsPath -Filter "*.md" -Recurse | Sort-Object FullName | Select-Object -First 3

"Dateien gefunden: $($files.Count) $(Get-Date)" | Add-Content $LogPath
Write-Host "Dateien gefunden: $($files.Count)"

$current = 0

foreach ($file in $files) {
    $current++

    "BEGIN Datei $current $($file.FullName) $(Get-Date)" | Add-Content $LogPath
    Write-Host "BEGIN Datei $current $($file.FullName)"

    try {
        $text = Get-Content -Path $file.FullName -Raw -Encoding UTF8
        "Text gelesen Laenge=$($text.Length) $(Get-Date)" | Add-Content $LogPath


$bodyObject = New-Object 'System.Collections.Generic.Dictionary[string,object]'
$bodyObject.Add("title", "DEBUG IMPORT $current $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')")
$bodyObject.Add("text", $text)
$bodyObject.Add("collectionId", $CollectionId)
$bodyObject.Add("parentDocumentId", $ParentDocumentId)
$bodyObject.Add("publish", $true)

$body = $serializer.Serialize($bodyObject)

        "Vor Request $(Get-Date)" | Add-Content $LogPath

        $response = Invoke-RestMethod `
            -Uri "$OutlineUrl/api/documents.create" `
            -Method Post `
            -Headers $headers `
            -Body $body `
            -TimeoutSec 30

        "Nach Request ok=$($response.ok) url=$($response.data.url) $(Get-Date)" | Add-Content $LogPath
        Write-Host "OK $($response.data.url)" -ForegroundColor Green
    }
    catch {
        "CATCH $($_.Exception.Message) $(Get-Date)" | Add-Content $LogPath
        if ($_.ErrorDetails.Message) {
            "DETAILS $($_.ErrorDetails.Message)" | Add-Content $LogPath
        }

        Write-Host "FEHLER $($_.Exception.Message)" -ForegroundColor Red
    }
}

"ENDE $(Get-Date)" | Add-Content $LogPath
Write-Host "Debug fertig. Log: $LogPath"
