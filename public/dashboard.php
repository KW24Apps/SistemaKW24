<?php
// public/dashboard.php
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="dashboard-container">
            <h1 id="dashboard-title">Dashboard</h1>
            <div id="dashboard-date" class="dashboard-date"></div>
            <button id="btn-refresh-dashboard">Atualizar</button>
            <div id="dashboard-loader" class="dashboard-loader" style="display:none"></div>
        </div>
    </div>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
