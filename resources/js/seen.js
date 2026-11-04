/**
 * "New" as an unread mark: an announcement this visitor has opened ([data-seen-mark] on its page) loses its
 * "New" in the lists ([data-seen-id]). Kept in the visitor's browser only, the last two hundred.
 */
const KEY = 'seen-announcements';

function seen() {
    try {
        return JSON.parse(localStorage.getItem(KEY) || '[]').map(Number).filter(Number.isInteger);
    } catch {
        return [];
    }
}

export function startSeen() {
    const mark = document.querySelector('[data-seen-mark]');

    if (mark) {
        try {
            localStorage.setItem(KEY, JSON.stringify([...new Set([...seen(), Number(mark.dataset.seenMark)])].slice(-200)));
        } catch {
            // Not remembered: it stays "New" in the lists.
        }
    }

    const read = new Set(seen());
    document.querySelectorAll('[data-seen-id]').forEach((row) => {
        if (read.has(Number(row.dataset.seenId))) row.classList.add('is-seen');
    });
}
