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

// Exemplo de uso:
// loadMainContent('/Apps/public/ajax/conteudo.php', '/Apps/public/ajax/submenu.php');
// Ou só atualizar o conteúdo principal:
// loadMainContent('/Apps/public/ajax/conteudo.php');
