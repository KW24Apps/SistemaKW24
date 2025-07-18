document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById("sidebar");
    const toggleBtn = document.getElementById("sidebarToggle");

    toggleBtn.addEventListener("click", function () {
        sidebar.classList.toggle("collapsed");
    });

    // Opcional: Expandir ao passar o mouse, só se estiver recolhida
    sidebar.addEventListener("mouseenter", function () {
        if (sidebar.classList.contains("collapsed")) {
            sidebar.classList.add("hovered");
            sidebar.classList.remove("collapsed");
        }
    });
    sidebar.addEventListener("mouseleave", function () {
        if (sidebar.classList.contains("hovered")) {
            sidebar.classList.remove("hovered");
            sidebar.classList.add("collapsed");
        }
    });
});
