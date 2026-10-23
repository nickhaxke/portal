$ErrorActionPreference = 'Stop'
$portalDir = $PSScriptRoot
$zipPath = Join-Path $portalDir 'portal-update-package.zip'
$temporaryZipPath = Join-Path $portalDir 'portal-update-package.pending.zip'

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$directoryPaths = @('app', 'config', 'database/migrations', 'database/factories', 'database/seeders', 'resources', 'routes')
$filePaths = @(
    'artisan', 'bootstrap/app.php', 'bootstrap/providers.php',
    'composer.json', 'composer.lock', 'package.json', 'package-lock.json',
    'vite.config.js', '.env.example', 'MAAGIZO_YA_UPDATE_CPANEL.txt', 'database/update_schema.sql'
)
foreach ($directoryPath in $directoryPaths) {
    $filePaths += Get-ChildItem -LiteralPath (Join-Path $portalDir $directoryPath) -File -Recurse -Force |
        ForEach-Object { $_.FullName.Substring($portalDir.Length + 1).Replace('\', '/') }
}

# Skip the storage symlink and local Vite marker before recursing.
foreach ($publicItem in Get-ChildItem -LiteralPath (Join-Path $portalDir 'public') -Force) {
    if ($publicItem.Name -in @('storage', 'hot') -or ($publicItem.Attributes -band [IO.FileAttributes]::ReparsePoint)) {
        continue
    }
    if ($publicItem.PSIsContainer) {
        $filePaths += Get-ChildItem -LiteralPath $publicItem.FullName -File -Recurse -Force |
            ForEach-Object { $_.FullName.Substring($portalDir.Length + 1).Replace('\', '/') }
    } else {
        $filePaths += 'public/' + $publicItem.Name
    }
}
$filePaths = @($filePaths | Sort-Object -Unique)
foreach ($filePath in $filePaths) {
    if (-not (Test-Path -LiteralPath (Join-Path $portalDir $filePath) -PathType Leaf)) {
        throw "Missing package file: $filePath"
    }
}
if ($filePaths -notcontains 'public/build/manifest.json') {
    throw 'Build assets first using npm.cmd run build.'
}

try {
    $archive = [IO.Compression.ZipFile]::Open($temporaryZipPath, [IO.Compression.ZipArchiveMode]::Create)
    try {
        foreach ($filePath in $filePaths) {
            [IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
                $archive, (Join-Path $portalDir $filePath), $filePath,
                [IO.Compression.CompressionLevel]::Optimal
            ) | Out-Null
        }
    } finally {
        $archive.Dispose()
    }
    if (Test-Path -LiteralPath $zipPath) {
        [IO.File]::Copy($temporaryZipPath, $zipPath, $true)
    } else {
        Move-Item -LiteralPath $temporaryZipPath -Destination $zipPath
    }
} finally {
    if (Test-Path -LiteralPath $temporaryZipPath) {
        Remove-Item -LiteralPath $temporaryZipPath -Force
    }
}
$zipFile = Get-Item -LiteralPath $zipPath
Write-Host "Package created: $zipPath"
Write-Host "Files: $($filePaths.Count); Size: $([math]::Round($zipFile.Length / 1MB, 2)) MB"
