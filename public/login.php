<?php
session_start();

// Se já estiver logado, redireciona para o index/dashboard
if (isset($_SESSION['logviewer_auth']) && $_SESSION['logviewer_auth'] === true) {
    header('Location: index.php');
    exit;
}

// Credenciais
$usuario_correto = "KW24";
$senha_correta = "159Qwaszx753";
$loginError = false;

// Só seta erro se de fato tentou logar e errou
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['usuario']) && isset($_POST['senha'])) {
        if (strtolower($_POST['usuario']) === strtolower($usuario_correto) && $_POST['senha'] === $senha_correta) {
            $_SESSION['logviewer_auth'] = true;
            $_SESSION['logviewer_user'] = $usuario_correto;
            header('Location: index.php');
            exit;
        } else {
            $loginError = true;
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
    <style>
    .alert-top {
        width: 100vw;
        max-width: 540px;
        margin: 0 auto;
        position: fixed;
        top: 30px;
        left: 0;
        right: 0;
        z-index: 9999;
        background: #e74c3c;
        color: #fff;
        border-radius: 9px;
        padding: 13px 24px;
        text-align: center;
        font-size: 16px;
        box-shadow: 0 4px 14px rgba(0,0,0,0.10);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        animation: fadeIn 0.35s;
    }
    @keyframes fadeIn {
        from { opacity: 0; top: 0px;}
        to { opacity: 1; top: 30px;}
    }
    @media (max-width: 540px) {
        .alert-top { max-width: 96vw; font-size: 15px; }
    }
    </style>
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
            <img src="https://img.kw24.com.br/KW24/02_KW24_HORIZONTAL_NEGATIVO.png" alt="Logo KW24">
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
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var alert = document.getElementById('loginErrorAlert');
        if(alert){
            setTimeout(function(){
                alert.style.display = 'none';
            }, 10000); // 10 segundos
        }
    });
    </script>
</body>
</html>
