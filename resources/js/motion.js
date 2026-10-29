/**
 * The site's small movements: parts of a page rise into place as they are scrolled to, and figures
 * count up to their value the first time they are seen.
 *
 * Both are added here, never in the page: without scripts everything is simply there. And both are
 * left out for a reader who has asked their device for less motion.
 */
const RISES = 'main .card, main .code-block, main section > .wrap > h2, main [data-reveal]';

export function startMotion() {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) return;

    rise();
    countUp();
}

function rise() {
    const seen = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;

            entry.target.classList.add('is-in');
            // Once in place, the wait goes, so that a hover answers at once.
            entry.target.addEventListener('transitionend', () => entry.target.style.removeProperty('transition-delay'), { once: true });
            seen.unobserve(entry.target);
        });
    }, { rootMargin: '0px 0px -8% 0px' });

    document.querySelectorAll(RISES).forEach((part) => {
        // What is on the screen as the page opens stays put: only what is scrolled to moves.
        // One inside another that rises does not rise again.
        if (part.getBoundingClientRect().top < window.innerHeight || part.parentElement.closest('.rise')) return;

        part.classList.add('rise');
        // Cards side by side in a row come one after another, not all at once.
        const row = [...part.parentElement.children].filter((child) => child.classList.contains('rise'));
        part.style.transitionDelay = `${Math.min(row.indexOf(part), 5) * 70}ms`;
        seen.observe(part);
    });
}

function countUp() {
    const seen = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;

            seen.unobserve(entry.target);
            run(entry.target);
        });
    });

    document.querySelectorAll('[data-count]').forEach((figure) => {
        // Only a plain whole number is counted; anything else is left as it is written.
        if (/^[\d,]+$/.test(figure.textContent.trim())) seen.observe(figure);
    });
}

function run(figure) {
    const end = Number(figure.textContent.replace(/,/g, ''));
    const written = figure.textContent;
    const started = performance.now();
    const lasts = 700;

    if (!Number.isFinite(end) || end < 2) return;

    const step = (now) => {
        const done = Math.min(1, (now - started) / lasts);
        // Quick at first, slowing into the value.
        const eased = 1 - (1 - done) ** 3;

        figure.textContent = done < 1 ? Math.round(end * eased).toLocaleString('en-US') : written;

        if (done < 1) requestAnimationFrame(step);
    };

    requestAnimationFrame(step);
}
