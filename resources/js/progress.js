/**
 * How far down an announcement the reader is (announcements/show): a thin line along the top of the screen, set
 * once a frame while scrolling and not at all otherwise.
 */
export function startProgress() {
    const bar = document.querySelector('[data-read-progress]');
    if (!bar) return;

    // How far there is to scroll, measured when the page's size changes (pictures arriving, a turned phone), not
    // on every frame: reading it while scrolling would make the browser lay the page out again each time.
    let end = 0;
    const measure = () => {
        end = document.documentElement.scrollHeight - innerHeight;
        set();
    };

    let waiting = false;
    const set = () => {
        waiting = false;
        bar.style.setProperty('--read', end > 0 ? Math.min(1, scrollY / end) : 0);
    };

    addEventListener('scroll', () => {
        if (!waiting) {
            waiting = true;
            requestAnimationFrame(set);
        }
    }, { passive: true });
    addEventListener('resize', measure, { passive: true });
    if ('ResizeObserver' in window) new ResizeObserver(measure).observe(document.body);
    measure();
}
