/**
 * Parts of the home page read two ways: with the details (pictures, cards) or as a plain list. Each part
 * ([data-news-view]) has its own buttons and its own choice, kept in the visitor's browser under its key
 * (data-news-view-key; the news itself uses "news-view").
 */
export function startNewsView() {
    document.querySelectorAll('[data-news-view]').forEach((box) => {
        const key = box.dataset.newsViewKey || 'news-view';
        // Its own buttons only, not those of a part inside it.
        const buttons = [...box.querySelectorAll('[data-news-view-as]')].filter((button) => button.closest('[data-news-view]') === box);
        const show = (view) => {
            box.dataset.newsView = view;
            buttons.forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.newsViewAs === view)));
        };

        try {
            if (localStorage.getItem(key) === 'list') show('list');
        } catch {
            // Not remembered: the details, as for everyone.
        }

        buttons.forEach((button) => button.addEventListener('click', () => {
            show(button.dataset.newsViewAs);
            try {
                localStorage.setItem(key, button.dataset.newsViewAs);
            } catch {
                // Kept for this page only.
            }
        }));
    });
}
