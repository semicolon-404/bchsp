# .\deploy.ps1: puts what is committed on main onto bchsp.com, a host reached only by FTP.
#
# Builds a copy without the development packages in %LOCALAPPDATA%\bchsp-deploy (made once, then only brought up to date),
# then WinSCP sends up only the files that changed: the code to /bchsp.com/bchsp, the public files to /bchsp.com/public_html.
# What lives only on the server is left alone: .env, storage (uploads, logs, sessions) and public_html/index.php.
# Last, a page made for this run applies new migrations and clears what the site kept from before.
#
# What is not committed is not sent. The FTP password is asked each time and kept nowhere.
# The page's key and the site's address are in .deploy.local.json, made on the first run and kept out of git.
#
# .\deploy.ps1 -BySize   the first time: the server already has these files, so they are compared by size, not by date.
param([switch] $BySize)
$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot

$winscp = "${env:ProgramFiles(x86)}\WinSCP\WinSCP.com"
if (-not (Test-Path $winscp)) { $winscp = "$env:ProgramFiles\WinSCP\WinSCP.com" }
if (-not (Test-Path $winscp)) { throw 'WinSCP is not installed: winget install WinSCP.WinSCP' }

$settingsFile = Join-Path $PSScriptRoot '.deploy.local.json'
if (-not (Test-Path $settingsFile)) {
    @{ site = 'http://bchsp.com'; host = '103.4.217.180'; user = 'bchspcxp'; key = -join ((1..32) | ForEach-Object { '{0:x}' -f (Get-Random -Maximum 16) }) } |
        ConvertTo-Json | Set-Content $settingsFile -Encoding utf8
}
$settings = Get-Content $settingsFile -Raw | ConvertFrom-Json

if (git status --porcelain) { Write-Warning 'There are changes not committed: they are not sent.' }

# The copy that goes up: what is committed, its production packages, its built pages and scripts.
$stage = Join-Path $env:LOCALAPPDATA 'bchsp-deploy'
if (-not (Test-Path $stage)) {
    git clone --quiet $PSScriptRoot $stage
} else {
    git -C $stage pull --quiet --ff-only
}
if ($LASTEXITCODE) { throw 'Could not bring the copy up to date.' }
Write-Host "Sending $(git -C $stage log -1 --format='%h %s')"

Push-Location $stage
try {
    composer install --no-dev --optimize-autoloader --no-interaction --quiet
    if ($LASTEXITCODE) { throw 'composer install failed.' }
    npm ci --silent
    if ($LASTEXITCODE) { throw 'npm ci failed.' }
    npm run build --silent | Out-Null
    if ($LASTEXITCODE) { throw 'npm run build failed.' }
} finally {
    Pop-Location
}

# The page that finishes the run on the server, with this computer's key in it; it is not in git.
$refresh = Join-Path $env:TEMP 'refresh-bchsp.php'
@'
<?php
// Put here by deploy.ps1: applies new migrations and clears what the site kept from before.
if (! hash_equals('KEY', (string) ($_GET['key'] ?? ''))) { http_response_code(404); exit; }
header('Content-Type: text/plain; charset=utf-8');
set_time_limit(300);
require __DIR__.'/../bchsp/vendor/autoload.php';
$app = require __DIR__.'/../bchsp/bootstrap/app.php';
$app->usePublicPath(__DIR__);
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
foreach ([['migrate', ['--force' => true]], ['optimize:clear', []]] as [$command, $options]) {
    $code = $kernel->call($command, $options);
    echo "> $command\n", $kernel->output(), "\n";
    if ($code !== 0) { http_response_code(500); exit; }
}
echo "OK\n";
'@.Replace('KEY', $settings.key) | Set-Content $refresh -Encoding utf8 -NoNewline

$password = Read-Host "FTP password for $($settings.user)" -AsSecureString
$plain = [Runtime.InteropServices.Marshal]::PtrToStringBSTR([Runtime.InteropServices.Marshal]::SecureStringToBSTR($password))
$session = "ftpes://$([uri]::EscapeDataString($settings.user)):$([uri]::EscapeDataString($plain))@$($settings.host)/"
$criteria = if ($BySize) { '-criteria=size' } else { '' }

$script = Join-Path $env:TEMP 'bchsp-deploy.winscp'
@"
option batch abort
option confirm off
open "$session" -certificate="*"
synchronize remote -delete $criteria -filemask="|.env; .git/; node_modules/; tests/; storage/; public/" "$stage" /bchsp.com/bchsp
synchronize remote $criteria -filemask="|index.php; storage/; build/; setup-bchsp.php; refresh-bchsp.php" "$stage\public" /bchsp.com/public_html
synchronize remote -delete $criteria "$stage\public\build" /bchsp.com/public_html/build
put "$refresh" /bchsp.com/public_html/refresh-bchsp.php
exit
"@ | Set-Content $script -Encoding utf8

try {
    & $winscp /ini=nul /log="$env:TEMP\bchsp-deploy.log" /script="$script"
    if ($LASTEXITCODE) { throw "WinSCP stopped; see $env:TEMP\bchsp-deploy.log" }
} finally {
    Remove-Item $script, $refresh -ErrorAction SilentlyContinue
    $plain = $null
}

Write-Host "`nFinishing on the server..."
$answer = Invoke-WebRequest "$($settings.site)/refresh-bchsp.php?key=$($settings.key)" -UseBasicParsing
Write-Host $answer.Content
Write-Host "Done: $($settings.site)"
