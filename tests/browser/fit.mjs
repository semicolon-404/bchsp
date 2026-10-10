/**
 * Does every public page fit a phone, a tablet and a desktop without scrolling sideways?
 *
 * Opens each page in a real browser (headless Edge or Chrome, over the DevTools protocol; nothing
 * to install) at each width, in the dark look and the light one, and compares the width of the
 * page with the width of the window. Also saves a picture of each to storage/app/fit, to look at.
 *
 * The site must be running:   php artisan serve
 * Then:                       npm run check:fit            (or: node tests/browser/fit.mjs http://127.0.0.1:8000)
 *
 * To check another page, add its address to PAGES.
 */
import { spawn } from 'node:child_process';
import { existsSync, mkdirSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const PAGES = ['/', '/announcements', '/blog', '/departments', '/contact', '/about', '/schedule', '/downloads', '/ita', '/menu', '/staff', '/p/history', '/p/structure', '/p/privacy', '/search?q=%E0%B9%82%E0%B8%A3%E0%B8%87%E0%B8%9E%E0%B8%A2%E0%B8%B2%E0%B8%9A%E0%B8%B2%E0%B8%A5'];

const SCREENS = [
    ['phone', 360, 740],
    ['large-phone', 414, 896],
    ['tablet', 768, 1024],
    ['tablet-wide', 1024, 768],
    ['desktop', 1366, 768],
];

const BROWSERS = [
    'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
    'C:/Program Files/Microsoft/Edge/Application/msedge.exe',
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
    '/usr/bin/google-chrome',
    '/usr/bin/chromium',
    '/usr/bin/chromium-browser',
    '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
];

const base = (process.argv[2] ?? 'http://127.0.0.1:8000').replace(/\/$/, '');
const browser = process.env.BROWSER_PATH ?? BROWSERS.find(existsSync);
const out = join(process.cwd(), 'storage', 'app', 'fit');
const port = 9333;
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

if (!browser) {
    console.error('No Edge or Chrome found. Set BROWSER_PATH to one.');
    process.exit(2);
}

mkdirSync(out, { recursive: true });

const running = spawn(browser, ['--headless', '--disable-gpu', `--remote-debugging-port=${port}`, `--user-data-dir=${join(tmpdir(), 'bchsp-fit-profile')}`, 'about:blank'], { stdio: 'ignore' });

let target;

for (let tries = 0; tries < 40 && !target; tries++) {
    await sleep(250);

    try {
        target = (await (await fetch(`http://127.0.0.1:${port}/json`)).json()).find((one) => one.type === 'page');
    } catch {
        // Not up yet.
    }
}

const socket = new WebSocket(target.webSocketDebuggerUrl);
await new Promise((resolve) => (socket.onopen = resolve));

let id = 0;
const waiting = new Map();

socket.onmessage = (message) => {
    const data = JSON.parse(message.data);

    if (data.id && waiting.has(data.id)) {
        waiting.get(data.id)(data.result);
    }
};

const send = (method, params = {}) => new Promise((resolve) => {
    waiting.set(++id, resolve);
    socket.send(JSON.stringify({ id, method, params }));
});

const evaluate = async (expression) => (await send('Runtime.evaluate', { expression, returnByValue: true })).result.value;

await send('Page.enable');

let failures = 0;

for (const page of PAGES) {
    for (const [name, width, height] of SCREENS) {
        await send('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: width < 1024 });

        for (const look of ['dark', 'light']) {
            await send('Page.navigate', { url: base + page });
            await sleep(900);
            await evaluate(`localStorage.setItem('theme', '${look}')`);
            await send('Page.reload');
            await sleep(900);

            const measured = JSON.parse(await evaluate('JSON.stringify({ page: document.documentElement.scrollWidth, window: window.innerWidth, title: document.title })'));
            const fits = measured.title !== '' && measured.page <= measured.window;

            failures += fits ? 0 : 1;
            console.log(`${fits ? 'ok  ' : 'FAIL'} ${page.padEnd(8)} ${name.padEnd(12)} ${look.padEnd(5)} page ${measured.page}px in a window of ${measured.window}px`);

            const picture = await send('Page.captureScreenshot', { format: 'png' });
            writeFileSync(join(out, `${page === '/' ? 'home' : page.replace(/\W+/g, '-').replace(/^-/, '')}-${name}-${look}.png`), Buffer.from(picture.data, 'base64'));
        }
    }
}

await evaluate("localStorage.removeItem('theme')");
socket.close();
running.kill();

console.log(failures ? `\n${failures} did not fit.` : `\nEverything fits. Pictures are in ${out}`);
process.exit(failures ? 1 : 0);
