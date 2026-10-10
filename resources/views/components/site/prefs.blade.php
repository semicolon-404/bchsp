{{--
    In <head>, before the stylesheet: puts the visitor's own look and size of words on the page before it is
    first painted, so nothing flashes. The buttons are handled by resources/js/prefs.js; keep the two in step
    (the same keys, the same sizes).

    The site opens light. "system" is what the admin panel may have stored under the same key.
--}}
<script>
    (function () {
        try {
            var theme = localStorage.getItem('theme');
            var dark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.dataset.theme = dark ? 'dark' : 'light';

            var sizes = [100, 112.5, 125, 140];
            var step = parseInt(localStorage.getItem('font-size'), 10);
            if (step >= 0 && step < sizes.length && step !== 1) {
                document.documentElement.style.fontSize = sizes[step] + '%';
            }
        } catch (e) {
            document.documentElement.dataset.theme = 'light';
        }
    })();
</script>
