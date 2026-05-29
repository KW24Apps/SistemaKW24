<?php
if (!defined('SYSTEM_ACCESS') && !isset($user_data)) {
    header('Location: /public/login.php'); exit;
}
require_once __DIR__ . '/../helpers/Database.php';

try {
    $db = Database::getInstance();
    $totalClientes   = $db->fetchOne("SELECT COUNT(*) AS n FROM clientes")['n'] ?? 0;
    $totalAppsAtivas = $db->fetchOne("SELECT COUNT(*) AS n FROM cliente_aplicacoes WHERE ativo = TRUE")['n'] ?? 0;
    $valorTotal      = $db->fetchOne("SELECT COALESCE(SUM(valor),0) AS v FROM cliente_aplicacoes WHERE ativo = TRUE")['v'] ?? 0;
} catch (Exception $e) {
    $totalClientes = $totalAppsAtivas = 0;
    $valorTotal = 0;
}
$valorFmt = 'R$ ' . number_format((float)$valorTotal, 2, ',', '.');
?>

<div class="page-header" style="margin-bottom:1.5rem">
    <div>
        <h1 class="page-title" style="margin-bottom:.15rem"><i class="fas fa-home"></i> Dashboard</h1>
        <p style="font-size:.82rem;color:#a0aec0;margin:0">Bem-vindo, <?= htmlspecialchars($user_data['nome']) ?></p>
    </div>
</div>

<!-- KPIs gerais -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.75rem">
    <?php
    $kpis = [
        ['fas fa-users',    '#0DC2FF', '#e0f7ff', 'Clientes',       $totalClientes],
        ['fas fa-th',       '#086B8D', '#e6f2f7', 'Apps Ativas',    $totalAppsAtivas],
        ['fas fa-dollar-sign','#26FF93','#e0fff3', 'Valor em Carteira', $valorFmt],
    ];
    foreach ($kpis as [$icon, $cor, $bg, $label, $val]):
    ?>
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1.25rem 1.5rem;display:flex;align-items:center;gap:1rem">
        <div style="width:48px;height:48px;border-radius:10px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="<?= $icon ?>" style="font-size:1.2rem;color:<?= $cor ?>"></i>
        </div>
        <div>
            <div style="font-size:1.5rem;font-weight:800;color:#1a202c;line-height:1"><?= $val ?></div>
            <div style="font-size:.72rem;color:#a0aec0;margin-top:.25rem;text-transform:uppercase;letter-spacing:.05em"><?= $label ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Linha de painéis: 2 por linha, meia página cada -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;align-items:start">

    <!-- Espaço para futuras widgets -->
    <div class="table-panel" style="padding:0;min-height:280px">
        <div style="padding:.75rem 1.25rem;border-bottom:1px solid #f0f4f8">
            <span style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#a0aec0">
                <i class="fas fa-chart-bar" style="color:#a0aec0;margin-right:.3rem"></i> Em breve
            </span>
        </div>
        <div style="display:flex;align-items:center;justify-content:center;min-height:220px;color:#e2e8f0">
            <div style="text-align:center">
                <i class="fas fa-chart-line" style="font-size:2.5rem;margin-bottom:.75rem;display:block"></i>
                <span style="font-size:.82rem">Próxima widget</span>
            </div>
        </div>
    </div>

    <!-- Painel de sync Banco de Dados -->
    <div class="table-panel" style="padding:0">
        <div style="padding:.75rem 1.25rem;border-bottom:1px solid #f0f4f8;display:flex;align-items:center;justify-content:space-between">
            <span style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#a0aec0">
                <i class="fas fa-database" style="color:#0DC2FF;margin-right:.3rem"></i> Banco de Dados — Sincronização
            </span>
            <button onclick="syncRecarregar()" style="border:none;background:none;color:#a0aec0;cursor:pointer;display:flex;align-items:center;gap:.3rem">
                <i class="fas fa-sync-alt" id="sync-refresh-icon" style="font-size:.78rem"></i>
                <span style="font-size:.72rem">Atualizar</span>
            </button>
        </div>
        <div id="sync-lista" style="padding:.5rem 1rem">
            <div style="text-align:center;color:#a0aec0;padding:2rem"><i class="fas fa-spinner fa-spin"></i></div>
        </div>
    </div>
</div>

<script>
let syncData = { clientes: [], historico: [] };

function syncCarregar() {
    fetch('/api/bancodados-status.php', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            if (data.erro) return;
            syncData = data;
            syncRender(data.clientes || [], data.runs || []);
        });
}

function syncRecarregar() {
    const icon = document.getElementById('sync-refresh-icon');
    if (icon) icon.classList.add('fa-spin');
    fetch('/api/bancodados-status.php', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            if (icon) icon.classList.remove('fa-spin');
            if (data.erro) return;
            syncData = data;
            syncRender(data.clientes || [], data.runs || []);
        })
        .catch(() => { if (icon) icon.classList.remove('fa-spin'); });
}

function syncRender(clientes, runs) {
    const lista = document.getElementById('sync-lista');
    const emAndamento = clientes.filter(c => c.running_since);

    if (!emAndamento.length && !runs.length) {
        lista.innerHTML = '<p style="color:#a0aec0;font-size:.82rem;text-align:center;padding:1.5rem">Nenhuma sincronização registrada.</p>';
        return;
    }

    const andamentoHtml = emAndamento.map(c => {
        const nome = c.cliente_nome.length > 32 ? c.cliente_nome.substring(0,30)+'…' : c.cliente_nome;
        const inicio = c.run_started ? new Date(c.run_started).toLocaleString('pt-BR',{day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'}) : '—';
        return `
        <div style="border-bottom:1px solid #f0f4f8;padding:.75rem .25rem;display:flex;align-items:center;justify-content:space-between">
            <div style="display:flex;align-items:center;gap:.65rem">
                <i class="fas fa-spinner fa-spin" style="color:#d69e2e;font-size:.95rem;width:18px;text-align:center"></i>
                <div>
                    <div style="font-weight:600;font-size:.875rem;color:#2d3748">${nome}</div>
                    <div style="font-size:.72rem;color:#a0aec0">${inicio}</div>
                </div>
            </div>
            <span style="font-size:.72rem;font-weight:700;padding:.25rem .7rem;border-radius:20px;background:#fffff0;color:#d69e2e">
                <i class="fas fa-spinner fa-spin" style="margin-right:.25rem"></i>Em andamento
            </span>
        </div>`;
    }).join('');

    lista.innerHTML = andamentoHtml + runs.map((r, i) => syncRunRow(r, i)).join('');
}

function syncRunRow(r, i) {
    const id       = 'run-' + r.cliente_id + '-' + i;
    const hasError = parseInt(r.total_erros) > 0;
    const cor      = hasError ? '#c53030' : '#38a169';
    const ic       = hasError ? 'fa-times-circle' : 'fa-check-circle';
    const label    = hasError ? 'Com erros' : 'Concluído';
    const bg       = hasError ? '#fff5f5' : '#f0fff4';
    const nome     = r.cliente_nome.length > 32 ? r.cliente_nome.substring(0,30)+'…' : r.cliente_nome;
    const dt       = new Date(r.terminou_em).toLocaleString('pt-BR',{day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'});
    const fmtHM    = dt => new Date(dt).toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'});
    const durSec   = (new Date(r.terminou_em) - new Date(r.iniciou_em)) / 1000;
    const durStr   = durSec >= 60 ? Math.round(durSec/60) + ' min' : Math.round(durSec) + 's';

    const entidadesHtml = (r.entidades || []).map(e => `
        <div style="display:flex;align-items:center;gap:.5rem;padding:.25rem 0;border-bottom:1px solid #f0f4f8;font-size:.75rem">
            <span style="color:#4a5568;flex:1">${e.entidade_label || e.entidade}</span>
            <span style="color:#718096;min-width:40px;text-align:right">${e.registros}</span>
            <span style="font-weight:700;min-width:28px;text-align:center;color:${e.status==='ok'?'#38a169':'#c53030'}">${e.status==='ok'?'OK':'Err'}</span>
        </div>`).join('');

    return `
    <div style="border-bottom:1px solid #f0f4f8">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:.75rem .25rem;cursor:pointer"
             onclick="syncToggle('${id}', this)">
            <div style="display:flex;align-items:center;gap:.65rem;min-width:0">
                <i class="fas ${ic}" style="color:${cor};font-size:.95rem;flex-shrink:0;width:18px;text-align:center"></i>
                <div style="min-width:0">
                    <div style="font-weight:600;font-size:.875rem;color:#2d3748;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${nome}</div>
                    <div style="font-size:.72rem;color:#a0aec0">${dt} · ${r.total_tabelas} tabelas · ${durStr}</div>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:.5rem;flex-shrink:0;margin-left:.75rem">
                <span style="font-size:.72rem;font-weight:700;padding:.25rem .7rem;border-radius:20px;background:${bg};color:${cor}">
                    <i class="fas ${ic}" style="margin-right:.25rem"></i>${label}
                </span>
                <i class="fas fa-chevron-down sync-chevron" style="color:#cbd5e0;font-size:.7rem;transition:transform .2s"></i>
            </div>
        </div>
        <div id="${id}" style="display:none;padding:.4rem .75rem .75rem;background:#f8fafc;border-radius:8px;margin-bottom:.5rem">
            <div style="display:flex;gap:1.25rem;font-size:.72rem;color:#718096;padding:.3rem 0 .5rem;border-bottom:1px solid #e2e8f0;margin-bottom:.35rem">
                <span><i class="fas fa-play" style="color:#38a169;margin-right:.25rem"></i>Início <strong>${fmtHM(r.iniciou_em)}</strong></span>
                <span><i class="fas fa-flag-checkered" style="color:#086B8D;margin-right:.25rem"></i>Fim <strong>${fmtHM(r.terminou_em)}</strong></span>
                <span><i class="fas fa-clock" style="color:#a0aec0;margin-right:.25rem"></i><strong>${durStr}</strong></span>
            </div>
            ${entidadesHtml}
        </div>
    </div>`;
}

function syncToggle(id, header) {
    const detail  = document.getElementById(id);
    const chevron = header.querySelector('.sync-chevron');
    const open    = detail.style.display === 'block';
    detail.style.display  = open ? 'none' : 'block';
    if (chevron) chevron.style.transform = open ? '' : 'rotate(180deg)';
}

syncCarregar();
setInterval(syncRecarregar, 60000); // auto-refresh 1 min
</script>
