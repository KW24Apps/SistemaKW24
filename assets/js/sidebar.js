document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById("sidebar");
    const toggleBtn = document.getElementById("sidebarToggle");
    let hoverTimeout = null;
    // Inicializa sidebarLocked conforme o estado visual
    let sidebarLocked = sidebar.classList.contains("collapsed");

    toggleBtn.addEventListener("click", function () {
        sidebar.classList.toggle("collapsed");
        sidebarLocked = sidebar.classList.contains("collapsed");
        // Sempre remove o hovered ao clicar, força fechamento imediato
        sidebar.classList.remove("hovered");
    });

    sidebar.addEventListener("mouseenter", function () {
        if (sidebar.classList.contains("collapsed") && !sidebarLocked) {
            if (hoverTimeout) {
                clearTimeout(hoverTimeout);
                hoverTimeout = null;
            }
            hoverTimeout = setTimeout(function () {
                sidebar.classList.add("hovered");
                sidebar.classList.remove("collapsed");
            }, 700); // 700ms delay
        }
    });

    sidebar.addEventListener("mouseleave", function () {
        if (hoverTimeout) {
            clearTimeout(hoverTimeout);
            hoverTimeout = null;
        }
        if (sidebar.classList.contains("hovered")) {
            sidebar.classList.remove("hovered");
            sidebar.classList.add("collapsed");
        }
        sidebarLocked = sidebar.classList.contains("collapsed");
    });
});
