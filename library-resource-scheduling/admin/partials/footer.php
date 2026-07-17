</main>
<footer class="footer">
<p>&copy; <?= date('Y') ?> Academic &amp; Library Resource Scheduling &middot; Admin Panel</p>
</footer>
<script>
(function () {
    var btn = document.getElementById('theme-toggle');
    if (!btn) return;

    function currentTheme() {
        var attr = document.documentElement.getAttribute('data-theme');
        if (attr) return attr;
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function updateIcon() {
        btn.innerHTML = currentTheme() === 'dark' ? '&#9728;' : '&#127769;';
    }

    btn.addEventListener('click', function () {
        var next = currentTheme() === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
        updateIcon();
    });

    updateIcon();
})();
</script>
<script>
(function () {
    var dropdowns = document.querySelectorAll('[data-nav-dropdown]');
    if (!dropdowns.length) return;

    function closeAll() {
        dropdowns.forEach(function (d) {
            d.querySelector('.nav-dropdown-menu').classList.remove('open');
            d.querySelector('.nav-dropdown-trigger').setAttribute('aria-expanded', 'false');
        });
    }

    dropdowns.forEach(function (dropdown) {
        var trigger = dropdown.querySelector('.nav-dropdown-trigger');
        var menu = dropdown.querySelector('.nav-dropdown-menu');
        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            var wasOpen = menu.classList.contains('open');
            closeAll();
            if (!wasOpen) {
                menu.classList.add('open');
                trigger.setAttribute('aria-expanded', 'true');
            }
        });
    });

    document.addEventListener('click', closeAll);
})();
</script>
</body>
</html>
