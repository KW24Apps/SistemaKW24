<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?= isset($pageTitle) ? $pageTitle : 'Sistema Administrativo KW24' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/Apps/assets/css/main.css">
    <link rel="stylesheet" href="/Apps/assets/css/sidebar-fixes.css">
    <?= isset($additionalCSS) ? $additionalCSS : '' ?>
</head>
<body class="<?= isset($sidebarState) && $sidebarState === 'collapsed' ? 'sidebar-collapsed' : '' ?>">
    <div class="container">
        <?php include __DIR__ . '/sidebar.php'; ?>

        <div class="main-content">
            <?php if (isset($pageTitle) && $pageTitle !== 'Dashboard - Sistema KW24'): ?>
                <div class="page-header">
                    <h1><?= $pageTitle ?></h1>
                    <?php if (isset($pageActions)): ?>
                        <div class="page-actions"><?= $pageActions ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="content-area">
                <?= isset($content) ? $content : '' ?>
            </div>

            <div class="footer">
                <p><?= date('Y') ?> - KW24</p>
            </div>
        </div>
    </div>

    <script src="/Apps/assets/js/main.js"></script>
    <?= isset($additionalJS) ? $additionalJS : '' ?>
</body>
</html>
