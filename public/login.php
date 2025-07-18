<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Aqui você coloca sua lógica de autenticação real!
    // Exemplo: usuário admin, senha 123
    if ($_POST['usuario'] == 'admin' && $_POST['senha'] == '123') {
        $_SESSION['logviewer_auth'] = true;

        // Redireciona para index.php, levando o parâmetro "page" se ele existir
        $redirect = 'index.php';
        if (isset($_GET['page'])) {
            $redirect .= '?page=' . urlencode($_GET['page']);
        }
        header('Location: ' . $redirect);
        exit;
    } else {
        $erro = "Usuário ou senha inválidos!";
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
    <div class="login-container">
        <div class="login-header">
            <img src="https://img.kw24.com.br/KW24/02_KW24_HORIZONTAL_NEGATIVO.png" alt="Logo KW24">
            <p class="login-subtitle">Acesso restrito ao sistema administrativo KW24</p>
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
