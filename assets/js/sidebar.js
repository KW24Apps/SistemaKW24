document.addEventListener("DOMContentLoaded", function () {

    const sidebar = document.getElementById("sidebar");
    const toggleBtn = document.getElementById("sidebarToggle");
    let hoverTimeout = null;

    // Ao carregar, aplica o estado salvo
    const savedState = localStorage.getItem('sidebarState');
    if (savedState === 'collapsed') {
        sidebar.classList.add('collapsed');
    } else {
        sidebar.classList.remove('collapsed');
    }

    toggleBtn.addEventListener("click", function () {
        const isCollapsed = sidebar.classList.toggle("collapsed");
        sidebar.classList.remove("hovered");
        // Salva o estado no localStorage
        localStorage.setItem('sidebarState', isCollapsed ? 'collapsed' : 'expanded');
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


// Aplica a classe collapsed no sidebar antes do DOM aparecer, evitando flicker
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