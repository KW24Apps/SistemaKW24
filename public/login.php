<?php
session_start();

// Se já estiver logado, redirecionar para o dashboard
if (isset($_SESSION['logviewer_auth']) && $_SESSION['logviewer_auth'] === true) {
    header('Location: index.php');
    exit;
}

// Credenciais
$usuario_correto = "KW24";
$senha_correta = "159Qwaszx753";
$loginError = false;

// Processar tentativa de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['usuario']) && isset($_POST['senha'])) {
        if (strtolower($_POST['usuario']) === strtolower($usuario_correto) && $_POST['senha'] === $senha_correta) {
            $_SESSION['logviewer_auth'] = true;
            $_SESSION['logviewer_user'] = $usuario_correto;
            header('Location: index.php');
            exit;
        }
    }
    $loginError = true;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Sistema KW24</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/login.css">
</head>
<body>
    <?php if ($loginError): ?>
    <div class="alert">
        <i class="fas fa-exclamation-circle"></i> Usuário ou senha inválidos
    </div>
    <?php endif; ?>
    <div class="login-container">
        <div class="login-header">
            <img src="https://gabriel.kw24.com.br/06_KW24_TAGLINE_%20POSITIVO.png" alt="KW24 Logo">
            <h1>Log Viewer</h1>
        </div>
        <form method="post">
            <div class="input-group">
                <span class="input-icon"><i class="fa fa-user"></i></span>
                <input type="text" name="usuario" placeholder="Usuário" required>
            </div>
            <div class="input-group">
                <span class="input-icon"><i class="fa fa-lock"></i></span>
                <input type="password" name="senha" placeholder="Senha" required>
            </div>
            <button type="submit">LOGIN</button>
        </form>
    </div>
</body>
</html>
