document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById("sidebar");
    const toggleBtn = document.getElementById("sidebarToggle");
    let hoverTimeout = null;
    // sidebarLocked começa como false, só fica true após clique
    let sidebarLocked = false;

    toggleBtn.addEventListener("click", function () {
        sidebar.classList.toggle("collapsed");
        // Se ficou minimizada após o clique, trava; se expandiu, destrava
        sidebarLocked = sidebar.classList.contains("collapsed");
        // Sempre remove o hovered ao clicar, força fechamento imediato
        sidebar.classList.remove("hovered");
    });

    sidebar.addEventListener("mouseenter", function () {
        // Só expande se estiver minimizada E não estiver travada
        if (sidebar.classList.contains("collapsed") && !sidebarLocked) {
            if (hoverTimeout) {
                clearTimeout(hoverTimeout);
                hoverTimeout = null;
            }
            hoverTimeout = setTimeout(function () {
                sidebar.classList.add("hovered"); // Mantém .collapsed e adiciona .hovered
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
        }
        // NÃO atualiza sidebarLocked aqui!
    });
});
