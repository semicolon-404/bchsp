/**
 * "Copy link" beside the ways to share a page (components/site/share).
 *
 * The button is in the page hidden: it is shown only where the browser can copy, so nobody is offered
 * a button that does nothing. Pressed, it copies the address and says so for a moment.
 */
export function startShare() {
    if (!navigator.clipboard?.writeText) return;

    document.querySelectorAll('button[data-copy]').forEach((button) => {
        const label = button.querySelector('[data-copy-label]');
        const usual = label.textContent;

        button.hidden = false;
        button.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(button.dataset.copy);
                label.textContent = button.dataset.copied;
                setTimeout(() => (label.textContent = usual), 2000);
            } catch {
                // Refused by the browser (no permission): the address bar still has it.
            }
        });
    });
}

/**
 * "Share" under a post (components/site/post): opens the share window (components/site/share-sheet), as a video
 * site's, filled with that post: LINE, Facebook, X, e-mail, a QR code, the phone's own sharing where there is one,
 * and the address to copy. Where the page has no window, the phone's own sharing, or else the address copied.
 */
export function startPostShare() {
    const sheet = document.querySelector('[data-share-sheet]');
    if (!document.querySelector('button[data-share-url]')) return;
    // One listener for the page, so the posts brought in later by "Show more" share too.
    const onShare = (handler) => document.addEventListener('click', (event) => {
        const button = event.target.closest?.('button[data-share-url]');
        if (button) handler(button);
    });

    let current = { url: '', title: '' };

    if (sheet && typeof sheet.showModal === 'function') {
        const field = sheet.querySelector('[data-share-sheet-url]');
        const copy = sheet.querySelector('[data-share-sheet-copy]');
        const native = sheet.querySelector('[data-share-native]');
        const copyLabel = copy.textContent;

        if (navigator.share) native.hidden = false;
        native.querySelector('button').addEventListener('click', () => {
            navigator.share({ title: current.title, url: current.url }).catch(() => {});
        });

        copy.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(field.value);
            } catch {
                field.select();
                document.execCommand?.('copy');
            }
            copy.textContent = copy.dataset.copied;
            setTimeout(() => (copy.textContent = copyLabel), 2000);
        });

        field.addEventListener('focus', () => field.select());

        // A press on the dimmed page around the window closes it.
        sheet.addEventListener('click', (event) => {
            if (event.target === sheet) sheet.close();
        });

        const links = (url, title) => {
            const u = encodeURIComponent(url);
            const t = encodeURIComponent(title);
            return {
                line: `https://social-plugins.line.me/lineit/share?url=${u}`,
                facebook: `https://www.facebook.com/sharer/sharer.php?u=${u}`,
                x: `https://twitter.com/intent/tweet?url=${u}&text=${t}`,
                email: `mailto:?subject=${t}&body=${u}`,
                qr: `${sheet.dataset.qr}?to=${encodeURIComponent(new URL(url).pathname)}`,
            };
        };

        onShare((button) => {
            current = { url: button.dataset.shareUrl, title: button.dataset.shareTitle };
            const to = links(current.url, current.title);
            sheet.querySelectorAll('[data-share-to]').forEach((a) => (a.href = to[a.dataset.shareTo]));
            sheet.querySelector('[data-share-sheet-title]').textContent = current.title;
            // Thai addresses read as Thai, not as %E0%B8...; what is copied is the same address.
            try { field.value = decodeURI(current.url); } catch { field.value = current.url; }
            copy.textContent = copyLabel;
            sheet.showModal();
            // The window itself holds the focus at first, so no button is ringed before anyone touches it.
            sheet.focus();
        });

        return;
    }

    onShare(async (button) => {
        const label = button.querySelector('[data-share-label]');
        const usual = label.dataset.usual ??= label.textContent;
        {
            const url = button.dataset.shareUrl;
            try {
                if (navigator.share) {
                    await navigator.share({ title: button.dataset.shareTitle, url });
                } else if (navigator.clipboard?.writeText) {
                    await navigator.clipboard.writeText(url);
                    label.textContent = button.dataset.shared;
                    setTimeout(() => (label.textContent = usual), 2000);
                } else {
                    window.open(url, '_blank', 'noopener');
                }
            } catch {
                // The reader closed the sharing sheet, or the browser refused: nothing to do.
            }
        }
    });
}
