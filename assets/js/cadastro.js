document.addEventListener('DOMContentLoaded', () => {
    // Refresh para Clientes
    const btnRefreshClientes = document.getElementById('btn-refresh-clientes');
    const clientesDate = document.getElementById('clientes-date');
    const clientesLoader = document.getElementById('clientes-loader');

    if (btnRefreshClientes) {
        btnRefreshClientes.addEventListener('click', () => {
            btnRefreshClientes.disabled = true;
            clientesLoader.style.display = 'block';
            setTimeout(() => {
                const now = new Date();
                clientesDate.textContent = now.toLocaleString();
                clientesLoader.style.display = 'none';
                btnRefreshClientes.disabled = false;
            }, 3000);
        });
    }

    // Refresh para Aplicações
    const btnRefreshAplicacoes = document.getElementById('btn-refresh-aplicacoes');
    const aplicacoesDate = document.getElementById('aplicacoes-date');
    const aplicacoesLoader = document.getElementById('aplicacoes-loader');

    if (btnRefreshAplicacoes) {
        btnRefreshAplicacoes.addEventListener('click', () => {
            btnRefreshAplicacoes.disabled = true;
            aplicacoesLoader.style.display = 'block';
            setTimeout(() => {
                const now = new Date();
                aplicacoesDate.textContent = now.toLocaleString();
                aplicacoesLoader.style.display = 'none';
                btnRefreshAplicacoes.disabled = false;
            }, 3000);
        });
    }

    // Navegação dos submenus (caso não funcione o inline)
    document.querySelectorAll('.cadastro-submenu-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const page = this.dataset.page;
            window.location.href = '/Apps/public/cadastro.php?sub=' + page;
        });
    });
});
