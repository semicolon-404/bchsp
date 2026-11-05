/**
 * The news list's row of kinds, and folded panels.
 *
 * A folded panel (<details data-fold>) closes again when the reader taps outside it or presses Escape,
 * as a menu does. Without this script it still opens and closes from its own button.
 */
export function startFold() {
    // A row of tabs that slides sideways opens with the one being looked at in sight.
    document.querySelectorAll('.news-tabs nav').forEach((row) => {
        const on = row.querySelector('[aria-current="page"]');
        if (on && on.offsetLeft + on.offsetWidth > row.clientWidth) {
            row.scrollLeft = on.offsetLeft - row.offsetLeft - 16;
        }
    });

    const folds = document.querySelectorAll('details[data-fold]');
    if (!folds.length) return;

    document.addEventListener('click', (event) => {
        folds.forEach((fold) => {
            if (fold.open && !fold.querySelector('.news-filters-panel')?.contains(event.target) && !fold.querySelector('summary').contains(event.target)) {
                fold.open = false;
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        folds.forEach((fold) => {
            if (fold.open) {
                fold.open = false;
                fold.querySelector('summary').focus();
            }
        });
    });
}
