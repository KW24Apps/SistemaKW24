<?php
if (!defined('SYSTEM_ACCESS') && !isset($user_data)) {
    header('Location: /public/login.php'); exit;
}
require_once __DIR__ . '/../helpers/Database.php';

try {
    $db = Database::getInstance();
    $totalClientes  = $db->fetchOne("SELECT COUNT(*) AS n FROM clientes")['n'] ?? 0;
    $totalAppsAtivas= $db->fetchOne("SELECT COUNT(*) AS n FROM cliente_aplicacoes WHERE ativo = TRUE")['n'] ?? 0;
    $totalBD        = $db->fetchOne("SELECT COUNT(*) AS n FROM cliente_aplicacoes ca JOIN aplicacoes a ON a.id=ca.aplicacao_id WHERE a.slug='BancoDados' AND ca.ativo=TRUE")['n'] ?? 0;
    $ultimaSync     = $db->fetchOne("SELECT MAX(executado_em) AS dt FROM sync_historico")['dt'] ?? null;
} catch (Exception $e) {
    $totalClientes = $totalAppsAtivas = $totalBD = 0;
    $ultimaSync = null;
}

$ultimaSyncFmt = $ultimaSync ? date('d/m/Y H:i', strtotime($ultimaSync)) : '—';
?>

<div class="page-header" style="margin-bottom:1.5rem">
    <div>
        <h1 class="page-title" style="margin-bottom:.15rem"><i class="fas fa-home"></i> Dashboard</h1>
        <p style="font-size:.82rem;color:#a0aec0;margin:0">Bem-vindo, <?= htmlspecialchars($user_data['nome']) ?></p>
    </div>
</div>

<!-- KPIs -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;margin-bottom:1.75rem">
    <?php
    $kpis = [
        ['fas fa-users',       '#0DC2FF', '#e0f7ff', 'Clientes',            $totalClientes,   null],
        ['fas fa-th',          '#086B8D', '#e6f2f7', 'Apps Ativas',         $totalAppsAtivas, null],
        ['fas fa-database',    '#26FF93', '#e0fff3', 'Banco de Dados',       $totalBD,         '?page=bancodados'],
        ['fas fa-sync-alt',    '#a78bfa', '#f0eeff', 'Última Sincronização', $ultimaSyncFmt,   null],
    ];
    foreach ($kpis as [$icon, $cor, $bg, $label, $val, $link]):
    ?>
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1.1rem 1.25rem;display:flex;align-items:center;gap:.9rem<?= $link ? ';cursor:pointer' : '' ?>"
         <?= $link ? "onclick=\"window.location.href='{$link}'\"" : '' ?>>
        <div style="width:44px;height:44px;border-radius:10px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="<?= $icon ?>" style="font-size:1.1rem;color:<?= $cor ?>"></i>
        </div>
        <div>
            <div style="font-size:1.4rem;font-weight:800;color:#1a202c;line-height:1"><?= $val ?></div>
            <div style="font-size:.72rem;color:#a0aec0;margin-top:.2rem;text-transform:uppercase;letter-spacing:.04em"><?= $label ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Banco de Dados — Status e Histórico -->
<div style="display:grid;grid-template-columns:1fr 1.6fr;gap:1rem;align-items:start">

    <!-- Status dos clientes BD -->
    <div class="table-panel" style="padding:0">
        <div style="padding:.75rem 1.25rem;border-bottom:1px solid #f0f4f8;display:flex;align-items:center;justify-content:space-between">
            <span style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#a0aec0">
                <i class="fas fa-database" style="color:#0DC2FF"></i> Banco de Dados — Clientes
            </span>
            <button onclick="dashBDRecarregar()" style="border:none;background:none;color:#a0aec0;cursor:pointer;font-size:.8rem">
                <i class="fas fa-sync-alt" id="dash-refresh-icon"></i>
            </button>
        </div>
        <div id="dash-bd-clientes" style="padding:.75rem">
            <div style="text-align:center;color:#a0aec0;padding:1.5rem"><i class="fas fa-spinner fa-spin"></i></div>
        </div>
    </div>

    <!-- Histórico de sincronizações -->
    <div class="table-panel" style="padding:0">
        <div style="padding:.75rem 1.25rem;border-bottom:1px solid #f0f4f8">
            <span style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#a0aec0">
                <i class="fas fa-history" style="color:#086B8D"></i> Sincronizações Recentes
            </span>
        </div>
        <div class="table-scroll">
            <table class="clientes-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Entidade</th>
                        <th>Reg.</th>
                        <th>Status</th>
                        <th>Horário</th>
                    </tr>
                </thead>
                <tbody id="dash-bd-historico">
                    <tr><td colspan="5" style="text-align:center;padding:2rem;color:#a0aec0">
                        <i class="fas fa-spinner fa-spin"></i>
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
function dashBDCarregar() {
    fetch('/api/bancodados-status.php', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            if (data.erro) return;
            dashBDRenderClientes(data.clientes || []);
            dashBDRenderHistorico(data.historico || []);
        });
}

function dashBDRecarregar() {
    const icon = document.getElementById('dash-refresh-icon');
    if (icon) icon.classList.add('fa-spin');
    fetch('/api/bancodados-status.php', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            if (icon) icon.classList.remove('fa-spin');
            if (data.erro) return;
            dashBDRenderClientes(data.clientes || []);
            dashBDRenderHistorico(data.historico || []);
        })
        .catch(() => { if (icon) icon.classList.remove('fa-spin'); });
}

function dashBDRenderClientes(clientes) {
    const el = document.getElementById('dash-bd-clientes');
    if (!clientes.length) {
        el.innerHTML = '<p style="color:#a0aec0;font-size:.85rem;text-align:center;padding:1rem">Nenhum cliente configurado.</p>';
        return;
    }
    const corMap = { green:'#38a169', yellow:'#d69e2e', gray:'#a0aec0' };
    const icMap  = { green:'fa-check-circle', yellow:'fa-clock', gray:'fa-minus-circle' };

    el.innerHTML = clientes.map(c => {
        const cor   = corMap[c.status_cor] || '#a0aec0';
        const ic    = icMap[c.status_cor]  || 'fa-minus-circle';
        const ultimo = c.last_synced ? new Date(c.last_synced).toLocaleString('pt-BR') : 'Nunca';
        return `
        <div style="display:flex;align-items:center;justify-content:space-between;padding:.6rem .25rem;border-bottom:1px solid #f0f4f8">
            <div>
                <div style="font-weight:600;font-size:.85rem;color:#2d3748">${c.cliente_nome}</div>
                <div style="font-size:.72rem;color:#a0aec0;font-family:monospace">bx_sync_${c.db_name} · ${c.intervalo_h}h</div>
            </div>
            <div style="text-align:right">
                <div style="font-size:.75rem;font-weight:700;color:${cor}"><i class="fas ${ic}"></i> ${c.status_label}</div>
                <div style="font-size:.7rem;color:#a0aec0">${ultimo}</div>
            </div>
        </div>`;
    }).join('');
}

function dashBDRenderHistorico(historico) {
    const tbody = document.getElementById('dash-bd-historico');
    if (!historico.length) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:1.5rem;color:#a0aec0">Nenhuma sincronização registrada ainda.</td></tr>';
        return;
    }
    tbody.innerHTML = historico.slice(0, 10).map(h => {
        const ok = h.status === 'ok';
        const dt = new Date(h.executado_em).toLocaleTimeString('pt-BR', {hour:'2-digit',minute:'2-digit'});
        const day = new Date(h.executado_em).toLocaleDateString('pt-BR',{day:'2-digit',month:'2-digit'});
        return `
        <tr>
            <td style="font-weight:600;font-size:.82rem;color:#2d3748;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${h.cliente_nome}</td>
            <td style="font-family:monospace;font-size:.78rem;color:#718096">${h.entidade}</td>
            <td style="text-align:center;font-size:.82rem">${h.registros}</td>
            <td>
                <span style="font-size:.72rem;font-weight:700;padding:.15rem .5rem;border-radius:20px;
                    background:${ok?'#f0fff4':'#fff5f5'};color:${ok?'#276749':'#c53030'}">
                    ${ok ? 'OK' : 'Erro'}
                </span>
            </td>
            <td style="font-size:.75rem;color:#a0aec0;white-space:nowrap">${day} ${dt}</td>
        </tr>`;
    }).join('');
}

dashBDCarregar();
setInterval(dashBDRecarregar, 120000); // auto-refresh 2 min
</script>
