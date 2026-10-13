/**
 * "Back to the top" on a long page (components/layouts/site).
 *
 * The button is in the page hidden: it is a plain link to the top of the content, so it would work
 * without this, but it has no business on a short page or at the top of a long one. Here it is shown
 * once the reader is more than a screen and a half down, and hidden again near the top.
 */
export function startTop() {
    const button = document.querySelector('[data-to-top]');

    if (!button) return;

    let waiting = false;
    const look = () => {
        button.hidden = window.scrollY < window.innerHeight * 1.5;
        waiting = false;
    };

    window.addEventListener('scroll', () => {
        if (waiting) return;

        waiting = true;
        requestAnimationFrame(look);
    }, { passive: true });

    button.addEventListener('click', (event) => {
        event.preventDefault();
        window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
        document.getElementById('main')?.focus?.({ preventScroll: true });
    });

    look();
}
