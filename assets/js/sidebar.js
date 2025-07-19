// Aplica classe 'collapsed' antes do DOM aparecer (evita flicker)
(function() {
    try {
        var state = localStorage.getItem('sidebarState');
        if (state === 'collapsed') {
            var sidebar = document.getElementById('sidebar');
            if (sidebar) sidebar.classList.add('collapsed');
            else document.addEventListener('DOMContentLoaded', function() {
                var sidebar = document.getElementById('sidebar');
                if (sidebar) sidebar.classList.add('collapsed');
            });
        }
    } catch(e){}
})();

document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById("sidebar");
    const toggleBtn = document.getElementById("sidebarToggle");
    let hoverTimeout = null;

    // Funções auxiliares
    function setSidebarState(collapsed) {
        if (collapsed) sidebar.classList.add('collapsed');
        else sidebar.classList.remove('collapsed');
        localStorage.setItem('sidebarState', collapsed ? 'collapsed' : 'expanded');
    }

    function addHovered() {
        sidebar.classList.add("hovered");
    }

    function removeHovered() {
        sidebar.classList.remove("hovered");
    }

    // Inicializa estado salvo (só se faltar pelo anti-flicker)
    if (!sidebar.classList.contains('collapsed') && localStorage.getItem('sidebarState') === 'collapsed') {
        sidebar.classList.add('collapsed');
    }

    toggleBtn.addEventListener("click", function () {
        const isCollapsed = sidebar.classList.toggle("collapsed");
        removeHovered();
        localStorage.setItem('sidebarState', isCollapsed ? 'collapsed' : 'expanded');
        // Log opcional
        // console.log('[Sidebar] Toggle click. Classes:', sidebar.className);
    });

    sidebar.addEventListener("mouseenter", function () {
        if (sidebar.classList.contains("collapsed")) {
            if (hoverTimeout) clearTimeout(hoverTimeout);
            hoverTimeout = setTimeout(addHovered, 700);
        }
    });

    sidebar.addEventListener("mouseleave", function () {
        if (hoverTimeout) clearTimeout(hoverTimeout);
        removeHovered();
    });
});
