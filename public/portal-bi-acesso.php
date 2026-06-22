<?php
/**
 * Portal BI — Acesso externo com filtro por parceiro ou oportunidade.
 * Chamado pelo portal-router.php com $relatorio_slug e $portal_slug definidos.
 * Sem auth_request nginx — o acesso tem autenticação própria (senha ou embed_token).
 */
if (!defined('PORTAL_ACCESS')) { http_response_code(403); exit; }

require_once __DIR__ . '/../helpers/Database.php';

$pdo    = Database::getInstance()->getConnection();
$rSlug  = $relatorio_slug ?? '';
$pSlug  = $portal_slug    ?? '';
$error  = '';

// Carrega o portal
$stmt = $pdo->prepare(
    'SELECT * FROM portais_bi WHERE relatorio_slug = ? AND slug = ? LIMIT 1'
);
$stmt->execute([$rSlug, $pSlug]);
$portal = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$portal) {
    http_response_code(404);
    echo '<p style="font-family:sans-serif;color:#ccc;padding:2rem">Portal não encontrado.</p>';
    exit;
}

if (!$portal['ativo']) {
    $error = 'Este portal está desativado.';
}

$reportUrl = '/relatorios-bi/' . $rSlug . '/';

// ── Sessão de portal BI já existe e é válida para este portal ──────────────
if (isset($_SESSION['portal_bi'])
    && ($_SESSION['portal_bi']['portal_id']       ?? 0)  === (int)$portal['id']
    && ($_SESSION['portal_bi']['relatorio_slug']  ?? '') === $rSlug
) {
    $pb      = $_SESSION['portal_bi'];
    $expires = $pb['expires'] ?? 0;
    if ($expires === 0 || time() <= $expires) {
        header('Location: ' . $reportUrl);
        exit;
    }
    // Sessão expirada — limpa e continua para o formulário
    unset($_SESSION['portal_bi']);
}

// ── Acesso via embed_token (?embed=TOKEN) ──────────────────────────────────
$embedParam = trim($_GET['embed'] ?? '');
if ($embedParam && $portal['ativo']) {
    if (hash_equals($portal['embed_token'], $embedParam)) {
        // Embed não expira (8h de sessão PHP, renovada a cada visita)
        $_SESSION['portal_bi'] = [
            'portal_id'      => (int)$portal['id'],
            'relatorio_slug' => $rSlug,
            'filter_type'    => $portal['filter_type'],
            'filter_values'  => json_decode($portal['filter_values'], true) ?? [],
            'expires'        => 0,   // 0 = sem expiração de sessão para embed
        ];
        header('Location: ' . $reportUrl);
        exit;
    }
    $error = 'Token de incorporação inválido.';
}

// ── POST: validação de senha ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $portal['ativo'] && !$error) {
    $senha = $_POST['senha'] ?? '';
    if ($senha && password_verify($senha, $portal['senha_hash'])) {
        $_SESSION['portal_bi'] = [
            'portal_id'      => (int)$portal['id'],
            'relatorio_slug' => $rSlug,
            'filter_type'    => $portal['filter_type'],
            'filter_values'  => json_decode($portal['filter_values'], true) ?? [],
            'expires'        => time() + 7200,  // 2 horas
        ];
        header('Location: ' . $reportUrl);
        exit;
    }
    $error = 'Senha incorreta. Tente novamente.';
}

// Nome amigável do portal (fallback para slug)
$nomeExibido = $portal['nome'] ?: $portal['slug'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($nomeExibido) ?> — Relatório | KW24</title>
    <link rel="stylesheet" href="/assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
    .portal-name-badge {
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

        <div class="portal-name-badge">
            <?= htmlspecialchars($nomeExibido) ?>
        </div>

        <?php if ($portal['ativo'] && !$embedParam): ?>
        <form method="POST" action="" class="login-form">
            <div class="input-group">
                <input
                    type="password"
                    id="senha"
                    name="senha"
                    placeholder="Senha de acesso"
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
