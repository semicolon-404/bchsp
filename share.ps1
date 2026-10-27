# .\share.ps1: the site on this computer, opened to others for a while through a Cloudflare tunnel.
# A new https://….trycloudflare.com address each time; closed with Ctrl+C. Nothing in .env is changed:
# the address, a site that shows no errors, and trust in the tunnel's https are given to this run only.
param([int] $port = 8010)
$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot

$cloudflared = (Get-Command cloudflared -ErrorAction SilentlyContinue).Source
if (-not $cloudflared) { $cloudflared = "${env:ProgramFiles(x86)}\cloudflared\cloudflared.exe" }
if (-not (Test-Path $cloudflared)) { throw 'cloudflared is not installed: winget install Cloudflare.cloudflared' }

$log = Join-Path $env:TEMP 'bchsp-tunnel.log'
Remove-Item $log -ErrorAction SilentlyContinue

# The built pages and scripts, not the development server's.
if (Test-Path public\hot) { Remove-Item public\hot }
php artisan config:clear | Out-Null

$tunnel = Start-Process $cloudflared -ArgumentList 'tunnel', '--no-autoupdate', '--url', "http://127.0.0.1:$port" -RedirectStandardError $log -PassThru -WindowStyle Hidden

try {
    $url = $null
    for ($i = 0; $i -lt 60 -and -not $url; $i++) {
        Start-Sleep -Milliseconds 500
        if (Test-Path $log) {
            $found = Select-String -Path $log -Pattern 'https://[a-z0-9-]+\.trycloudflare\.com' | Select-Object -First 1
            if ($found) { $url = $found.Matches[0].Value }
        }
    }
    if (-not $url) { throw "No address from the tunnel; see $log" }

    Write-Host ''
    Write-Host "  The site:   $url" -ForegroundColor Green
    Write-Host "  The panel:  $url/admin" -ForegroundColor Green
    Write-Host '  Ctrl+C closes it.' -ForegroundColor DarkGray
    Write-Host ''

    $env:APP_URL = $url
    $env:APP_DEBUG = 'false'
    $env:TRUSTED_PROXIES = '*'
    # --no-reload: otherwise the server drops what is set here and reads .env alone.
    php artisan serve --host=127.0.0.1 --port=$port --no-reload
}
finally {
    Stop-Process -Id $tunnel.Id -ErrorAction SilentlyContinue
    Remove-Item Env:APP_URL, Env:APP_DEBUG, Env:TRUSTED_PROXIES -ErrorAction SilentlyContinue
}
