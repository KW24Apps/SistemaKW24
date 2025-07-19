document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById("sidebar");
    const toggleBtn = document.getElementById("sidebarToggle");
    let sidebarLocked = false;

    // Clique para minimizar/maximizar e travar
    toggleBtn.addEventListener("click", function () {
        sidebar.classList.toggle("collapsed");
        sidebarLocked = sidebar.classList.contains("collapsed");
    });

    // Hover só funciona se NÃO estiver travada manualmente
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
        // Atualiza o estado de travamento
        sidebarLocked = sidebar.classList.contains("collapsed");
    });
});
