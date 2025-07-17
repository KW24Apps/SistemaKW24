document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('dashboard-refresh');
    const timeBox = document.getElementById('dashboard-time');
    const loader = document.getElementById('dashboard-loader');

    btn.addEventListener('click', function() {
        loader.style.display = 'block';
        btn.disabled = true;

        setTimeout(() => {
            fetch('dashboard.php?justtime=1')
            .then(res => res.text())
            .then(time => {
                timeBox.textContent = time;
                loader.style.display = 'none';
                btn.disabled = false;
            });
        }, 3000);
    });
});
