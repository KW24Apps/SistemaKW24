<?php
/**
 * Portal Login — standalone, sem layout principal
 * Chamado pelo portal-router.php com $portal_slug definido
 */
if (!defined('PORTAL_ACCESS')) { http_response_code(403); exit; }

require_once __DIR__ . '/../helpers/Database.php';

$pdo   = Database::getInstance()->getConnection();
$slug  = $portal_slug ?? '';
$error = '';

// Carregar dados do portal
$stmt = $pdo->prepare('SELECT * FROM portais_cliente WHERE slug = ?');
$stmt->execute([$slug]);
$portal = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$portal) {
    http_response_code(404);
    echo '<p style="font-family:sans-serif;color:#ccc;padding:2rem">Portal não encontrado.</p>';
    exit;
}

if (!$portal['ativo']) {
    // Mostrar mensagem de desativado (mesma tela, sem form)
    $error = 'Portal desativado.';
}

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $portal['ativo']) {
    $senha = $_POST['senha'] ?? '';
    if ($senha && password_verify($senha, $portal['senha_hash'])) {
        $_SESSION['portal_mode']        = true;
        $_SESSION['portal_slug']        = $portal['slug'];
        $_SESSION['portal_company_id']  = (int)$portal['company_id'];
        $_SESSION['portal_company_name']= $portal['company_name'];
        header('Location: /portal/' . $portal['slug'] . '/bi');
        exit;
    }
    $error = 'Senha incorreta. Tente novamente.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($portal['company_name']) ?> — Relatório | KW24</title>
    <link rel="stylesheet" href="/assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
    .portal-company-name {
        text-align: center;
        font-size: .875rem;
        font-weight: 600;
        color: rgba(255,255,255,.8);
        margin-bottom: 1.5rem;
        padding: .5rem 1rem;
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 10px;
    }
    </style>
</head>
<body>
    <canvas id="kw24-bg-login"></canvas>

    <?php if ($error): ?>
    <div class="alert-top">
        <i class="fas fa-exclamation-circle"></i>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <div class="login-container">
        <div class="login-header">
            <img src="/assets/img/03_KW24_BRANCO1.png" alt="KW24 - Sistemas Harmônicos">
        </div>

        <div class="portal-company-name">
            <?= htmlspecialchars($portal['company_name']) ?>
        </div>

        <?php if ($portal['ativo']): ?>
        <form method="POST" action="" class="login-form">
            <div class="input-group">
                <input
                    type="password"
                    id="senha"
                    name="senha"
                    placeholder="Senha"
                    required
                    autocomplete="current-password">
                <i class="fas fa-lock input-icon"></i>
                <button type="button" class="toggle-password" aria-label="Mostrar/Ocultar senha">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <button type="submit" class="login-button">
                <span>Acessar relatório</span>
            </button>
        </form>
        <?php endif; ?>

        <div class="login-footer">
            <p>&copy; <?= date('Y') ?> KW24 - Sistemas Harmônicos</p>
        </div>
    </div>

    <script src="/assets/js/login.js"></script>
    <script src="/assets/js/bg-login.js"></script>
</body>
</html>
