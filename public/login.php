<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login KW24</title>
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="img/logo-kw24.png" alt="Logo KW24">
        </div>
        <h1>Bem-vindo!</h1>
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
        <div style="text-align:center; margin-top:16px;">
            <a href="#" style="color: #086B8D; font-size:14px;">Esqueceu sua senha?</a>
        </div>
    </div>
    <script src="login.js"></script>
</body>
</html>
