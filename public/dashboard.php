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

<!-- Monitor de Sincronização -->
<div class="table-panel" style="padding:0">
    <div style="padding:.85rem 1.25rem;border-bottom:1px solid #f0f4f8;display:flex;align-items:center;justify-content:space-between">
        <span style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#a0aec0">
            <i class="fas fa-database" style="color:#0DC2FF;margin-right:.3rem"></i> Sincronização — Banco de Dados
        </span>
        <button onclick="syncRecarregar()" style="border:none;background:none;color:#a0aec0;cursor:pointer;font-size:.85rem;display:flex;align-items:center;gap:.3rem">
            <i class="fas fa-sync-alt" id="sync-refresh-icon"></i>
            <span style="font-size:.75rem">Atualizar</span>
        </button>
    </div>

    <!-- Em andamento -->
    <div id="sync-andamento-wrap" style="display:none;border-bottom:1px solid #f0f4f8;padding:.5rem 1.25rem .75rem">
        <div style="font-size:.7rem;font-weight:700;color:#d69e2e;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.5rem">
            <i class="fas fa-spinner fa-spin"></i> Em andamento
        </div>
        <div id="sync-andamento"></div>
    </div>

    <!-- Concluídos / Nunca -->
    <div style="padding:.75rem 1.25rem">
        <div style="font-size:.7rem;font-weight:700;color:#a0aec0;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.5rem">
            Concluídos
        </div>
        <div id="sync-concluidos">
            <div style="text-align:center;color:#a0aec0;padding:1.5rem"><i class="fas fa-spinner fa-spin"></i></div>
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
            syncRender(data.clientes || [], data.historico || []);
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
            syncRender(data.clientes || [], data.historico || []);
        })
        .catch(() => { if (icon) icon.classList.remove('fa-spin'); });
}

function syncRender(clientes, historico) {
    const andamento  = clientes.filter(c => c.status_cor === 'yellow' && c.last_synced);
    // Adiciona running como "em andamento" visual
    const running    = clientes.filter(c => c.running_since);
    const concluidos = clientes.filter(c => c.status_cor === 'green');
    const nunca      = clientes.filter(c => c.status_cor === 'gray');

    // Monta mapa de histórico por cliente (últimas entidades sincronizadas)
    const histMap = {};
    historico.forEach(h => {
        if (!histMap[h.cliente_nome]) histMap[h.cliente_nome] = [];
        if (histMap[h.cliente_nome].length < 8) histMap[h.cliente_nome].push(h);
    });

    // Em andamento
    const andamentoWrap = document.getElementById('sync-andamento-wrap');
    const andamentoEl   = document.getElementById('sync-andamento');
    if (running.length) {
        andamentoWrap.style.display = 'block';
        andamentoEl.innerHTML = running.map(c => syncClienteCard(c, histMap, true)).join('');
    } else {
        andamentoWrap.style.display = 'none';
    }

    // Concluídos + nunca
    const concluidosEl = document.getElementById('sync-concluidos');
    const todos = [...concluidos, ...andamento, ...nunca];
    if (!todos.length) {
        concluidosEl.innerHTML = '<p style="color:#a0aec0;font-size:.85rem;text-align:center;padding:1rem">Nenhum cliente com Banco de Dados configurado.</p>';
        return;
    }
    concluidosEl.innerHTML = todos.map(c => syncClienteCard(c, histMap, false)).join('');
}

function syncClienteCard(c, histMap, running) {
    const cor  = running ? '#d69e2e' : (c.status_cor === 'green' ? '#38a169' : '#a0aec0');
    const ic   = running ? 'fa-spinner fa-spin' : (c.status_cor === 'green' ? 'fa-check-circle' : 'fa-minus-circle');
    const label= running ? 'Em andamento' : c.status_label;
    const ultimo = c.last_synced ? new Date(c.last_synced).toLocaleString('pt-BR') : '—';
    const id   = 'sync-detail-' + c.cliente_id;

    const entidades = (histMap[c.cliente_nome] || []);
    const detailHtml = entidades.length ? entidades.map(h => `
        <div style="display:flex;align-items:center;gap:.75rem;padding:.3rem 0;border-bottom:1px solid #f7fafc">
            <span style="font-family:monospace;font-size:.78rem;color:#4a5568;flex:1">${h.entidade}</span>
            <span style="font-size:.78rem;color:#718096">${h.registros} reg.</span>
            <span style="font-size:.72rem;font-weight:700;padding:.1rem .4rem;border-radius:10px;
                background:${h.status==='ok'?'#f0fff4':'#fff5f5'};color:${h.status==='ok'?'#276749':'#c53030'}">
                ${h.status === 'ok' ? 'OK' : 'Erro'}
            </span>
            <span style="font-size:.7rem;color:#a0aec0">${new Date(h.executado_em).toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'})}</span>
        </div>`).join('')
    : '<p style="color:#a0aec0;font-size:.8rem;padding:.5rem 0">Nenhum registro de sync ainda.</p>';

    return `
    <div style="border:1px solid #e2e8f0;border-radius:8px;margin-bottom:.5rem;overflow:hidden">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:.7rem 1rem;cursor:pointer;background:#fff"
             onclick="syncToggle('${id}', this)">
            <div style="display:flex;align-items:center;gap:.75rem">
                <i class="fas ${ic}" style="color:${cor};font-size:.9rem;width:16px"></i>
                <div>
                    <div style="font-weight:600;font-size:.875rem;color:#2d3748">${c.cliente_nome}</div>
                    <div style="font-size:.72rem;color:#a0aec0;font-family:monospace">bx_sync_${c.db_name} · a cada ${c.intervalo_h}h</div>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:1rem">
                <div style="text-align:right">
                    <div style="font-size:.75rem;font-weight:600;color:${cor}">${label}</div>
                    <div style="font-size:.7rem;color:#a0aec0">${ultimo}</div>
                </div>
                <i class="fas fa-chevron-down sync-chevron" style="color:#a0aec0;font-size:.75rem;transition:transform .2s"></i>
            </div>
        </div>
        <div id="${id}" style="display:none;padding:.5rem 1rem .75rem;background:#f8fafc;border-top:1px solid #e2e8f0">
            ${detailHtml}
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
