<?php
if (!defined('SYSTEM_ACCESS') && !isset($user_data)) {
    header('Location: /public/login.php');
    exit;
}

if (!isset($user_data['perfil']) || $user_data['perfil'] !== 'admin_interno') {
    error_log("Tentativa de acesso não autorizado à área admin - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    header('Location: ?page=dashboard&error=unauthorized');
    exit;
}
?>

<style>
/* ===== CONFIGURAÇÕES — visual refactor ===== */

/* Card */
.config-card {
    width: 120px;
    height: 110px;
    border-radius: 12px;
    background: rgba(255,255,255,0.07);
    border: 1.5px solid rgba(255,255,255,0.12);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    cursor: pointer;
    transition: border-color 0.18s, background 0.18s;
    user-select: none;
    flex-shrink: 0;
}
.config-card:hover {
    background: rgba(255,255,255,0.11);
}
.config-card.active {
    border-color: #0DC2FF;
    background: rgba(13,194,255,0.10);
}

.config-card-icon {
    font-size: 1.6rem;
    color: #0DC2FF;
}

.config-card-label {
    font-family: 'Inter', sans-serif;
    font-size: 0.72rem;
    font-weight: 500;
    color: #fff;
}

/* Cards row */
.config-cards-row {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

/* Panel */
.config-panel {
    width: 100%;
    background: rgba(255,255,255,0.07);
    border: 1.5px solid rgba(13,194,255,0.25);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.25rem;
    box-sizing: border-box;
}

.config-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.25rem;
}

.config-panel-title {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #0DC2FF;
    margin: 0;
}

.config-panel-close {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: rgba(255,255,255,0.10);
    border: none;
    color: #fff;
    font-size: 0.85rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    transition: background 0.15s;
}
.config-panel-close:hover {
    background: rgba(255,255,255,0.18);
}

/* Fields grid — two columns */
.cfg-fields-grid {
    display: grid;
    grid-template-columns: 140px 1fr;
    gap: 1.5rem;
    align-items: start;
}

@media (max-width: 600px) {
    .cfg-fields-grid { grid-template-columns: 1fr; }
}

.cfg-label {
    display: block;
    font-size: 0.7rem;
    font-weight: 600;
    color: #a0aec0;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.4rem;
}

.cfg-input {
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 8px;
    padding: 0.55rem 0.7rem;
    font-size: 0.875rem;
    color: #fff;
    font-family: 'Inter', sans-serif;
    outline: none;
    transition: border-color 0.15s;
    box-sizing: border-box;
}
.cfg-input::placeholder { color: rgba(255,255,255,0.30); }
.cfg-input:focus        { border-color: #0DC2FF; }

.cfg-input-day { width: 80px; }
.cfg-input-url { width: 100%; }

.cfg-desc {
    font-size: 0.7rem;
    color: rgba(255,255,255,0.35);
    margin: 0.35rem 0 0;
}

/* Save row */
.cfg-save-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-top: 1.25rem;
}

.cfg-feedback {
    font-size: 0.82rem;
    font-weight: 500;
}
.cfg-feedback.ok   { color: #26FF93; }
.cfg-feedback.erro { color: #fc8181; }
</style>

<div class="page-header">
    <h1 class="page-title"><i class="fas fa-cog"></i> Configurações</h1>
</div>

<!-- Painel (acima dos cards, oculto por padrão) -->
<div class="config-panel" id="cfgPanel" style="display:none">

    <div class="config-panel-header">
        <span class="config-panel-title" id="cfgPanelTitle">FINANCEIRO</span>
        <button class="config-panel-close" onclick="cfgClose()" title="Fechar">✕</button>
    </div>

    <div class="cfg-fields-grid">
        <div>
            <label class="cfg-label" for="cfg-dia-inicio">Dia de início do período</label>
            <input id="cfg-dia-inicio" class="cfg-input cfg-input-day"
                   type="number" min="1" max="28" value="27">
            <p class="cfg-desc">Dia em que começa o novo período de faturamento.</p>
        </div>
        <div>
            <label class="cfg-label" for="cfg-webhook-bitrix">Webhook Bitrix24 (KW24)</label>
            <input id="cfg-webhook-bitrix" class="cfg-input cfg-input-url"
                   type="url" placeholder="https://gnapp.bitrix24.com.br/rest/...">
            <p class="cfg-desc">URL do webhook de acesso à conta Bitrix24 da KW24.</p>
        </div>
    </div>

    <div class="cfg-save-row">
        <button class="btn-primary" onclick="cfgSalvarFinanceiro()">
            <i class="fas fa-save"></i> Salvar configurações
        </button>
        <span class="cfg-feedback" id="cfg-financeiro-feedback"></span>
    </div>

</div>

<!-- Cards -->
<div class="config-cards-row" id="cfgCardsRow">

    <div class="config-card" data-section="financeiro" onclick="cfgToggle(this)">
        <span class="config-card-icon"><i class="fas fa-dollar-sign"></i></span>
        <span class="config-card-label">Financeiro</span>
    </div>

</div>

<script>
(function () {
    var activeCard = null;

    window.cfgToggle = function (card) {
        if (card.classList.contains('active')) {
            cfgClose();
            return;
        }
        card.classList.add('active');
        activeCard = card;
        document.getElementById('cfgPanel').style.display = 'block';
    };

    window.cfgClose = function () {
        if (activeCard) activeCard.classList.remove('active');
        activeCard = null;
        document.getElementById('cfgPanel').style.display = 'none';
    };

    // Pré-preencher campos com valores salvos
    fetch('/api/configuracao-carregar.php')
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data.sucesso) return;
            var d = data.dados;
            if (d['financeiro_dia_inicio']) {
                document.getElementById('cfg-dia-inicio').value = d['financeiro_dia_inicio'];
            }
            if (d['financeiro_webhook_bitrix'] !== undefined) {
                document.getElementById('cfg-webhook-bitrix').value = d['financeiro_webhook_bitrix'];
            }
        })
        .catch(function () {});
})();

window.cfgSalvarFinanceiro = function () {
    var dia     = parseInt(document.getElementById('cfg-dia-inicio').value, 10);
    var webhook = document.getElementById('cfg-webhook-bitrix').value.trim();
    var fb      = document.getElementById('cfg-financeiro-feedback');

    if (isNaN(dia) || dia < 1 || dia > 28) {
        fb.textContent = 'Dia de início deve ser entre 1 e 28.';
        fb.className   = 'cfg-feedback erro';
        return;
    }
    if (webhook !== '' && webhook.indexOf('https://') !== 0) {
        fb.textContent = 'Webhook deve começar com https://.';
        fb.className   = 'cfg-feedback erro';
        return;
    }

    fb.textContent = '';

    var salvar = function (chave, valor) {
        return fetch('/api/configuracao-salvar.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ chave: chave, valor: String(valor) })
        }).then(function (r) { return r.json(); });
    };

    Promise.all([
        salvar('financeiro_dia_inicio',     dia),
        salvar('financeiro_webhook_bitrix', webhook)
    ])
    .then(function (results) {
        var erros = results.filter(function (r) { return r.erro; });
        if (erros.length) {
            fb.textContent = erros[0].erro;
            fb.className   = 'cfg-feedback erro';
        } else {
            fb.textContent = 'Configurações salvas com sucesso.';
            fb.className   = 'cfg-feedback ok';
            setTimeout(function () { fb.textContent = ''; }, 4000);
        }
    })
    .catch(function () {
        fb.textContent = 'Erro de comunicação. Tente novamente.';
        fb.className   = 'cfg-feedback erro';
    });
};
</script>
