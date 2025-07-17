document.addEventListener('DOMContentLoaded', () => {
    const btnRefresh = document.getElementById('btn-refresh-clientes');
    const clientesDate = document.getElementById('clientes-date');
    const clientesLoader = document.getElementById('clientes-loader');

    if (btnRefresh) {
        btnRefresh.addEventListener('click', () => {
            btnRefresh.disabled = true;
            clientesLoader.style.display = 'block';
            setTimeout(() => {
                const now = new Date();
                clientesDate.textContent = now.toLocaleString();
                clientesLoader.style.display = 'none';
                btnRefresh.disabled = false;
            }, 5000);
        });
    }
});
