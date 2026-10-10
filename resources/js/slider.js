/**
 * The slides at the top of the home page (components/site/banners, part "banners").
 *
 * The slides sit side by side in a strip that scrolls sideways and snaps, so they can be swiped
 * and work without this file. What this adds: the arrows, the dots, and moving on by itself every
 * few seconds (longer on a slide that asks for it). It never moves while someone is pointing at it or has the keyboard in it, while the
 * tab is hidden, or for a visitor whose device asks for less motion.
 */
const EVERY = 6000;

export function startSlider() {
    document.querySelectorAll('[data-slider]').forEach((slider) => {
        const track = slider.querySelector('[data-slider-track]');
        const slides = [...track.children];
        const dots = [...slider.querySelectorAll('[data-slider-dot]')];

        if (slides.length < 2) return;

        const current = () => Math.round(track.scrollLeft / track.clientWidth);
        const go = (index) => track.scrollTo({ left: ((index + slides.length) % slides.length) * track.clientWidth, behavior: 'smooth' });
        const mark = () => dots.forEach((dot, index) => dot.setAttribute('aria-current', index === current() ? 'true' : 'false'));

        slider.querySelector('[data-slider-prev]')?.addEventListener('click', () => go(current() - 1));
        slider.querySelector('[data-slider-next]')?.addEventListener('click', () => go(current() + 1));
        dots.forEach((dot, index) => dot.addEventListener('click', () => go(index)));
        track.addEventListener('scroll', () => requestAnimationFrame(mark), { passive: true });
        mark();

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        let paused = false;

        ['pointerenter', 'focusin'].forEach((event) => slider.addEventListener(event, () => (paused = true)));
        ['pointerleave', 'focusout'].forEach((event) => slider.addEventListener(event, () => (paused = false)));

        // A slide can ask to be held longer (data-slider-hold, in milliseconds): the first one has buttons to press.
        const wait = () => setTimeout(() => {
            if (!paused && !document.hidden) go(current() + 1);
            wait();
        }, Number(slides[current()]?.dataset.sliderHold) || EVERY);

        wait();
    });
}
