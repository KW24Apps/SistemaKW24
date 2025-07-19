document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById("sidebar");
    const toggleBtn = document.getElementById("sidebarToggle");
    let hoverTimeout = null;

    toggleBtn.addEventListener("click", function () {
        sidebar.classList.toggle("collapsed");
        sidebar.classList.remove("hovered");
        console.log('[Sidebar] Toggle click. Classes:', sidebar.className);
    });

    sidebar.addEventListener("mouseenter", function () {
        console.log('[Sidebar] mouseenter | Classes:', sidebar.className);
        if (sidebar.classList.contains("collapsed")) {
            if (hoverTimeout) {
                clearTimeout(hoverTimeout);
                hoverTimeout = null;
            }
            hoverTimeout = setTimeout(function () {
                sidebar.classList.add("hovered");
                console.log('[Sidebar] hovered ADICIONADO | Classes:', sidebar.className);
            }, 700);
        } else {
            console.log('[Sidebar] Não expande: collapsed?', sidebar.classList.contains("collapsed"));
        }
    });

    sidebar.addEventListener("mouseleave", function () {
        console.log('[Sidebar] mouseleave | Classes:', sidebar.className);
        if (hoverTimeout) {
            clearTimeout(hoverTimeout);
            hoverTimeout = null;
            console.log('[Sidebar] hoverTimeout LIMPO');
        }
        if (sidebar.classList.contains("hovered")) {
            sidebar.classList.remove("hovered");
            console.log('[Sidebar] hovered REMOVIDO | Classes:', sidebar.className);
        }
    });
});
