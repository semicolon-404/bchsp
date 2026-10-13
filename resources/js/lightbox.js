/**
 * Looking at the photographs of an album one after another, large, without leaving the page.
 *
 *   <ul data-gallery> <a href="large.webp" data-lightbox><img alt="..."></a> ... </ul>
 *
 * Without this each link simply opens its picture, as it always did. With it, a press opens the picture
 * over the page in a <dialog> (Escape closes it, the page behind is inert), with the way to the one
 * before and the one after: the buttons, the arrow keys, or a swipe. A link on its own, outside any
 * [data-gallery], is a gallery of one.
 */
export function startLightbox() {
    const links = [...document.querySelectorAll('a[data-lightbox]')];

    if (links.length === 0 || typeof HTMLDialogElement === 'undefined') return;

    const dialog = document.createElement('dialog');
    dialog.className = 'lightbox';
    dialog.innerHTML = `
        <div class="lightbox-bar">
            <span class="lightbox-count" aria-live="polite"></span>
            <button type="button" class="lightbox-button" data-close></button>
        </div>
        <button type="button" class="lightbox-button lightbox-prev" data-prev>&#8249;</button>
        <img class="lightbox-picture" alt="">
        <button type="button" class="lightbox-button lightbox-next" data-next>&#8250;</button>`;
    document.body.append(dialog);

    const picture = dialog.querySelector('img');
    const count = dialog.querySelector('.lightbox-count');
    const [close, previous, next] = [dialog.querySelector('[data-close]'), dialog.querySelector('[data-prev]'), dialog.querySelector('[data-next]')];
    // The words for the buttons come from the page, in the reader's language.
    const words = document.body.dataset;
    close.textContent = '✕';
    close.setAttribute('aria-label', words.lightboxClose || 'Close');
    previous.setAttribute('aria-label', words.lightboxPrevious || 'Previous');
    next.setAttribute('aria-label', words.lightboxNext || 'Next');

    let set = [];
    let at = 0;

    const show = (index) => {
        at = (index + set.length) % set.length;
        picture.src = set[at].href;
        picture.alt = set[at].querySelector('img')?.alt ?? '';
        count.textContent = set.length > 1 ? `${at + 1} / ${set.length}` : '';
        previous.hidden = next.hidden = set.length < 2;
    };

    links.forEach((link) => {
        link.addEventListener('click', (event) => {
            // With Ctrl or a middle press the reader asked for a new tab: let them have it.
            if (event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0) return;

            event.preventDefault();
            const gallery = link.closest('[data-gallery]');
            set = gallery ? [...gallery.querySelectorAll('a[data-lightbox]')] : [link];
            show(set.indexOf(link));
            dialog.showModal();
        });
    });

    close.addEventListener('click', () => dialog.close());
    previous.addEventListener('click', () => show(at - 1));
    next.addEventListener('click', () => show(at + 1));
    // A press on the dark around the picture closes it.
    dialog.addEventListener('click', (event) => event.target === dialog && dialog.close());
    dialog.addEventListener('close', () => picture.removeAttribute('src'));
    dialog.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') show(at - 1);
        if (event.key === 'ArrowRight') show(at + 1);
    });

    let from = null;
    dialog.addEventListener('touchstart', (event) => (from = event.touches.length === 1 ? event.touches[0].clientX : null), { passive: true });
    dialog.addEventListener('touchend', (event) => {
        if (from === null || set.length < 2) return;

        const across = event.changedTouches[0].clientX - from;
        from = null;

        if (Math.abs(across) > 60) show(at + (across < 0 ? 1 : -1));
    }, { passive: true });
}
