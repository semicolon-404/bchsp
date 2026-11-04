/**
 * A row of cards that scrolls sideways (the home page's most read): swiped on a phone, dragged with the
 * mouse on a computer, or moved a card at a time with the arrows beside its heading, which grey out at
 * either end. It works as a plain scrolling row without this file.
 */
export function startRail() {
    document.querySelectorAll('[data-rail]').forEach((rail) => {
        const box = rail.parentElement;
        const prev = box.querySelector('[data-rail-prev]');
        const next = box.querySelector('[data-rail-next]');
        const step = () => (rail.firstElementChild?.getBoundingClientRect().width ?? 200) + 12;
        const mark = () => {
            if (prev) prev.disabled = rail.scrollLeft <= 2;
            if (next) next.disabled = rail.scrollLeft + rail.clientWidth >= rail.scrollWidth - 2;
        };

        prev?.addEventListener('click', () => rail.scrollBy({ left: -step(), behavior: 'smooth' }));
        next?.addEventListener('click', () => rail.scrollBy({ left: step(), behavior: 'smooth' }));
        rail.addEventListener('scroll', () => requestAnimationFrame(mark), { passive: true });
        mark();

        // Dragged with the mouse: a press that moves more than a few pixels is a drag, not a click on a card.
        let start = null;
        rail.addEventListener('pointerdown', (event) => {
            if (event.pointerType !== 'mouse') return;
            start = { x: event.clientX, left: rail.scrollLeft, moved: false };
        });
        window.addEventListener('pointermove', (event) => {
            if (!start) return;
            const dx = event.clientX - start.x;
            if (!start.moved && Math.abs(dx) > 5) {
                start.moved = true;
                rail.classList.add('is-dragging');
            }
            if (start.moved) rail.scrollLeft = start.left - dx;
        });
        window.addEventListener('pointerup', () => {
            if (start?.moved) {
                rail.classList.remove('is-dragging');
                // Settle on the nearest card.
                rail.scrollTo({ left: Math.round(rail.scrollLeft / step()) * step(), behavior: 'smooth' });
            }
            start = null;
        });
        rail.addEventListener('dragstart', (event) => event.preventDefault());
    });
}

/** "Show more" under a list cut short: opens the rest, and closes it again. */
export function startMore() {
    document.querySelectorAll('[data-more]').forEach((button) => {
        const list = button.previousElementSibling;
        button.addEventListener('click', () => {
            const open = button.getAttribute('aria-expanded') !== 'true';
            button.setAttribute('aria-expanded', String(open));
            list.classList.toggle('is-open', open);
        });
    });
}
