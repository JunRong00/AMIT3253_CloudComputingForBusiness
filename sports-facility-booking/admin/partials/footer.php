</main>
<footer class="footer">
<p>&copy; <?= date('Y') ?> Campus Sports Facility Booking &middot; Admin Panel</p>
</footer>
<div id="lightbox" class="lightbox-overlay">
<button type="button" class="lightbox-close" aria-label="Close">&times;</button>
<img id="lightbox-img" src="" alt="">
</div>
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
    var trigger = document.querySelector('.user-menu-trigger');
    var dropdown = document.querySelector('.user-menu-dropdown');
    if (!trigger || !dropdown) return;

    trigger.addEventListener('click', function (e) {
        e.stopPropagation();
        var isOpen = dropdown.classList.toggle('open');
        trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    document.addEventListener('click', function () {
        dropdown.classList.remove('open');
        trigger.setAttribute('aria-expanded', 'false');
    });
})();
</script>
<script>
(function () {
    var lightbox = document.getElementById('lightbox');
    var lightboxImg = document.getElementById('lightbox-img');
    if (!lightbox || !lightboxImg) return;

    document.addEventListener('click', function (e) {
        var img = e.target.closest('.card-thumb, .table-thumb');
        if (img) {
            lightboxImg.src = img.src;
            lightboxImg.alt = img.alt;
            lightbox.classList.add('open');
        }
    });

    lightbox.addEventListener('click', function () {
        lightbox.classList.remove('open');
        lightboxImg.src = '';
    });
})();
</script>
</body>
</html>
