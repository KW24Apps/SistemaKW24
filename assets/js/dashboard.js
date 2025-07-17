document.addEventListener('DOMContentLoaded', () => {
    const btnRefresh = document.getElementById('btn-refresh-dashboard');
    const dashboardDate = document.getElementById('dashboard-date');
    const dashboardLoader = document.getElementById('dashboard-loader');

    if (btnRefresh) {
        btnRefresh.addEventListener('click', () => {
            // Exibir o loader enquanto "processa"
            dashboardLoader.style.display = 'block';
            
            // Simula o tempo de processamento (5 segundos)
            setTimeout(() => {
                // Atualizar a data e hora
                const now = new Date();
                dashboardDate.textContent = now.toLocaleString();

                // Esconder o loader após o tempo de atualização
                dashboardLoader.style.display = 'none';
            }, 5000); // 5 segundos de simulação
        });
    }
});
