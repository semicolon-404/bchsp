/**
 * "Show more" under the news index (?view=index): the next titles fetched and added to this page, so the reader goes
 * on reading down. A heading that carries on from the last one (the same month, say) is not said twice. Without the
 * script, the button is a link to the next page.
 */
export function startToc() {
    const toc = document.querySelector('[data-toc]');
    const more = document.querySelector('[data-toc-more]');
    if (!toc || !more || !window.fetch) return;

    more.addEventListener('click', async (event) => {
        event.preventDefault();
        if (more.getAttribute('aria-busy') === 'true') return;
        more.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(more.href, { headers: { Accept: 'text/html' } });
            if (!response.ok) throw new Error(String(response.status));
            const page = new DOMParser().parseFromString(await response.text(), 'text/html');

            page.querySelectorAll('[data-toc] > section').forEach((section) => {
                const last = toc.querySelector(':scope > section:last-child');
                const heading = (el) => el?.querySelector('.toc-head > span')?.textContent.trim();

                if (last && heading(last) === heading(section)) {
                    last.querySelector('ol').append(...section.querySelectorAll('li'));
                    const count = last.querySelectorAll('li').length;
                    const label = last.querySelector('.toc-head > span:last-child');
                    if (label) label.textContent = label.textContent.replace(/\d[\d,]*/, count.toLocaleString());
                } else {
                    toc.append(section);
                }
            });

            const next = page.querySelector('[data-toc-more]');
            if (next) {
                more.href = next.href;
                more.replaceChildren(...next.childNodes);
                more.removeAttribute('aria-busy');
            } else {
                more.closest('p')?.remove();
            }
        } catch {
            // Something went wrong on the way: the link still leads to the next page.
            window.location.href = more.href;
        }
    });
}
