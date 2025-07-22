// Função para atualizar apenas a área principal (.main-content) e o submenu da topbar via AJAX
function loadMainContent(url, submenuUrl = null) {
    // Atualiza a área principal
    fetch(url)
        .then(response => response.text())
        .then(html => {
            document.querySelector('.main-content').innerHTML = html;
        });

    // Se for passado um submenuUrl, atualiza o submenu da topbar
    if (submenuUrl) {
        fetch(submenuUrl)
            .then(response => response.text())
            .then(html => {
                const submenu = document.querySelector('.topbar-submenu');
                if (submenu) submenu.innerHTML = html;
            });
    }
}

// Submenu de logs: troca de abas via AJAX
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('logs-submenu-btn')) {
        var sub = e.target.getAttribute('data-page');
        // Atualiza conteúdo principal
        loadMainContent('/Apps/public/ajax/ajax-content.php?page=logs&sub=' + sub);
        // Atualiza visual do submenu
        document.querySelectorAll('.logs-submenu-btn').forEach(btn => btn.classList.remove('active'));
        e.target.classList.add('active');
    }
});
