<?php
if (!defined('SYSTEM_ACCESS') && !isset($user_data)) {
    header('Location: /public/login.php'); exit;
}
?>
<div class="page-header">
    <h1 class="page-title"><i class="fas fa-database"></i> Banco de Dados</h1>
    <div class="page-header-actions">
        <button onclick="bdMonitorRecarregar()" class="btn-primary" style="padding:.45rem 1rem;font-size:.85rem">
            <i class="fas fa-sync-alt" id="bd-refresh-icon"></i> Atualizar
        </button>
    </div>
</div>

<!-- Status dos clientes -->
<div id="bdm-clientes" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1rem;margin-bottom:1.5rem">
    <div style="padding:2rem;text-align:center;color:#a0aec0"><i class="fas fa-spinner fa-spin"></i> Carregando...</div>
</div>

<!-- Histórico de sincronizações -->
<div class="table-panel">
    <div style="padding:.75rem 1.25rem;border-bottom:1px solid #f0f4f8;display:flex;align-items:center;justify-content:space-between">
        <span style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#a0aec0">Histórico de Sincronizações</span>
        <span id="bdm-total" style="font-size:.75rem;color:#718096"></span>
    </div>
    <div class="table-scroll">
        <table class="clientes-table">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Entidade</th>
                    <th>Registros</th>
                    <th>Status</th>
                    <th>Mensagem</th>
                    <th>Executado em</th>
                </tr>
            </thead>
            <tbody id="bdm-historico">
                <tr><td colspan="6" style="text-align:center;padding:2rem;color:#a0aec0">
                    <i class="fas fa-spinner fa-spin"></i> Carregando...
                </td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
function bdMonitorCarregar() {
    fetch('/api/bancodados-status.php', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            if (data.erro) { console.error(data.erro); return; }
            bdMonitorRenderClientes(data.clientes || []);
            bdMonitorRenderHistorico(data.historico || []);
        })
        .catch(e => console.error(e));
}

function bdMonitorRecarregar() {
    const icon = document.getElementById('bd-refresh-icon');
    if (icon) { icon.classList.add('fa-spin'); }
    fetch('/api/bancodados-status.php', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            if (icon) icon.classList.remove('fa-spin');
            if (data.erro) return;
            bdMonitorRenderClientes(data.clientes || []);
            bdMonitorRenderHistorico(data.historico || []);
        })
        .catch(() => { if (icon) icon.classList.remove('fa-spin'); });
}

function bdMonitorRenderClientes(clientes) {
    const el = document.getElementById('bdm-clientes');
    if (!clientes.length) {
        el.innerHTML = '<p style="color:#a0aec0;font-size:.875rem;grid-column:1/-1">Nenhum cliente com Banco de Dados configurado.</p>';
        return;
    }
    const corMap = { green: '#38a169', yellow: '#d69e2e', gray: '#a0aec0' };
    const bgMap  = { green: '#f0fff4', yellow: '#fffff0', gray: '#f8fafc' };
    const icMap  = { green: 'fa-check-circle', yellow: 'fa-clock', gray: 'fa-minus-circle' };

    el.innerHTML = clientes.map(c => {
        const cor    = corMap[c.status_cor] || '#a0aec0';
        const bg     = bgMap[c.status_cor]  || '#f8fafc';
        const ic     = icMap[c.status_cor]  || 'fa-minus-circle';
        const ultimo = c.last_synced ? new Date(c.last_synced).toLocaleString('pt-BR') : '—';
        const proximo= c.next_sync   ? new Date(c.next_sync).toLocaleString('pt-BR')  : '—';
        const ents   = (c.entidades || []).map(e =>
            `<span style="font-size:.72rem;background:#e2e8f0;color:#4a5568;border-radius:4px;padding:.1rem .4rem">${e.tabela}</span>`
        ).join(' ');

        return `
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1rem 1.25rem;border-left:4px solid ${cor}">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.6rem">
                <div>
                    <div style="font-weight:700;font-size:.95rem;color:#1a202c">${c.cliente_nome}</div>
                    <div style="font-size:.75rem;color:#a0aec0;font-family:monospace">bx_sync_${c.db_name}</div>
                </div>
                <span style="font-size:.8rem;font-weight:600;color:${cor}">
                    <i class="fas ${ic}"></i> ${c.status_label}
                </span>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.4rem;font-size:.78rem;color:#718096;margin-bottom:.6rem">
                <div><i class="fas fa-history" style="width:14px"></i> Última: <strong style="color:#2d3748">${ultimo}</strong></div>
                <div><i class="fas fa-forward" style="width:14px"></i> Próxima: <strong style="color:#2d3748">${proximo}</strong></div>
                <div><i class="fas fa-clock" style="width:14px"></i> Intervalo: <strong style="color:#2d3748">${c.intervalo_h}h</strong></div>
                <div><i class="fas fa-table" style="width:14px"></i> Tabelas: <strong style="color:#2d3748">${(c.entidades||[]).length}</strong></div>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:.3rem">${ents}</div>
        </div>`;
    }).join('');
}

function bdMonitorRenderHistorico(historico) {
    const tbody = document.getElementById('bdm-historico');
    const total = document.getElementById('bdm-total');
    if (total) total.textContent = historico.length + ' registros recentes';

    if (!historico.length) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:2rem;color:#a0aec0">Nenhuma sincronização registrada ainda.</td></tr>';
        return;
    }

    tbody.innerHTML = historico.map(h => {
        const ok  = h.status === 'ok';
        const dt  = new Date(h.executado_em).toLocaleString('pt-BR');
        return `
        <tr>
            <td style="font-weight:600;color:#2d3748">${h.cliente_nome}</td>
            <td style="font-family:monospace;font-size:.82rem;color:#718096">${h.entidade}</td>
            <td style="text-align:center;color:#2d3748">${h.registros}</td>
            <td>
                <span style="font-size:.75rem;font-weight:700;padding:.2rem .6rem;border-radius:20px;
                    background:${ok ? '#f0fff4' : '#fff5f5'};color:${ok ? '#276749' : '#c53030'}">
                    <i class="fas ${ok ? 'fa-check' : 'fa-times'}"></i> ${ok ? 'OK' : 'Erro'}
                </span>
            </td>
            <td style="font-size:.78rem;color:#718096;max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                title="${(h.mensagem||'').replace(/"/g,'&quot;')}">${h.mensagem || '—'}</td>
            <td style="font-size:.78rem;color:#a0aec0;white-space:nowrap">${dt}</td>
        </tr>`;
    }).join('');
}

// Carrega ao abrir a página
bdMonitorCarregar();
// Auto-refresh a cada 5 minutos
setInterval(bdMonitorRecarregar, 300000);
</script>
