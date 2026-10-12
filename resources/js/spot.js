/**
 * The light under the pointer on cards and editor windows (.card and .code-block in app.css).
 *
 * The stylesheet draws it; this says where: the pointer's place inside the card it is over, as --mx and --my.
 * Under a finger there is no pointer to follow, so a touch lights the card where it landed for a moment.
 * Scrolling and presses are left alone: this only watches.
 */
const LIT = '.card, .code-block';

export function startSpot() {
    const canHover = window.matchMedia('(hover: hover)');
    const touched = new WeakMap();
    let waiting = false;

    const place = (card, event) => {
        const box = card.getBoundingClientRect();

        card.style.setProperty('--mx', `${event.clientX - box.left}px`);
        card.style.setProperty('--my', `${event.clientY - box.top}px`);
    };

    document.addEventListener('pointerdown', (event) => {
        if (event.pointerType === 'mouse') return;

        const card = event.target.closest?.(LIT);

        if (!card) return;

        place(card, event);
        card.classList.add('is-touched');
        clearTimeout(touched.get(card));
        touched.set(card, setTimeout(() => card.classList.remove('is-touched'), 700));
    }, { passive: true });

    // One update per frame is plenty.
    document.addEventListener('pointermove', (event) => {
        if (waiting || !canHover.matches) return;

        const card = event.target.closest?.(LIT);

        if (!card) return;

        waiting = true;
        requestAnimationFrame(() => {
            place(card, event);
            waiting = false;
        });
    }, { passive: true });
}
