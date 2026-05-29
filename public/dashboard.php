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

<!-- Linha principal: espaço para futuras widgets + painel sync compacto -->
<div style="display:grid;grid-template-columns:1fr 340px;gap:1rem;align-items:start">

    <!-- Espaço para futuras aplicações/widgets -->
    <div id="dash-widgets" style="min-height:200px">
        <!-- futuras widgets aqui -->
    </div>

    <!-- Painel de sync compacto -->
    <div class="table-panel" style="padding:0">
        <div style="padding:.65rem 1rem;border-bottom:1px solid #f0f4f8;display:flex;align-items:center;justify-content:space-between">
            <span style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#a0aec0">
                <i class="fas fa-database" style="color:#0DC2FF;margin-right:.25rem"></i> Banco de Dados
            </span>
            <button onclick="syncRecarregar()" style="border:none;background:none;color:#a0aec0;cursor:pointer">
                <i class="fas fa-sync-alt" id="sync-refresh-icon" style="font-size:.8rem"></i>
            </button>
        </div>
        <div id="sync-lista" style="padding:.5rem .75rem">
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
    const lista = document.getElementById('sync-lista');

    if (!clientes.length) {
        lista.innerHTML = '<p style="color:#a0aec0;font-size:.82rem;text-align:center;padding:1rem">Nenhum cliente configurado.</p>';
        return;
    }

    // Mapa histórico por cliente
    const histMap = {};
    historico.forEach(h => {
        if (!histMap[h.cliente_nome]) histMap[h.cliente_nome] = [];
        if (histMap[h.cliente_nome].length < 10) histMap[h.cliente_nome].push(h);
    });

    // Ordena: rodando primeiro, depois concluídos, depois nunca
    const ordenados = [
        ...clientes.filter(c => c.running_since),
        ...clientes.filter(c => !c.running_since && c.status_cor === 'green'),
        ...clientes.filter(c => !c.running_since && c.status_cor !== 'green'),
    ];

    lista.innerHTML = ordenados.map(c => syncClienteCard(c, histMap)).join('');
}

function syncClienteCard(c, histMap) {
    const isRunning = !!c.running_since;
    const cor   = isRunning ? '#d69e2e' : (c.status_cor === 'green' ? '#38a169' : '#a0aec0');
    const ic    = isRunning ? 'fa-spinner fa-spin' : (c.status_cor === 'green' ? 'fa-check-circle' : 'fa-minus-circle');
    const label = isRunning ? 'Em andamento' : c.status_label;
    const ultimo = c.last_synced
        ? new Date(c.last_synced).toLocaleString('pt-BR',{day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'})
        : '—';
    const id = 'sync-detail-' + c.cliente_id;

    const entidades  = histMap[c.cliente_nome] || [];
    const detailHtml = entidades.length
        ? entidades.map(h => `
            <div style="display:flex;align-items:center;gap:.5rem;padding:.25rem 0;border-bottom:1px solid #f0f4f8;font-size:.75rem">
                <span style="font-family:monospace;color:#4a5568;flex:1">${h.entidade}</span>
                <span style="color:#718096">${h.registros}</span>
                <span style="font-weight:700;color:${h.status==='ok'?'#38a169':'#c53030'}">${h.status==='ok'?'OK':'Err'}</span>
            </div>`).join('')
        : '<p style="color:#a0aec0;font-size:.78rem;padding:.4rem 0">Sem registros de sync.</p>';

    // Nome curto para o card compacto
    const nomeShort = c.cliente_nome.length > 28 ? c.cliente_nome.substring(0,26)+'…' : c.cliente_nome;

    return `
    <div style="border-bottom:1px solid #f0f4f8;margin-bottom:.1rem">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:.55rem .25rem;cursor:pointer"
             onclick="syncToggle('${id}', this)">
            <div style="display:flex;align-items:center;gap:.5rem;min-width:0">
                <i class="fas ${ic}" style="color:${cor};font-size:.8rem;flex-shrink:0"></i>
                <div style="min-width:0">
                    <div style="font-weight:600;font-size:.8rem;color:#2d3748;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${nomeShort}</div>
                    <div style="font-size:.68rem;color:#a0aec0">${ultimo}</div>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:.4rem;flex-shrink:0">
                <span style="font-size:.68rem;font-weight:700;color:${cor}">${label}</span>
                <i class="fas fa-chevron-down sync-chevron" style="color:#cbd5e0;font-size:.65rem;transition:transform .2s"></i>
            </div>
        </div>
        <div id="${id}" style="display:none;padding:.25rem .5rem .6rem;background:#f8fafc;border-radius:6px;margin-bottom:.4rem">
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
