/**
 * The news on the home page read two ways: with the details (a calendar leaf, the kind, cards with pictures)
 * or as a plain list of titles and dates, more of them. The visitor's choice is kept in their browser.
 */
const KEY = 'news-view';

export function startNewsView() {
    const box = document.querySelector('[data-news-view]');

    if (!box) return;

    const buttons = [...box.querySelectorAll('[data-news-view-as]')];
    const show = (view) => {
        box.dataset.newsView = view;
        buttons.forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.newsViewAs === view)));
    };

    try {
        if (localStorage.getItem(KEY) === 'list') show('list');
    } catch {
        // Not remembered: the details, as for everyone.
    }

    buttons.forEach((button) => button.addEventListener('click', () => {
        show(button.dataset.newsViewAs);
        try {
            localStorage.setItem(KEY, button.dataset.newsViewAs);
        } catch {
            // Kept for this page only.
        }
    }));
}
