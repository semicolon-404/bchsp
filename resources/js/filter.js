/**
 * A box that narrows a list as one types (the phone numbers page).
 *
 *   <input type="search" data-filter="#the-list" hidden>
 *   <ul id="the-list"><li data-filter-text="words to match">...</li></ul>
 *   <p data-filter-none class="hidden">Nothing matches.</p>
 *
 * The box is in the page hidden and shown from here: without scripts there is no box that does nothing,
 * and the whole list is simply there. "Print this page" lives here too: [data-print] opens the printer.
 */
export function startFilter() {
    document.querySelectorAll('input[data-filter]').forEach((box) => {
        const rows = [...document.querySelectorAll(`${box.dataset.filter} > [data-filter-text]`)];
        const none = document.querySelector('[data-filter-none]');

        box.hidden = false;
        box.addEventListener('input', () => {
            const words = box.value.trim().toLowerCase().split(/\s+/).filter(Boolean);
            let shown = 0;

            rows.forEach((row) => {
                const fits = words.every((word) => row.dataset.filterText.includes(word));

                row.hidden = !fits;
                // Tailwind's "flex" on the row would outvote the hidden attribute.
                row.style.display = fits ? '' : 'none';
                shown += fits ? 1 : 0;
            });

            none?.classList.toggle('hidden', shown > 0);
        });
    });

    document.querySelectorAll('button[data-print]').forEach((button) => {
        button.hidden = false;
        button.addEventListener('click', () => window.print());
    });
}
