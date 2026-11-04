/**
 * Looking at the photographs of an album one after another, large, without leaving the page.
 *
 *   <ul data-gallery> <a href="large.webp" data-lightbox><img alt="..."></a> ... </ul>
 *
 * Without this each link simply opens its picture, as it always did. With it, a press opens a window over
 * the page, as a Mac's: a bar with the round close button, the album's name and where in it the reader is,
 * the picture, and under it a strip of every photograph of the album, so a long album is seen to be long and
 * any of it is one press away. "All photographs" lays the whole album out at once in a grid; a press on one
 * opens it alone again. Escape closes it; the arrows, the arrow keys or a swipe move through it.
 * A link on its own, outside any [data-gallery], is a gallery of one.
 */
export function startLightbox() {
    const links = [...document.querySelectorAll('a[data-lightbox]')];

    if (links.length === 0 || typeof HTMLDialogElement === 'undefined') return;

    const dialog = document.createElement('dialog');
    dialog.className = 'lightbox';
    dialog.innerHTML = `
        <div class="lightbox-window">
            <div class="lightbox-bar">
                <button type="button" class="lightbox-close" data-close></button>
                <span class="lightbox-title"></span>
                <span class="lightbox-count" aria-live="polite"></span>
                <button type="button" class="lightbox-all" data-all aria-pressed="false"></button>
            </div>
            <div class="lightbox-stage">
                <button type="button" class="lightbox-button lightbox-prev" data-prev>&#8249;</button>
                <img class="lightbox-picture" alt="">
                <button type="button" class="lightbox-button lightbox-next" data-next>&#8250;</button>
            </div>
            <div class="lightbox-grid" hidden></div>
            <div class="lightbox-strip" role="list"></div>
        </div>`;
    document.body.append(dialog);

    const picture = dialog.querySelector('.lightbox-picture');
    const stage = dialog.querySelector('.lightbox-stage');
    const count = dialog.querySelector('.lightbox-count');
    const title = dialog.querySelector('.lightbox-title');
    const strip = dialog.querySelector('.lightbox-strip');
    const grid = dialog.querySelector('.lightbox-grid');
    const all = dialog.querySelector('[data-all]');
    const [close, previous, next] = [dialog.querySelector('[data-close]'), dialog.querySelector('[data-prev]'), dialog.querySelector('[data-next]')];
    // The words for the buttons come from the page, in the reader's language.
    const words = document.body.dataset;
    close.setAttribute('aria-label', words.lightboxClose || 'Close');
    previous.setAttribute('aria-label', words.lightboxPrevious || 'Previous');
    next.setAttribute('aria-label', words.lightboxNext || 'Next');

    // The whole album at once, or one photograph at a time.
    const lay = (whole) => {
        dialog.classList.toggle('is-grid', whole);
        all.setAttribute('aria-pressed', String(whole));
        grid.hidden = !whole;
    };

    let set = [];
    let at = 0;

    const show = (index) => {
        lay(false);
        at = (index + set.length) % set.length;
        picture.classList.remove('is-in');
        picture.src = set[at].href;
        // The stage behind it takes the photograph's own colours, out of focus and muted.
        stage.style.setProperty('--photo', `url("${set[at].href}")`);
        picture.alt = set[at].querySelector('img')?.alt ?? '';
        requestAnimationFrame(() => picture.classList.add('is-in'));
        count.textContent = set.length > 1 ? `${at + 1} / ${set.length}` : '';
        previous.hidden = next.hidden = set.length < 2;
        [...strip.children].forEach((thumb, i) => thumb.setAttribute('aria-current', String(i === at)));
        strip.children[at]?.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
    };

    const fill = (gallery) => {
        strip.replaceChildren(...set.map((link, i) => {
            const thumb = document.createElement('button');
            thumb.type = 'button';
            thumb.setAttribute('role', 'listitem');
            thumb.setAttribute('aria-label', link.querySelector('img')?.alt || `${i + 1}`);
            thumb.innerHTML = `<img src="${link.querySelector('img')?.src ?? link.href}" alt="" loading="lazy">`;
            thumb.addEventListener('click', () => show(i));
            return thumb;
        }));
        strip.hidden = set.length < 2;
        all.hidden = set.length < 2;
        // Its words come with the album, in the reader's language.
        all.textContent = gallery?.dataset.galleryAll || 'All';
        grid.replaceChildren(...set.map((link, i) => {
            const tile = document.createElement('button');
            tile.type = 'button';
            tile.setAttribute('aria-label', link.querySelector('img')?.alt || `${i + 1}`);
            tile.innerHTML = `<img src="${link.querySelector('img')?.src ?? link.href}" alt="" loading="lazy">`;
            tile.addEventListener('click', () => show(i));
            return tile;
        }));
        title.textContent = gallery?.dataset.galleryTitle || document.querySelector('h1')?.textContent.trim() || '';
    };

    links.forEach((link) => {
        link.addEventListener('click', (event) => {
            // With Ctrl or a middle press the reader asked for a new tab: let them have it.
            if (event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0) return;

            event.preventDefault();
            const gallery = link.closest('[data-gallery]');
            set = gallery ? [...gallery.querySelectorAll('a[data-lightbox]')] : [link];
            fill(gallery);
            dialog.showModal();
            show(set.indexOf(link));
        });
    });

    close.addEventListener('click', () => dialog.close());
    all.addEventListener('click', () => lay(all.getAttribute('aria-pressed') !== 'true'));
    previous.addEventListener('click', () => show(at - 1));
    next.addEventListener('click', () => show(at + 1));
    // A press on the dark around the window closes it.
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
