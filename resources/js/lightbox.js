/**
 * Looking at the photographs of an album without leaving the page, as a post opened on a social page.
 *
 *   <ul data-gallery data-gallery-title="..." data-gallery-kind="..." data-gallery-date="..."> <a href="large.webp" data-lightbox><img alt="..."></a> ... </ul>
 *
 * Without this each link simply opens its picture, as it always did. With it, a press opens a card over the
 * page: on the left the photograph, whole, on its own colours blurred, with the way to the one before and
 * after; on the right what it belongs to (its kind, its title, its day) and every photograph of the album as
 * small tiles, the one shown marked, so a long album is seen to be long and any of it is one press away.
 * On a phone the tiles go under the photograph. Escape, the round button or a press outside closes it; the
 * arrows, the arrow keys or a swipe move through it. A link outside any [data-gallery] is an album of one.
 */
export function startLightbox() {
    // A feed that brings more posts onto the page (resources/js/toc.js) may bring photographs later on.
    if (typeof HTMLDialogElement === 'undefined') return;
    if (!document.querySelector('a[data-lightbox]') && !document.querySelector('[data-feed]')) return;

    const dialog = document.createElement('dialog');
    dialog.className = 'lightbox';
    dialog.innerHTML = `
        <div class="lightbox-card">
            <div class="lightbox-stage">
                <button type="button" class="lightbox-round lightbox-close" data-close>&#10005;</button>
                <button type="button" class="lightbox-round lightbox-prev" data-prev>&#8249;</button>
                <img class="lightbox-picture" alt="">
                <button type="button" class="lightbox-round lightbox-next" data-next>&#8250;</button>
            </div>
            <aside class="lightbox-side">
                <p class="lightbox-kind"></p>
                <h2 class="lightbox-title"></h2>
                <p class="lightbox-date"></p>
                <p class="lightbox-count" aria-live="polite"></p>
                <div class="lightbox-tiles"></div>
                <div class="lightbox-details rich"></div>
            </aside>
        </div>`;
    document.body.append(dialog);

    const $ = (selector) => dialog.querySelector(selector);
    const [picture, stage, side, kind, title, date, count, tiles, details] = ['.lightbox-picture', '.lightbox-stage', '.lightbox-side', '.lightbox-kind', '.lightbox-title', '.lightbox-date', '.lightbox-count', '.lightbox-tiles', '.lightbox-details'].map($);
    const [close, previous, next] = ['[data-close]', '[data-prev]', '[data-next]'].map($);
    // The words for the buttons come from the page, in the reader's language.
    const words = document.body.dataset;
    close.setAttribute('aria-label', words.lightboxClose || 'Close');
    previous.setAttribute('aria-label', words.lightboxPrevious || 'Previous');
    next.setAttribute('aria-label', words.lightboxNext || 'Next');

    let set = [];
    let at = 0;

    const show = (index) => {
        at = (index + set.length) % set.length;
        picture.classList.remove('is-in');
        picture.src = set[at].href;
        picture.alt = set[at].querySelector('img')?.alt ?? '';
        requestAnimationFrame(() => picture.classList.add('is-in'));
        // Behind the photograph, its own colours, out of focus.
        stage.style.setProperty('--photo', `url("${set[at].href}")`);
        count.textContent = set.length > 1 ? `${at + 1} / ${set.length}` : '';
        previous.hidden = next.hidden = set.length < 2;
        [...tiles.children].forEach((tile, i) => tile.setAttribute('aria-current', String(i === at)));
        tiles.children[at]?.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    };

    const fill = (gallery, link) => {
        // What it is about: from its album, or from the picture itself when it stands alone.
        const about = gallery?.dataset ?? link?.dataset ?? {};
        // The post's own words beside the picture, as they are on the page (already made safe there).
        const words = document.querySelector(about.lightboxDetails || '#none');
        details.innerHTML = words ? words.innerHTML : '';
        details.hidden = !words;
        kind.textContent = about.galleryKind || '';
        kind.style.color = about.galleryKindColour || '';
        title.textContent = about.galleryTitle || document.querySelector('h1')?.textContent.trim() || '';
        date.textContent = about.galleryDate || '';
        side.hidden = !title.textContent && set.length < 2 && details.hidden;
        tiles.replaceChildren(...set.map((link, i) => {
            const tile = document.createElement('button');
            tile.type = 'button';
            tile.setAttribute('aria-label', link.querySelector('img')?.alt || `${i + 1}`);
            tile.innerHTML = `<img src="${link.querySelector('img')?.src ?? link.href}" alt="" loading="lazy">`;
            tile.addEventListener('click', () => show(i));
            return tile;
        }));
        tiles.hidden = set.length < 2;
    };

    // One listener for the page, so photographs brought in later open here too.
    document.addEventListener('click', (event) => {
        const link = event.target.closest?.('a[data-lightbox]');
        // With Ctrl or a middle press the reader asked for a new tab: let them have it.
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0) return;

        event.preventDefault();
        const gallery = link.closest('[data-gallery]');
        set = gallery ? [...gallery.querySelectorAll('a[data-lightbox]')] : [link];
        fill(gallery, link);
        dialog.showModal();
        show(set.indexOf(link));
    });

    close.addEventListener('click', () => dialog.close());
    previous.addEventListener('click', () => show(at - 1));
    next.addEventListener('click', () => show(at + 1));
    // A press outside the card closes it.
    dialog.addEventListener('click', (event) => event.target === dialog && dialog.close());
    dialog.addEventListener('close', () => picture.removeAttribute('src'));
    dialog.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') show(at - 1);
        if (event.key === 'ArrowRight') show(at + 1);
    });

    let from = null;
    picture.addEventListener('touchstart', (event) => (from = event.touches.length === 1 ? event.touches[0].clientX : null), { passive: true });
    picture.addEventListener('touchend', (event) => {
        if (from === null || set.length < 2) return;

        const across = event.changedTouches[0].clientX - from;
        from = null;

        if (Math.abs(across) > 60) show(at + (across < 0 ? 1 : -1));
    }, { passive: true });
}
