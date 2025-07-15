<?php
session_start();

// Se já estiver logado, redirecionar para o dashboard
if (isset($_SESSION['logviewer_auth']) && $_SESSION['logviewer_auth'] === true) {
    header('Location: index.php');
    exit;
}

// Credenciais (do antigo LOGs.php)
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
    <link rel="stylesheet" href="../assets/css/login.css">
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
        </div>
        <h1>Acessar Sistema</h1>
        <form method="post">
            <div class="input-group">
                <span class="input-icon">👤</span>
                <input type="text" name="usuario" placeholder="Usuário" required>
            </div>
            <div class="input-group">
                <span class="input-icon">🔒</span>
                <input type="password" name="senha" placeholder="Senha" required>
            </div>
            <button type="submit">LOGIN</button>
        </form>
    </div>
</body>
</html>
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            color: #333;
            overflow: hidden;
        }
        
        .login-container { 
            width: 360px;
            background: rgba(255, 255, 255, 0.75);
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .alert {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 12px 24px;
            background: rgba(231, 76, 60, 0.9);
            color: white;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 1000;
            font-weight: 500;
            display: flex;
            align-items: center;
            animation: fadeIn 0.3s ease-out, fadeOut 0.5s ease-in 9.5s forwards;
        }
        
        .alert i {
            margin-right: 8px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translate(-50%, -20px); }
            to { opacity: 1; transform: translate(-50%, 0); }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; transform: translate(-50%, 0); }
            to { opacity: 0; transform: translate(-50%, -20px); }
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header img {
            max-width: 140px;
            margin-bottom: 20px;
        }
        
        h1 { 
            font-family: 'Rubik', sans-serif;
            margin-top: 0;
            margin-bottom: 30px;
            color: var(--primary-dark); 
            font-weight: 600;
            font-size: 1.6rem;
            text-align: center;
        }
        
        .input-group {
            position: relative;
            margin-bottom: 20px;
        }
        
        .input-icon {
            position: absolute;
            left: 12px;
            top: 12px;
            color: #777;
            width: 20px;
            text-align: center;
        }
        
        input[type="text"],
        input[type="password"] { 
            width: 100%; 
            padding: 12px 12px 12px 40px; 
            box-sizing: border-box; 
            border: 1px solid #ddd; 
            border-radius: 6px;
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            background-color: rgba(255, 255, 255, 0.8);
        }
        
        button { 
            background: var(--primary-dark); 
            color: white; 
            padding: 12px 18px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            width: 100%;
            font-family: 'Inter', sans-serif;
            font-size: 16px;
            font-weight: 500;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        button:hover { 
            background: var(--primary); 
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            font-size: 14px;
            color: #555;
        }
        
        .remember-me input {
            margin-right: 8px;
        }
    </style>
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
        </div>
        <h1>Sistema Administrativo</h1>
        <form method="post">
            <div class="input-group">
                <span class="input-icon">👤</span>
                <input type="text" name="usuario" placeholder="Email ID" required>
            </div>
            <div class="input-group">
                <span class="input-icon">🔒</span>
                <input type="password" name="senha" placeholder="Password" required>
            </div>
            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Remember me</label>
            </div>
            <button type="submit">LOGIN</button>
        </form>
    </div>
</body>
</html>
