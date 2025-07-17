document.addEventListener('DOMContentLoaded', () => {
    const btnRefresh = document.getElementById('btn-refresh-logs');
    const logsDate = document.getElementById('logs-date');
    const logsLoader = document.getElementById('logs-loader');

    if (btnRefresh) {
        btnRefresh.addEventListener('click', () => {
            btnRefresh.disabled = true;
            logsLoader.style.display = 'block';
            setTimeout(() => {
                const now = new Date();
                logsDate.textContent = now.toLocaleString();
                logsLoader.style.display = 'none';
                btnRefresh.disabled = false;
            }, 5000);
        });
    }
});
