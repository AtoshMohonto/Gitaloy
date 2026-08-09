(function () {
    'use strict';

    var sidebar = document.getElementById('app-sidebar');
    var backdrop = document.getElementById('sidebar-backdrop');
    var openBtn = document.getElementById('sidebar-open');
    var closeBtn = document.getElementById('sidebar-close');

    if (sidebar && openBtn) {
        function isDesktop() {
            return window.matchMedia('(min-width: 1024px)').matches;
        }

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            if (backdrop) backdrop.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            openBtn.setAttribute('aria-expanded', 'true');
        }

        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            if (backdrop) backdrop.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            openBtn.setAttribute('aria-expanded', 'false');
        }

        openBtn.addEventListener('click', openSidebar);
        if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
        if (backdrop) backdrop.addEventListener('click', closeSidebar);

        sidebar.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                if (!isDesktop()) closeSidebar();
            });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeSidebar();
        });

        window.addEventListener('resize', function () {
            if (isDesktop()) closeSidebar();
        });
    }

    if (window.lucide) {
        lucide.createIcons();
    }

    var toggleBtn = document.getElementById('sidebar-toggle');
    if (toggleBtn) {
        var COLLAPSE_KEY = 'gitaloy:sidebar-collapsed';

        function isCollapsed() {
            return localStorage.getItem(COLLAPSE_KEY) === '1';
        }

        function renderSidebarToggle() {
            var collapsed = isCollapsed();
            document.body.classList.toggle('sidebar-collapsed', collapsed);
            toggleBtn.setAttribute('aria-pressed', collapsed ? 'true' : 'false');
            var ic = toggleBtn.querySelector('i');
            if (ic) {
                ic.setAttribute('data-lucide', collapsed ? 'panel-right' : 'panel-left');
                if (window.lucide) lucide.createIcons();
            }
        }

        renderSidebarToggle();
        toggleBtn.addEventListener('click', function () {
            localStorage.setItem(COLLAPSE_KEY, isCollapsed() ? '0' : '1');
            renderSidebarToggle();
        });
    }

    window.showToast = function (message, type) {
        type = type || 'success';
        var root = document.getElementById('toast-root');
        if (!root) return;
        var tints = {
            success: 'border-emerald-200 bg-emerald-50 text-emerald-900',
            error: 'border-rose-200 bg-rose-50 text-rose-900',
            info: 'border-sky-200 bg-sky-50 text-sky-900'
        };
        var icons = { success: 'check-circle-2', error: 'alert-circle', info: 'info' };
        var el = document.createElement('div');
        el.className = 'fade-in flex items-center gap-2 rounded-xl border px-4 py-3 text-sm font-semibold shadow-lg ' + (tints[type] || tints.success);
        el.innerHTML = (window.lucide
            ? '<i data-lucide="' + icons[type] + '" class="h-4 w-4"></i>'
            : '') + '<span>' + message + '</span>';
        root.appendChild(el);
        if (window.lucide) lucide.createIcons();
        setTimeout(function () {
            el.style.transition = 'opacity .3s ease';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 350);
        }, 3500);
    };
})();
