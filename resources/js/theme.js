/**
 * Light / dark / system theme (components/site/theme-switch).
 *
 * The choice is stored under the same localStorage key ("theme") and with
 * the same values the Filament admin panel uses, so switching in one place
 * switches the other. The resolved theme is written to <html data-theme>,
 * and app.css re-points the colour variables for [data-theme="light"].
 *
 * The first paint is set by a few lines in <head> (components/layouts/site),
 * so that a page never flashes in the wrong look. The site opens light; the dark look is one press away.
 */
const KEY = 'theme';
const MODES = ['light', 'dark', 'system'];

/** What the site opens in until a visitor chooses: light is what most people, older eyes above all, read most easily. */
const DEFAULT = 'light';
const prefersLight = window.matchMedia('(prefers-color-scheme: light)');

function saved() {
    try {
        const mode = localStorage.getItem(KEY);

        return MODES.includes(mode) ? mode : DEFAULT;
    } catch {
        // Storage blocked: stay on the default for this visit.
        return DEFAULT;
    }
}

function apply(mode) {
    const light = mode === 'light' || (mode === 'system' && prefersLight.matches);

    document.documentElement.dataset.theme = light ? 'light' : 'dark';

    // The switch shows which of the three is chosen.
    document.querySelectorAll('[data-theme-mode]').forEach((button) => {
        button.setAttribute('aria-pressed', button.dataset.themeMode === mode ? 'true' : 'false');
    });
}

export function startTheme() {
    apply(saved());

    document.addEventListener('click', (event) => {
        const mode = event.target.closest('[data-theme-mode]')?.dataset.themeMode;

        if (!MODES.includes(mode)) return;

        try {
            localStorage.setItem(KEY, mode);
        } catch {
            // Not remembered, but still applied.
        }

        apply(mode);
    });

    // Follow the operating system live while in "system" mode.
    prefersLight.addEventListener('change', () => apply(saved()));

    // Changed in another tab, or in the admin panel.
    window.addEventListener('storage', (event) => {
        if (event.key === KEY) apply(saved());
    });
}
