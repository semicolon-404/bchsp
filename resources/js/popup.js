/**
 * The poster that opens over the home page (components/site/banners, part "popup").
 *
 * It opens once the page has loaded. A visitor who closes it does not see that
 * poster again in that browser; a different poster, put up later, opens as usual.
 * What was closed is kept in the visitor's browser and nowhere else.
 */
const KEY = 'popups-closed';

function closed() {
    try {
        return JSON.parse(localStorage.getItem(KEY) || '[]').map(Number).filter(Number.isInteger);
    } catch {
        return [];
    }
}

export function startPopup() {
    const dialog = document.querySelector('dialog[data-popup]');

    if (!dialog || typeof dialog.showModal !== 'function') return;

    const id = Number(dialog.dataset.popup);

    if (closed().includes(id)) return;

    dialog.addEventListener('close', () => {
        try {
            // The last twenty are plenty: older posters are long gone.
            localStorage.setItem(KEY, JSON.stringify([...closed(), id].slice(-20)));
        } catch {
            // Not remembered: it will open again on the next visit.
        }
    });

    // A press outside the poster closes it too.
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });

    dialog.showModal();
}
