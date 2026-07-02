<?php
/**
 * Página de Relatórios BI por grupo (?grupo=nimbus|contabilidade|...).
 * admin_interno vê todos do grupo; demais, apenas os de usuario_relatorio_acesso.
 * Renderiza cards com link para cada relatório (/relatorios-bi/<slug>/).
 */
if (!defined('SYSTEM_ACCESS') && !isset($user_data)) {
    header('Location: /public/login.php'); exit;
}
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Acesso.php';

$db     = Database::getInstance();
$grupo  = trim($_GET['grupo'] ?? '');
$perfil = $user_data['perfil'] ?? '';

if ($perfil === 'admin_interno') {
    $sql = "SELECT slug, nome_amigavel, grupo FROM relatorios_bi WHERE visivel = TRUE";
    $params = [];
    if ($grupo !== '') { $sql .= " AND grupo = :g"; $params['g'] = $grupo; }
    $sql .= " ORDER BY grupo, ordem";
    $rels = $db->fetchAll($sql, $params);
} else {
    $sql = "SELECT rb.slug, rb.nome_amigavel, rb.grupo
              FROM usuario_relatorio_acesso ura
              JOIN relatorios_bi rb ON rb.id = ura.relatorio_id
             WHERE ura.usuario_id = :id AND rb.visivel = TRUE";
    $params = ['id' => (int)($user_data['id'] ?? 0)];
    if ($grupo !== '') { $sql .= " AND rb.grupo = :g"; $params['g'] = $grupo; }
    $sql .= " ORDER BY rb.grupo, rb.ordem";
    $rels = $db->fetchAll($sql, $params);
}

$tituloGrupo = $grupo !== '' ? ' — ' . htmlspecialchars(ucfirst($grupo)) : '';
?>
<div class="page-header">
    <h1 class="page-title"><i class="fas fa-chart-bar"></i> Relatórios BI<?= $tituloGrupo ?></h1>
</div>

<?php if (empty($rels)): ?>
    <div class="empty-state" style="text-align:center;padding:3rem 1rem;color:#a0aec0">
        <i class="fas fa-chart-bar" style="font-size:2rem;display:block;margin-bottom:.75rem;opacity:.4"></i>
        <p>Nenhum relatório disponível<?= $grupo !== '' ? ' neste grupo' : '' ?>.</p>
    </div>
<?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1rem;padding:.25rem">
        <?php foreach ($rels as $r): ?>
        <a href="/relatorios-bi/<?= htmlspecialchars($r['slug']) ?>/" target="_blank" rel="noopener"
           style="display:block;text-decoration:none;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1.25rem;transition:box-shadow .15s,transform .15s"
           onmouseover="this.style.boxShadow='0 8px 24px rgba(13,194,255,.15)';this.style.transform='translateY(-2px)'"
           onmouseout="this.style.boxShadow='none';this.style.transform='none'">
            <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.6rem">
                <div style="width:42px;height:42px;border-radius:10px;background:rgba(13,194,255,.12);display:flex;align-items:center;justify-content:center;color:#0DC2FF;font-size:1.1rem">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <div style="font-size:.6rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#a0aec0">
                    <?= htmlspecialchars(ucfirst($r['grupo'] ?: 'Geral')) ?>
                </div>
            </div>
            <div style="font-size:.95rem;font-weight:700;color:#1a202c;margin-bottom:.35rem">
                <?= htmlspecialchars($r['nome_amigavel']) ?>
            </div>
            <div style="font-size:.78rem;color:#0DC2FF;font-weight:600">
                Abrir relatório <i class="fas fa-arrow-right" style="font-size:.7rem"></i>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
