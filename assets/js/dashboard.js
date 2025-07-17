document.addEventListener('DOMContentLoaded', () => {
    const btnRefresh = document.getElementById('btn-refresh-dashboard');
    const dashboardDate = document.getElementById('dashboard-date');
    const dashboardLoader = document.getElementById('dashboard-loader');

    if (btnRefresh) {
        btnRefresh.addEventListener('click', () => {
            btnRefresh.disabled = true; // Desabilita o botão
            dashboardLoader.style.display = 'block';
            
            setTimeout(() => {
                const now = new Date();
                dashboardDate.textContent = now.toLocaleString();
                dashboardLoader.style.display = 'none';
                btnRefresh.disabled = false; // Habilita novamente
            }, 5000);
        });
    }
});
