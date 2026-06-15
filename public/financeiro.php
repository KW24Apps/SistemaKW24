<?php
if (!defined('SYSTEM_ACCESS') && !isset($user_data)) {
    header('Location: /public/login.php'); exit;
}
?>

<style>
/* ===== FINANCEIRO ===== */
.fin-periodo-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: rgba(255,255,255,0.07);
    border: 1.5px solid rgba(13,194,255,0.20);
    border-radius: 12px;
    padding: .9rem 1.25rem;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
    gap: .75rem;
}

.fin-periodo-info {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    flex-wrap: wrap;
}

.fin-periodo-label {
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #0DC2FF;
}

.fin-periodo-val {
    font-size: .875rem;
    font-weight: 600;
    color: #fff;
}

.fin-periodo-range {
    font-size: .78rem;
    color: rgba(255,255,255,.45);
}

.fin-sync-btn {
    display: flex;
    align-items: center;
    gap: .5rem;
    background: #0DC2FF;
    color: #061920;
    border: none;
    border-radius: 8px;
    padding: .5rem 1rem;
    font-size: .82rem;
    font-weight: 700;
    cursor: pointer;
    transition: background .15s, opacity .15s;
    white-space: nowrap;
    flex-shrink: 0;
}
.fin-sync-btn:hover    { background: #08aadd; }
.fin-sync-btn:disabled { opacity: .55; cursor: not-allowed; }

.fin-sync-feedback {
    font-size: .78rem;
    font-weight: 500;
    margin-top: .35rem;
}
.fin-sync-feedback.ok   { color: #26FF93; }
.fin-sync-feedback.erro { color: #fc8181; }

/* Tabela */
.fin-table-panel {
    background: rgba(255,255,255,0.05);
    border: 1.5px solid rgba(255,255,255,0.10);
    border-radius: 12px;
    overflow: hidden;
}

.fin-table-header {
    padding: .75rem 1.25rem;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.fin-table-title {
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: rgba(255,255,255,.5);
}

#fin-total {
    font-size: .75rem;
    color: rgba(255,255,255,.35);
}

.fin-table-scroll {
    overflow-x: auto;
}

.fin-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .85rem;
}

.fin-table th {
    padding: .65rem 1rem;
    text-align: left;
    font-size: .68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: rgba(255,255,255,.4);
    border-bottom: 1px solid rgba(255,255,255,0.08);
    white-space: nowrap;
}

.fin-table td {
    padding: .7rem 1rem;
    color: rgba(255,255,255,.85);
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.fin-table tbody tr:last-child td {
    border-bottom: none;
}

.fin-table tbody tr:hover td {
    background: rgba(255,255,255,0.03);
}

.fin-table td.empresa {
    font-weight: 600;
    color: #fff;
}

.fin-table td.minutos {
    font-family: 'Inter', monospace;
    text-align: right;
}

.fin-stage-badge {
    display: inline-block;
    font-size: .7rem;
    font-weight: 700;
    padding: .2rem .55rem;
    border-radius: 20px;
}

.fin-empty {
    text-align: center;
    padding: 3rem 1rem;
    color: rgba(255,255,255,.3);
}

.fin-empty i {
    font-size: 2rem;
    margin-bottom: .75rem;
    display: block;
    color: rgba(13,194,255,.4);
}

.fin-empty-msg {
    font-size: .875rem;
}
</style>

<div class="page-header">
    <h1 class="page-title"><i class="fas fa-dollar-sign"></i> Financeiro</h1>
</div>

<!-- Barra de período + botão sincronizar -->
<div class="fin-periodo-bar">
    <div class="fin-periodo-info">
        <div>
            <div class="fin-periodo-label">Período de faturamento</div>
            <div class="fin-periodo-val" id="fin-periodo-ref">—</div>
        </div>
        <div class="fin-periodo-range" id="fin-periodo-range">Carregando…</div>
    </div>
    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:.3rem">
        <?php if (isset($user_data['perfil']) && $user_data['perfil'] === 'admin_interno'): ?>
        <button class="fin-sync-btn" id="finSyncBtn" onclick="finSincronizar()">
            <i class="fas fa-sync-alt" id="finSyncIcon"></i> Sincronizar
        </button>
        <div class="fin-sync-feedback" id="finSyncFeedback"></div>
        <?php endif; ?>
    </div>
</div>

<!-- Tabela de cards financeiros -->
<div class="fin-table-panel">
    <div class="fin-table-header">
        <span class="fin-table-title"><i class="fas fa-file-invoice-dollar" style="color:#0DC2FF;margin-right:.4rem"></i> Cards Financeiros — período atual</span>
        <span id="fin-total"></span>
    </div>
    <div class="fin-table-scroll">
        <table class="fin-table">
            <thead>
                <tr>
                    <th>Empresa</th>
                    <th>Status</th>
                    <th style="text-align:right">Suporte (min)</th>
                    <th style="text-align:right">Dev (min)</th>
                    <th style="text-align:right">Total (min)</th>
                </tr>
            </thead>
            <tbody id="fin-tbody">
                <tr><td colspan="5" class="fin-empty">
                    <i class="fas fa-spinner fa-spin"></i>
                    <div class="fin-empty-msg">Carregando…</div>
                </td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {

    function stageBadge(stageId) {
        var s = (stageId || '').toString().toUpperCase();
        var cor, bg, label;

        if (s.indexOf('CONVERTED') !== -1 || s.indexOf('SUCCESS') !== -1 || s.indexOf('WON') !== -1) {
            cor = '#26FF93'; bg = 'rgba(38,255,147,.12)'; label = 'Concluído';
        } else if (s.indexOf('LOSE') !== -1 || s.indexOf('FAIL') !== -1) {
            cor = '#fc8181'; bg = 'rgba(252,129,129,.12)'; label = 'Cancelado';
        } else {
            cor = '#0DC2FF'; bg = 'rgba(13,194,255,.10)'; label = stageId || '—';
        }

        return '<span class="fin-stage-badge" style="color:' + cor + ';background:' + bg + '">' + label + '</span>';
    }

    function renderTabela(cards) {
        var tbody = document.getElementById('fin-tbody');
        var total = document.getElementById('fin-total');

        if (!cards || !cards.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="fin-empty">'
                + '<i class="fas fa-inbox"></i>'
                + '<div class="fin-empty-msg">Nenhum card financeiro encontrado para este período.</div>'
                + '</td></tr>';
            if (total) total.textContent = '';
            return;
        }

        if (total) total.textContent = cards.length + ' empresa' + (cards.length !== 1 ? 's' : '');

        tbody.innerHTML = cards.map(function (c) {
            var totalMin = c.minSuporte + c.minDev;
            return '<tr>'
                + '<td class="empresa">' + escHtml(c.empresa) + '</td>'
                + '<td>' + stageBadge(c.stageId) + '</td>'
                + '<td class="minutos">' + c.minSuporte.toLocaleString('pt-BR') + '</td>'
                + '<td class="minutos">' + c.minDev.toLocaleString('pt-BR') + '</td>'
                + '<td class="minutos" style="font-weight:700;color:#fff">' + totalMin.toLocaleString('pt-BR') + '</td>'
                + '</tr>';
        }).join('');
    }

    function escHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function carregarCards() {
        fetch('/api/financeiro-cards.php', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.erro) {
                    document.getElementById('fin-tbody').innerHTML =
                        '<tr><td colspan="5" class="fin-empty">'
                        + '<i class="fas fa-exclamation-circle" style="color:#fc8181"></i>'
                        + '<div class="fin-empty-msg" style="color:#fc8181">' + escHtml(data.erro) + '</div>'
                        + '</td></tr>';
                    return;
                }

                var p = data.periodo || {};
                var refEl   = document.getElementById('fin-periodo-ref');
                var rangeEl = document.getElementById('fin-periodo-range');

                if (refEl)   refEl.textContent   = p.referencia || '—';
                if (rangeEl) rangeEl.textContent  = p.inicio && p.fim
                    ? p.inicio + ' → ' + p.fim
                    : '';

                if (data.aviso) {
                    document.getElementById('fin-tbody').innerHTML =
                        '<tr><td colspan="5" class="fin-empty">'
                        + '<i class="fas fa-plug" style="color:rgba(255,255,255,.3)"></i>'
                        + '<div class="fin-empty-msg">' + escHtml(data.aviso) + '</div>'
                        + '</td></tr>';
                    return;
                }

                renderTabela(data.cards || []);
            })
            .catch(function (e) {
                console.error(e);
            });
    }

    window.finSincronizar = function () {
        var btn = document.getElementById('finSyncBtn');
        var icon = document.getElementById('finSyncIcon');
        var fb   = document.getElementById('finSyncFeedback');

        if (btn) btn.disabled = true;
        if (icon) { icon.classList.add('fa-spin'); }
        if (fb) { fb.textContent = ''; fb.className = 'fin-sync-feedback'; }

        fetch('/api/financeiro-sync.php', {
            method:      'POST',
            credentials: 'same-origin',
            headers:     { 'Content-Type': 'application/json' },
            body:        JSON.stringify({}),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (btn)  btn.disabled  = false;
            if (icon) icon.classList.remove('fa-spin');

            if (data.erro) {
                if (fb) { fb.textContent = data.erro; fb.className = 'fin-sync-feedback erro'; }
                return;
            }

            var r = data.resultado || {};
            var msg = 'Sync concluído: ' + (r.atualizados || 0) + ' empresa(s), '
                + (r.demandas_total || 0) + ' demandas';
            if (r.erros > 0) msg += ' (' + r.erros + ' erro(s))';

            if (fb) { fb.textContent = msg; fb.className = 'fin-sync-feedback ' + (r.erros > 0 ? 'erro' : 'ok'); }

            carregarCards();
        })
        .catch(function () {
            if (btn)  btn.disabled  = false;
            if (icon) icon.classList.remove('fa-spin');
            if (fb)   { fb.textContent = 'Erro de comunicação.'; fb.className = 'fin-sync-feedback erro'; }
        });
    };

    carregarCards();
})();
</script>
