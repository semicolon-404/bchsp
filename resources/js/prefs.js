/**
 * What a visitor chose for their own eyes: the light or the dark look, and how large the words are.
 *
 * Both are kept in the visitor's browser and nowhere else. The look is stored under the same key
 * ("theme") and with the same values the admin panel uses, so switching in one place switches the other.
 * The words are sized by the <html> element; everything on the site is measured in rem, so it all follows.
 *
 * The first paint is set by a few lines in <head> (components/site/prefs.blade.php), so that a page
 * never flashes in the wrong look. Keep the two in step.
 */
const THEME_KEY = 'theme';
const SIZE_KEY = 'font-size';

/** Percent of the browser's own size. The second is what the site opens in (app.css). */
export const SIZES = [100, 112.5, 125, 140];
export const DEFAULT_SIZE = 1;

const read = (key) => {
    try {
        return localStorage.getItem(key);
    } catch {
        // Storage blocked: stay on the defaults for this visit.
        return null;
    }
};

const write = (key, value) => {
    try {
        localStorage.setItem(key, value);
    } catch {
        // Not remembered, but still applied.
    }
};

function currentSize() {
    const saved = Number.parseInt(read(SIZE_KEY) ?? '', 10);

    return Number.isInteger(saved) && saved >= 0 && saved < SIZES.length ? saved : DEFAULT_SIZE;
}

function applySize(step) {
    document.documentElement.style.fontSize = step === DEFAULT_SIZE ? '' : `${SIZES[step]}%`;

    document.querySelectorAll('[data-font-size]').forEach((button) => {
        const change = button.dataset.fontSize;

        button.disabled = (change === 'smaller' && step === 0) || (change === 'larger' && step === SIZES.length - 1);
    });
}

function isDark() {
    return document.documentElement.dataset.theme === 'dark';
}

function applyTheme(dark) {
    document.documentElement.dataset.theme = dark ? 'dark' : 'light';

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.setAttribute('aria-pressed', dark ? 'true' : 'false');
    });
}

export function startPrefs() {
    applySize(currentSize());
    applyTheme(isDark());

    document.addEventListener('click', (event) => {
        const size = event.target.closest('[data-font-size]');

        if (size) {
            const change = size.dataset.fontSize;
            const step = change === 'normal' ? DEFAULT_SIZE : Math.min(SIZES.length - 1, Math.max(0, currentSize() + (change === 'larger' ? 1 : -1)));

            write(SIZE_KEY, String(step));
            applySize(step);

            return;
        }

        if (event.target.closest('[data-theme-toggle]')) {
            const dark = !isDark();

            write(THEME_KEY, dark ? 'dark' : 'light');
            applyTheme(dark);
        }
    });

    // Changed in another tab, or in the admin panel.
    window.addEventListener('storage', (event) => {
        if (event.key === THEME_KEY) {
            applyTheme(event.newValue === 'dark');
        }

        if (event.key === SIZE_KEY) {
            applySize(currentSize());
        }
    });
}
