document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById("sidebar");
    const toggleBtn = document.getElementById("sidebarToggle");
    let sidebarLocked = false;

    toggleBtn.addEventListener("click", function () {
        sidebar.classList.toggle("collapsed");
        sidebarLocked = sidebar.classList.contains("collapsed");
        // Sempre remove o hovered ao clicar, força fechamento imediato
        sidebar.classList.remove("hovered");
    });

    sidebar.addEventListener("mouseenter", function () {
        if (sidebar.classList.contains("collapsed") && !sidebarLocked) {
            sidebar.classList.add("hovered");
            sidebar.classList.remove("collapsed");
        }
    });

    sidebar.addEventListener("mouseleave", function () {
        if (sidebar.classList.contains("hovered")) {
            sidebar.classList.remove("hovered");
            sidebar.classList.add("collapsed");
        }
        sidebarLocked = sidebar.classList.contains("collapsed");
    });
});
