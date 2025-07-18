document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById("sidebar");
    const toggleBtn = document.getElementById("sidebarToggle");

    toggleBtn.addEventListener("click", function () {
        sidebar.classList.toggle("collapsed");
    });

    // Opcional: Expandir ao passar o mouse, só se estiver recolhida
    let sidebarLocked = false;
    toggleBtn.addEventListener("click", function () {
        sidebar.classList.toggle("collapsed");
        sidebarLocked = sidebar.classList.contains("collapsed");
    });

    sidebar.addEventListener("mouseenter", function () {
        // Só expande se NÃO estiver travada (minimizada manualmente)
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
        // Se estava travada (clicada para minimizar), mantém travada
        sidebarLocked = sidebar.classList.contains("collapsed");
    });
});
