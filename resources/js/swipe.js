/**
 * Moving between the pages of a group with a finger (resources/views/page.blade.php).
 *
 * A page that has a page before or after it carries their addresses on [data-swipe]:
 * a swipe to the left opens the next one, a swipe to the right the one before, as when
 * turning the pages of a book. The links are on the page as well, so nothing depends on this.
 */
const FAR_ENOUGH = 80;

export function startSwipe() {
    const pages = document.querySelector('[data-swipe]');

    if (!pages) return;

    let start = null;

    document.addEventListener('touchstart', (event) => {
        // One finger, and not on something that itself moves sideways: a wide table, a slider.
        const sideways = event.target.closest?.('table, pre, [data-slider], dialog');

        start = event.touches.length === 1 && !sideways ? { x: event.touches[0].clientX, y: event.touches[0].clientY } : null;
    }, { passive: true });

    document.addEventListener('touchend', (event) => {
        if (!start) return;

        const across = event.changedTouches[0].clientX - start.x;
        const down = event.changedTouches[0].clientY - start.y;
        start = null;

        // Clearly sideways, not a scroll that drifted, and not while some text is selected.
        if (Math.abs(across) < FAR_ENOUGH || Math.abs(across) < Math.abs(down) * 2 || String(window.getSelection?.() ?? '') !== '') return;

        const address = across < 0 ? pages.dataset.swipeNext : pages.dataset.swipePrev;

        if (address) window.location.assign(address);
    }, { passive: true });
}
