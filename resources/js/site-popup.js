/**
 * The popups set up on their own (components/site/site-popups). Before the slider starts, each popup this visitor
 * is not due to see again is taken out: once (until it is changed in the panel), once a day, or every visit. What
 * is left opens once the page has loaded: the middle window first (several in it, swiped), then each corner card,
 * which does not cover the page. Closed, all it showed are remembered, in the visitor's browser only. The
 * announcements' own popup (resources/js/popup.js) waits until these are done.
 */
const KEY = 'site-popups-seen';
const today = () => new Date().toISOString().slice(0, 10);

function seen() {
    try {
        return JSON.parse(localStorage.getItem(KEY) || '{}');
    } catch {
        return {};
    }
}

function remember(keys) {
    try {
        const all = seen();
        keys.forEach((key) => (all[key] = today()));
        localStorage.setItem(KEY, JSON.stringify(Object.fromEntries(Object.entries(all).slice(-30))));
    } catch {
        // Not remembered: they open again next time.
    }
}

function due(item) {
    const when = seen()[item.dataset.sitePopup];
    if (item.dataset.frequency === 'always' || !when) return true;
    return item.dataset.frequency === 'daily' && when !== today();
}

export function startSitePopups() {
    const queue = [];

    document.querySelectorAll('dialog[data-site-popups]').forEach((dialog) => {
        const items = [...dialog.querySelectorAll('[data-site-popup]')];
        const dots = [...dialog.querySelectorAll('[data-slider-dot]')];

        items.forEach((item, i) => {
            if (!due(item)) {
                item.remove();
                dots[i]?.remove();
            }
        });

        const left = dialog.querySelectorAll('[data-site-popup]');
        if (!left.length) return dialog.remove();

        // One left of several: no arrows, no dots.
        if (left.length === 1) {
            dialog.querySelectorAll('[data-slider-prev], [data-slider-next], .popup-dots').forEach((el) => el.remove());
            dialog.querySelector('[data-slider]')?.removeAttribute('data-slider');
        }

        queue.push(dialog);
    });

    if (!queue.length || typeof HTMLDialogElement === 'undefined') return;

    window.sitePopupsBusy = true;

    const next = () => {
        const dialog = queue.shift();
        if (!dialog) {
            window.sitePopupsBusy = false;
            document.dispatchEvent(new Event('site-popups-done'));
            return;
        }

        dialog.addEventListener('close', () => {
            remember([...dialog.querySelectorAll('[data-site-popup]')].map((item) => item.dataset.sitePopup));
            setTimeout(next, 300);
        }, { once: true });
        dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
        dialog.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowLeft') dialog.querySelector('[data-slider-prev]')?.click();
            if (event.key === 'ArrowRight') dialog.querySelector('[data-slider-next]')?.click();
        });

        if (dialog.classList.contains('is-corner')) {
            dialog.show();
        } else {
            dialog.showModal();
            dialog.focus();
        }
    };

    setTimeout(next, 600);
}
