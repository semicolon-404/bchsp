/**
 * The popups set up on their own (components/site/site-popups): opened one after another once the page has loaded,
 * each as often as it was set to: once (until it is changed in the panel), once a day, or every visit. A middle one
 * is modal; a corner card opens without covering the page. What was seen is kept in the visitor's browser only.
 * The announcements' own popup (resources/js/popup.js) waits until these are done.
 */
const KEY = 'site-popups-seen';

function seen() {
    try {
        return JSON.parse(localStorage.getItem(KEY) || '{}');
    } catch {
        return {};
    }
}

function remember(key) {
    try {
        const all = seen();
        all[key] = new Date().toISOString().slice(0, 10);
        localStorage.setItem(KEY, JSON.stringify(Object.fromEntries(Object.entries(all).slice(-30))));
    } catch {
        // Not remembered: it opens again next time.
    }
}

function due(dialog) {
    const when = seen()[dialog.dataset.sitePopup];
    if (dialog.dataset.frequency === 'always' || !when) return true;
    return dialog.dataset.frequency === 'daily' && when !== new Date().toISOString().slice(0, 10);
}

export function startSitePopups() {
    const queue = [...document.querySelectorAll('dialog[data-site-popup]')].filter(due);
    if (!queue.length || typeof HTMLDialogElement === 'undefined') {
        document.dispatchEvent(new Event('site-popups-done'));
        return;
    }

    window.sitePopupsBusy = true;

    const next = () => {
        const dialog = queue.shift();
        if (!dialog) {
            window.sitePopupsBusy = false;
            document.dispatchEvent(new Event('site-popups-done'));
            return;
        }

        dialog.addEventListener('close', () => { remember(dialog.dataset.sitePopup); setTimeout(next, 300); }, { once: true });
        dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });

        if (dialog.classList.contains('is-corner')) {
            dialog.show();
        } else {
            dialog.showModal();
            dialog.focus();
        }
    };

    setTimeout(next, 600);
}
