<?php
session_start();
require_once __DIR__ . '/../services/AuthenticationService.php';
require_once __DIR__ . '/../helpers/Database.php';
header('Content-Type: application/json');
$auth = new AuthenticationService();
if (!$auth->validateSession()) { http_response_code(401); echo json_encode([]); exit; }
$db = Database::getInstance();
echo json_encode($db->fetchAll("SELECT id, slug, nome, descricao FROM aplicacoes ORDER BY nome ASC"));
