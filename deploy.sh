#!/usr/bin/env bash
# ./deploy.sh: deploy.ps1 for a Mac (or Linux): puts what is committed on main onto bchsp.com, a host reached only by FTP.
#
# Builds a copy without the development packages in ~/Library/Caches/bchsp-deploy (made once, then only brought up to date),
# then lftp sends up only the files that changed: the code to /bchsp.com/bchsp, the public files to /bchsp.com/public_html.
# What lives only on the server is left alone: .env, storage (uploads, logs, sessions) and public_html/index.php.
# Last, a page made for this run applies new migrations and clears what the site kept from before.
#
# What is not pushed to GitHub is not sent. The FTP password is asked each time and kept nowhere.
# The page's key and the site's address are in .deploy.local.json, made on the first run and kept out of git.
#
# Needs lftp: brew install lftp
# ./deploy.sh --by-size   the first time from this computer: files are compared by size, not by date.
set -euo pipefail
cd "$(dirname "$0")"

command -v lftp >/dev/null || { echo 'lftp is not installed: brew install lftp' >&2; exit 1; }
by_size=''
[[ "${1:-}" == '--by-size' ]] && by_size='--ignore-time'

settings=.deploy.local.json
if [[ ! -f $settings ]]; then
    printf '{\n    "site": "https://bchsp.com",\n    "host": "dg0072ns1.dragonhispeed.com",\n    "user": "bchspcxp",\n    "key": "%s"\n}\n' "$(openssl rand -hex 16)" > "$settings"
fi
setting() { php -r 'echo json_decode(file_get_contents($argv[1]))->{$argv[2]};' "$settings" "$1"; }
site=$(setting site); host=$(setting host); user=$(setting user); key=$(setting key)

[[ -n $(git status --porcelain) ]] && echo 'Warning: there are changes not committed: they are not sent.' >&2

# The copy that goes up: what is on GitHub's main, its production packages, its built pages and scripts.
stage="$HOME/Library/Caches/bchsp-deploy"
if [[ ! -d $stage ]]; then
    git clone --quiet "$(git remote get-url origin)" "$stage"
else
    git -C "$stage" pull --quiet --ff-only
fi
echo "Sending $(git -C "$stage" log -1 --format='%h %s')"

(
    cd "$stage"
    composer install --no-dev --optimize-autoloader --no-interaction --quiet
    npm ci --silent
    npm run build --silent >/dev/null
)

# The page that finishes the run on the server, with this computer's key in it; it is not in git.
refresh=$(mktemp -d)/refresh-bchsp.php
cat > "$refresh" <<PHP
<?php
// Put here by deploy.sh: applies new migrations and clears what the site kept from before.
if (! hash_equals('$key', (string) (\$_GET['key'] ?? ''))) { http_response_code(404); exit; }
header('Content-Type: text/plain; charset=utf-8');
set_time_limit(300);
require __DIR__.'/../bchsp/vendor/autoload.php';
\$app = require __DIR__.'/../bchsp/bootstrap/app.php';
\$app->usePublicPath(__DIR__);
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();
foreach ([['migrate', ['--force' => true]], ['optimize:clear', []], ['route:cache', []], ['view:cache', []], ['site:shrink-pictures', ['--at-most' => 20]]] as [\$command, \$options]) {
    \$code = \$kernel->call(\$command, \$options);
    echo "> \$command\n", \$kernel->output(), "\n";
    if (\$code !== 0) { http_response_code(500); exit; }
}
echo "OK\n";
PHP

read -r -s -p "FTP password for $user: " password; echo

lftp -u "$user,$password" "$host" <<LFTP
set ftp:ssl-force true
set ftp:ssl-protect-data true
set ssl:verify-certificate false
set cmd:fail-exit true
mirror --reverse --delete --only-newer $by_size --parallel=4 --exclude-glob .env --exclude-glob .git/ --exclude-glob node_modules/ --exclude-glob tests/ --exclude ^storage/ --exclude ^public/ "$stage" /bchsp.com/bchsp
mirror --reverse --only-newer $by_size --parallel=4 --exclude-glob index.php --exclude ^storage --exclude ^build/ --exclude-glob setup-bchsp.php --exclude-glob refresh-bchsp.php "$stage/public" /bchsp.com/public_html
mirror --reverse --delete --only-newer $by_size --parallel=4 "$stage/public/build" /bchsp.com/public_html/build
put "$refresh" -o /bchsp.com/public_html/refresh-bchsp.php
bye
LFTP
rm -rf "$(dirname "$refresh")"

echo; echo 'Finishing on the server...'
curl -fsS "$site/refresh-bchsp.php?key=$key"
echo "Done: $site"
