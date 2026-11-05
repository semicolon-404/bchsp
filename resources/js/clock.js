/**
 * The clock on the board of today's clinics (home page): the server writes the time as the page is made; this keeps
 * it going, a look every fifteen seconds, so the board still tells the time when the page has been open a while.
 */
export function startClock() {
    const clocks = document.querySelectorAll('[data-clock]');
    if (!clocks.length) return;

    const tick = () => {
        const now = new Date();
        const text = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
        clocks.forEach((clock) => {
            if (clock.textContent !== text) clock.textContent = text;
        });
    };

    setInterval(tick, 15000);
}
