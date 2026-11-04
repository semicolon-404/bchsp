/**
 * The posters that open over the home page (components/site/banners, part "popup"), in one window.
 *
 * It opens once the page has loaded. A visitor who closes it does not see those posters again in
 * that browser; when a poster is added that they have not closed, the window opens as usual.
 * What was closed is kept in the visitor's browser and nowhere else. With several posters the
 * arrow keys move between them, as the arrows on screen do (resources/js/slider.js).
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

    const ids = dialog.dataset.popup.split(',').map(Number).filter(Number.isInteger);

    if (ids.every((id) => closed().includes(id))) return;

    dialog.addEventListener('close', () => {
        try {
            // The last twenty are plenty: older posters are long gone.
            localStorage.setItem(KEY, JSON.stringify([...new Set([...closed(), ...ids])].slice(-20)));
        } catch {
            // Not remembered: it will open again on the next visit.
        }
    });

    // A press outside the poster closes it too.
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });

    dialog.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') dialog.querySelector('[data-slider-prev]')?.click();
        if (event.key === 'ArrowRight') dialog.querySelector('[data-slider-next]')?.click();
    });

    dialog.showModal();
}
