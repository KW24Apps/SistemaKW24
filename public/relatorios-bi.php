<?php
/**
 * Relatórios BI — página com DUAS ABAS:
 *   Aba "Relatórios": cards dos relatórios do grupo (?grupo=nimbus|contabilidade|...)
 *   Aba "Portais":    conteúdo completo de portais-bi.php (só se pode_portal / admin_interno)
 * admin_interno vê todos do grupo; demais, apenas os de usuario_relatorio_acesso.
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

// Aba "Portais" só aparece se o usuário pode gerar portal (ou é admin_interno).
$temPortal = ($perfil === 'admin_interno')
    || !empty(relatoriosPortalSlugsDoUsuario($db, (int)($user_data['id'] ?? 0)));

$tituloGrupo = $grupo !== '' ? ' — ' . htmlspecialchars(ucfirst($grupo)) : '';
?>
<style>
/* Abas da página Relatórios BI */
.rtbi-tabs { display:flex; gap:.25rem; border-bottom:1px solid #e2e8f0; margin-bottom:1.25rem; }
.rtbi-tab { background:none; border:none; padding:.6rem 1.1rem; font-size:.85rem; font-weight:600;
    color:#718096; cursor:pointer; border-bottom:2px solid transparent; margin-bottom:-1px; display:flex; align-items:center; gap:.45rem; }
.rtbi-tab:hover { color:#2d3748; }
.rtbi-tab.active { color:#0DC2FF; border-bottom-color:#0DC2FF; }
</style>

<div class="page-header">
    <h1 class="page-title"><i class="fas fa-chart-bar"></i> Relatórios BI<?= $tituloGrupo ?></h1>
</div>

<div class="rtbi-tabs">
    <button type="button" class="rtbi-tab active" data-pane="rel" onclick="rtbiTab(this)">
        <i class="fas fa-chart-pie"></i> Relatórios
    </button>
    <?php if ($temPortal): ?>
    <button type="button" class="rtbi-tab" data-pane="portais" onclick="rtbiTab(this)">
        <i class="fas fa-globe"></i> Portais
    </button>
    <?php endif; ?>
</div>

<!-- Aba 1: Relatórios (cards) -->
<div id="rtbi-pane-rel" class="rtbi-pane">
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
</div>

<!-- Aba 2: Portais (conteúdo completo de portais-bi.php) -->
<?php if ($temPortal): ?>
<div id="rtbi-pane-portais" class="rtbi-pane" style="display:none">
    <?php include __DIR__ . '/portais-bi.php'; ?>
</div>
<?php endif; ?>

<script>
// Alterna entre as abas "Relatórios" e "Portais" sem sair da página.
function rtbiTab(btn) {
    var pane = btn.getAttribute('data-pane');
    document.querySelectorAll('.rtbi-pane').forEach(function (p) { p.style.display = 'none'; });
    document.querySelectorAll('.rtbi-tab').forEach(function (t) { t.classList.remove('active'); });
    var el = document.getElementById('rtbi-pane-' + pane);
    if (el) el.style.display = '';
    btn.classList.add('active');
}
</script>
