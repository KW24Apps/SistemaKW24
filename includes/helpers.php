<?php
// Verificar se o usuário está autenticado

function checkAuthentication() {
    if (!isset($_SESSION['logviewer_auth']) || $_SESSION['logviewer_auth'] !== true) {
        return false;
    }
    return true;
}


// Redirecionar para login se não autenticado
function requireAuthentication() {
    if (!checkAuthentication()) {
        header('Location: login.php');
        exit;
    }
}
