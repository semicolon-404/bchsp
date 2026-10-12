/**
 * "Copy link" beside the ways to share a page (components/site/share).
 *
 * The button is in the page hidden: it is shown only where the browser can copy, so nobody is offered
 * a button that does nothing. Pressed, it copies the address and says so for a moment.
 */
export function startShare() {
    if (!navigator.clipboard?.writeText) return;

    document.querySelectorAll('button[data-copy]').forEach((button) => {
        const label = button.querySelector('[data-copy-label]');
        const usual = label.textContent;

        button.hidden = false;
        button.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(button.dataset.copy);
                label.textContent = button.dataset.copied;
                setTimeout(() => (label.textContent = usual), 2000);
            } catch {
                // Refused by the browser (no permission): the address bar still has it.
            }
        });
    });
}
