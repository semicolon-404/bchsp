/**
 * The photographs behind the first screen of the home page, one after another (components/site/hero-photos).
 *
 *   <section data-hero data-kind data-show> <div data-hero-slide data-kind="art" class="is-on"> <img data-hero-slide data-kind="photo" data-show="caption" data-src="..."> ...
 *       <span data-hero-caption> ... <button data-hero-dot> ... <button data-hero-pause>
 *
 * Every few seconds the next one fades in, with its line. It never moves by itself for a reader who asked
 * for less motion, and stops while the bar is under the pointer or holds the focus, while the page is not
 * being looked at, and for good once "pause" is pressed. A dot shows that photograph. Without this the
 * first photograph simply stays.
 */
const EVERY = 6500;

export function startHero() {
    const root = document.querySelector('[data-hero]');
    const slides = root ? [...root.querySelectorAll('[data-hero-slide]')] : [];

    if (slides.length < 2) return;

    const captions = [...root.querySelectorAll('[data-hero-caption]')];
    const dots = [...root.querySelectorAll('[data-hero-dot]')];
    const bar = root.querySelector('[data-hero-bar]');
    const pause = root.querySelector('[data-hero-pause]');
    const still = window.matchMedia('(prefers-reduced-motion: reduce)');

    let at = 0;
    let timer = null;
    let stopped = still.matches;
    let held = false;

    // The others are fetched once the page itself is there, so they do not slow its first paint.
    const fetchRest = () => slides.forEach((slide) => slide.dataset.src && ((slide.src = slide.dataset.src), delete slide.dataset.src));
    document.readyState === 'complete' ? fetchRest() : window.addEventListener('load', fetchRest, { once: true });

    const show = (index) => {
        const next = (index + slides.length) % slides.length;

        // Not before it has arrived: fading to an empty frame would flash the page behind. (The drawing is always there.)
        if (slides[next].dataset.src || (slides[next].tagName === 'IMG' && !slides[next].complete)) return;

        at = next;
        // What is over it follows it: the welcome, its own line, or nothing (app.css reads these two).
        root.dataset.kind = slides[at].dataset.kind;
        root.dataset.show = slides[at].dataset.show;
        slides.forEach((slide, i) => slide.classList.toggle('is-on', i === at));
        captions.forEach((caption, i) => (caption.hidden = i !== at));
        dots.forEach((dot, i) => dot.setAttribute('aria-current', i === at ? 'true' : 'false'));
    };

    const run = () => {
        clearInterval(timer);
        timer = stopped || held || document.hidden ? null : setInterval(() => show(at + 1), EVERY);
        root.classList.toggle('is-paused', stopped);
        pause?.setAttribute('aria-label', stopped ? pause.dataset.play : pause.dataset.stop);
    };

    dots.forEach((dot, i) => dot.addEventListener('click', () => (show(i), run())));

    if (pause) {
        pause.hidden = false;
        pause.addEventListener('click', () => ((stopped = !stopped), run()));
    }

    const hold = (on) => () => ((held = on), run());
    bar?.addEventListener('pointerenter', hold(true));
    bar?.addEventListener('pointerleave', hold(false));
    bar?.addEventListener('focusin', hold(true));
    bar?.addEventListener('focusout', hold(false));
    document.addEventListener('visibilitychange', run);

    run();
}
