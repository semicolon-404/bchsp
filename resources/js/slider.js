/**
 * The slides at the top of the home page (components/site/banners, part "banners").
 *
 * The slides sit side by side in a strip that scrolls sideways and snaps, so they can be swiped
 * and work without this file. What this adds: the arrows, the dots, and moving on by itself every
 * few seconds. It never moves while someone is pointing at it or has the keyboard in it, while the
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

        // The posters in the popup wait for the visitor: they are read, not watched go by.
        if (slider.hasAttribute('data-slider-still') || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        let paused = false;
        let waited = 0;

        // The dot of the slide shown fills as a bar while it waits (resources/css/app.css, [data-slider-auto]).
        slider.setAttribute('data-slider-auto', '');
        slider.style.setProperty('--slider-every', `${EVERY}ms`);
        const restart = () => {
            waited = 0;
            dots.forEach((dot) => {
                dot.style.animation = 'none';
                void dot.offsetWidth;
                dot.style.animation = '';
            });
        };
        const pause = (on) => {
            paused = on;
            slider.classList.toggle('is-paused', on);
        };

        ['pointerenter', 'focusin'].forEach((event) => slider.addEventListener(event, () => pause(true)));
        ['pointerleave', 'focusout'].forEach((event) => slider.addEventListener(event, () => pause(false)));
        track.addEventListener('scrollend', restart);

        setInterval(() => {
            if (paused || document.hidden) return;
            waited += 250;
            if (waited >= EVERY) {
                waited = 0;
                go(current() + 1);
            }
        }, 250);
    });
}
