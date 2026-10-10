/**
 * The groups of the top bar's menu (components/site/nav): each is a <details>, so it opens and closes
 * without this file. What this adds is what people expect of a menu: opening one closes the others,
 * and a press anywhere else, or Escape, closes whatever is open.
 */
export function startMenu() {
    const groups = () => document.querySelectorAll('details[data-menu]');
    const closeAll = (except = null) => groups().forEach((group) => {
        if (group !== except) group.open = false;
    });

    document.addEventListener('toggle', (event) => {
        if (event.target.matches?.('details[data-menu]') && event.target.open) closeAll(event.target);
    }, true);

    document.addEventListener('click', (event) => {
        if (!event.target.closest('details[data-menu]')) closeAll();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;

        const open = document.querySelector('details[data-menu][open]');

        if (open) {
            open.open = false;
            open.querySelector('summary')?.focus();
        }
    });
}
