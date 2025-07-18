<?php
session_start();

$loginError = false;
$usuarioDigitado = '';
if (isset($_SESSION['login_erro']) && $_SESSION['login_erro'] === true) {
    $loginError = true;
    unset($_SESSION['login_erro']);
}
if (isset($_SESSION['usuario_digitado'])) {
    $usuarioDigitado = $_SESSION['usuario_digitado'];
    unset($_SESSION['usuario_digitado']);
}

// Se já estiver logado, redireciona para o index/dashboard
if (isset($_SESSION['logviewer_auth']) && $_SESSION['logviewer_auth'] === true) {
    header('Location: index.php');
    exit;
}

// Credenciais
$usuario_correto = "KW24";
$senha_correta = "159Qwaszx753";

// Só seta erro se de fato tentou logar e errou
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['usuario']) && !empty($_POST['senha'])) {
        if (
            strtolower($_POST['usuario']) === strtolower($usuario_correto) &&
            $_POST['senha'] === $senha_correta
        ) {
            $_SESSION['logviewer_auth'] = true;
            $_SESSION['logviewer_user'] = $usuario_correto;
            header('Location: index.php');
            exit;
        } else {
            // Salva erro na session e redireciona para GET
            $_SESSION['login_erro'] = true;
            $_SESSION['usuario_digitado'] = $_POST['usuario'];
            header('Location: login.php');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login KW24</title>
    <link rel="stylesheet" href="/Apps/assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <?php if ($loginError): ?>
        <div class="alert-top" id="loginErrorAlert">
            <i class="fa fa-exclamation-triangle"></i>
            Usuário ou senha inválidos!
        </div>
    <?php endif; ?>

    <div class="login-container">
        <div class="login-header">
            <img src="https://img.kw24.com.br/KW24/03_KW24_BRANCO.png" alt="Logo KW24">
            <p class="login-subtitle">Acesso restrito ao sistema administrativo</p>
        </div>
        <form method="post" action="login.php" autocomplete="off">
            <div class="input-group">
                <span class="input-icon"><i class="fa fa-user"></i></span>
                <input type="text" name="usuario" id="usuario" placeholder="Usuário" required>
            </div>
            <div class="input-group" style="position:relative;">
                <span class="input-icon"><i class="fa fa-lock"></i></span>
                <input type="password" name="senha" id="senha" placeholder="Senha" required>
                <span id="toggleSenha" style="position:absolute; right:16px; top:12px; cursor:pointer;">
                    <i class="fa fa-eye"></i>
                </span>
            </div>
            <div class="remember-me">
                <input type="checkbox" id="lembrar" name="lembrar">
                <label for="lembrar">Lembrar-me</label>
            </div>
            <button type="submit">Entrar</button>
        </form>
    </div>
    <script src="/Apps/assets/js/login.js"></script>
</body>
</html>
