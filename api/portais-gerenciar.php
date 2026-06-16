<?php
session_start();
require_once __DIR__ . '/../services/AuthenticationService.php';
require_once __DIR__ . '/../helpers/Database.php';

header('Content-Type: application/json');

$auth = new AuthenticationService();
if (!$auth->validateSession()) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autenticado']);
    exit;
}
$user = $auth->getCurrentUser();
if (($user['perfil'] ?? '') !== 'admin_interno') {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso negado']);
    exit;
}

$pdo    = Database::getInstance()->getConnection();
$action = trim($_POST['action'] ?? $_GET['action'] ?? '');

try {
    switch ($action) {

        case 'listar':
            $rows = $pdo->query(
                'SELECT id, company_id, company_name, slug, embed_token, ativo,
                        to_char(created_at, \'DD/MM/YYYY\') AS created_fmt
                 FROM portais_cliente ORDER BY company_name'
            )->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$r) {
                $r['ativo'] = (bool)$r['ativo'];
            }
            echo json_encode(['sucesso' => true, 'portais' => $rows]);
            break;

        case 'criar':
            $companyId   = (int)($_POST['company_id']   ?? 0);
            $companyName = trim($_POST['company_name']   ?? '');
            $slug        = trim($_POST['slug']           ?? '');
            $senha       = trim($_POST['senha']          ?? '');

            if (!$companyId || !$companyName || !$slug || !$senha) {
                echo json_encode(['erro' => 'Campos obrigatórios não preenchidos']); exit;
            }
            if (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
                echo json_encode(['erro' => 'Slug inválido — use apenas letras minúsculas, números e hifens']); exit;
            }

            $senhaHash  = password_hash($senha, PASSWORD_BCRYPT);
            $embedToken = bin2hex(random_bytes(32));

            $stmt = $pdo->prepare(
                'INSERT INTO portais_cliente (company_id, company_name, slug, senha_hash, embed_token)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$companyId, $companyName, $slug, $senhaHash, $embedToken]);
            $id = (int)$pdo->lastInsertId();

            echo json_encode(['sucesso' => true, 'id' => $id, 'slug' => $slug, 'embed_token' => $embedToken]);
            break;

        case 'editar':
            $id          = (int)($_POST['id']           ?? 0);
            $companyName = trim($_POST['company_name']  ?? '');
            $slug        = trim($_POST['slug']          ?? '');
            $novaSenha   = trim($_POST['nova_senha']    ?? '');

            if (!$id || !$companyName || !$slug) {
                echo json_encode(['erro' => 'Campos obrigatórios não preenchidos']); exit;
            }
            if (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
                echo json_encode(['erro' => 'Slug inválido']); exit;
            }

            if ($novaSenha) {
                $senhaHash = password_hash($novaSenha, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare(
                    'UPDATE portais_cliente SET company_name=?, slug=?, senha_hash=?, updated_at=NOW() WHERE id=?'
                );
                $stmt->execute([$companyName, $slug, $senhaHash, $id]);
            } else {
                $stmt = $pdo->prepare(
                    'UPDATE portais_cliente SET company_name=?, slug=?, updated_at=NOW() WHERE id=?'
                );
                $stmt->execute([$companyName, $slug, $id]);
            }
            echo json_encode(['sucesso' => true]);
            break;

        case 'toggle':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['erro' => 'ID inválido']); exit; }

            $pdo->prepare('UPDATE portais_cliente SET ativo = NOT ativo, updated_at=NOW() WHERE id=?')
                ->execute([$id]);

            $row = $pdo->prepare('SELECT ativo FROM portais_cliente WHERE id=?');
            $row->execute([$id]);
            $novoAtivo = (bool)$row->fetchColumn();

            echo json_encode(['sucesso' => true, 'ativo' => $novoAtivo]);
            break;

        case 'excluir':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['erro' => 'ID inválido']); exit; }

            $pdo->prepare('DELETE FROM portais_cliente WHERE id=?')->execute([$id]);
            echo json_encode(['sucesso' => true]);
            break;

        default:
            echo json_encode(['erro' => 'Ação inválida']);
    }

} catch (PDOException $e) {
    $msg = $e->getMessage();
    if (stripos($msg, 'unique') !== false || stripos($msg, 'duplicate') !== false) {
        echo json_encode(['erro' => 'Slug ou token já existe — tente outro slug']);
    } else {
        error_log('[portais-gerenciar] ' . $msg);
        echo json_encode(['erro' => 'Erro no banco de dados']);
    }
} catch (Exception $e) {
    error_log('[portais-gerenciar] ' . $e->getMessage());
    echo json_encode(['erro' => 'Erro interno']);
}
