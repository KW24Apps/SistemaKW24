document.addEventListener('DOMContentLoaded', () => {
    const btnRefresh = document.getElementById('btn-refresh-aplicacoes');
    const aplicacoesDate = document.getElementById('aplicacoes-date');
    const aplicacoesLoader = document.getElementById('aplicacoes-loader');

    if (btnRefresh) {
        btnRefresh.addEventListener('click', () => {
            btnRefresh.disabled = true;
            aplicacoesLoader.style.display = 'block';
            setTimeout(() => {
                const now = new Date();
                aplicacoesDate.textContent = now.toLocaleString();
                aplicacoesLoader.style.display = 'none';
                btnRefresh.disabled = false;
            }, 5000);
        });
    }
});
