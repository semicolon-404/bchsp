/**
 * How far down an announcement the reader is (announcements/show): a thin line along the top of the screen, set
 * once a frame while scrolling and not at all otherwise.
 */
export function startProgress() {
    const bar = document.querySelector('[data-read-progress]');
    if (!bar) return;

    let waiting = false;
    const set = () => {
        waiting = false;
        const end = document.documentElement.scrollHeight - innerHeight;
        bar.style.setProperty('--read', end > 0 ? Math.min(1, scrollY / end) : 0);
    };

    addEventListener('scroll', () => {
        if (!waiting) {
            waiting = true;
            requestAnimationFrame(set);
        }
    }, { passive: true });
    set();
}
