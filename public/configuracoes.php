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
/* ===== CONFIGURAÇÕES ===== */
.config-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.25rem;
}

@media (max-width: 900px) { .config-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 520px) { .config-grid { grid-template-columns: 1fr; } }

.config-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.75rem 1.25rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.85rem;
    cursor: pointer;
    transition: box-shadow 0.18s, transform 0.18s, border-color 0.18s;
    user-select: none;
}
.config-card:hover {
    box-shadow: 0 6px 24px rgba(0,0,0,0.12);
    transform: translateY(-2px);
}
.config-card.active {
    border-color: #0DC2FF;
    box-shadow: 0 0 0 2px rgba(13,194,255,0.18);
}

.config-card-icon {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: #e0f7ff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    color: #718096;
    transition: color 0.18s, background 0.18s;
}
.config-card.active .config-card-icon {
    color: #0DC2FF;
    background: #e0f7ff;
}

.config-card-label {
    font-family: 'Rubik', sans-serif;
    font-size: 0.9rem;
    font-weight: 600;
    color: #1a202c;
}

/* ===== PAINEL EXPANDIDO ===== */
.config-panel {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.75rem;
    animation: cfgSlide 0.18s ease;
}
@keyframes cfgSlide {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: translateY(0); }
}

.config-panel-title {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #a0aec0;
    margin: 0 0 1.25rem;
}

.config-panel-placeholder {
    color: #718096;
    font-size: 0.9rem;
}

/* ===== FORM FIELDS ===== */
.cfg-field {
    margin-bottom: 1.25rem;
}
.cfg-field:last-of-type { margin-bottom: 0; }

.cfg-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 700;
    color: #4a5568;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.4rem;
    font-family: 'Rubik', sans-serif;
}

.cfg-input {
    width: 100%;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 0.6rem 0.75rem;
    font-size: 0.875rem;
    color: #2d3748;
    font-family: 'Inter', sans-serif;
    outline: none;
    transition: border-color 0.15s;
    box-sizing: border-box;
}
.cfg-input:focus { border-color: #0DC2FF; }

.cfg-input-narrow { max-width: 160px; }

.cfg-desc {
    font-size: 0.78rem;
    color: #718096;
    margin: 0.35rem 0 0;
}

.cfg-form-footer {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-top: 1.5rem;
    padding-top: 1.25rem;
    border-top: 1px solid #e2e8f0;
}

.cfg-feedback {
    font-size: 0.82rem;
    font-weight: 500;
}
.cfg-feedback.ok    { color: #065f46; }
.cfg-feedback.erro  { color: #c53030; }
</style>

<div class="page-header">
    <h1 class="page-title"><i class="fas fa-cog"></i> Configurações</h1>
</div>

<div class="config-grid" id="cfgGrid">

    <div class="config-card" data-section="colaboradores" onclick="cfgOpen(this)">
        <div class="config-card-icon"><i class="fas fa-users"></i></div>
        <span class="config-card-label">Colaboradores</span>
    </div>

    <div class="config-card" data-section="permissoes" onclick="cfgOpen(this)">
        <div class="config-card-icon"><i class="fas fa-shield-alt"></i></div>
        <span class="config-card-label">Permissões</span>
    </div>

    <div class="config-card" data-section="sistema" onclick="cfgOpen(this)">
        <div class="config-card-icon"><i class="fas fa-cog"></i></div>
        <span class="config-card-label">Sistema</span>
    </div>

    <div class="config-card" data-section="financeiro" onclick="cfgOpen(this)">
        <div class="config-card-icon"><i class="fas fa-dollar-sign"></i></div>
        <span class="config-card-label">Financeiro</span>
    </div>

</div>

<!-- Painel expandido — único, conteúdo trocado por JS -->
<div class="config-panel" id="cfgPanel" style="display:none">

    <!-- Colaboradores -->
    <div id="cfgSec-colaboradores" style="display:none">
        <p class="config-panel-title">Colaboradores</p>
        <p class="config-panel-placeholder">Em breve</p>
    </div>

    <!-- Permissões -->
    <div id="cfgSec-permissoes" style="display:none">
        <p class="config-panel-title">Permissões</p>
        <p class="config-panel-placeholder">Em breve</p>
    </div>

    <!-- Sistema -->
    <div id="cfgSec-sistema" style="display:none">
        <p class="config-panel-title">Sistema</p>
        <p class="config-panel-placeholder">Em breve</p>
    </div>

    <!-- Financeiro -->
    <div id="cfgSec-financeiro" style="display:none">
        <p class="config-panel-title">Financeiro</p>

        <div class="cfg-field">
            <label class="cfg-label" for="cfg-dia-inicio">Dia de início do período</label>
            <input id="cfg-dia-inicio" class="cfg-input cfg-input-narrow"
                   type="number" min="1" max="28" value="27">
            <p class="cfg-desc">Define o dia em que começa o novo período de faturamento.</p>
        </div>

        <div class="cfg-field">
            <label class="cfg-label" for="cfg-webhook-bitrix">Webhook Bitrix24 (KW24)</label>
            <input id="cfg-webhook-bitrix" class="cfg-input"
                   type="url" placeholder="https://gnapp.bitrix24.com.br/rest/...">
            <p class="cfg-desc">URL do webhook de acesso à conta Bitrix24 da KW24.</p>
        </div>

        <div class="cfg-form-footer">
            <button class="btn-primary" onclick="cfgSalvarFinanceiro()">
                <i class="fas fa-save"></i> Salvar configurações
            </button>
            <span class="cfg-feedback" id="cfg-financeiro-feedback"></span>
        </div>
    </div>

</div>

<script>
(function () {
    var activeSection = null;

    window.cfgOpen = function (card) {
        var section = card.getAttribute('data-section');

        if (activeSection === section) {
            // Fechar se clicar no mesmo card
            card.classList.remove('active');
            document.getElementById('cfgPanel').style.display = 'none';
            activeSection = null;
            return;
        }

        // Desmarcar todos os cards
        document.querySelectorAll('#cfgGrid .config-card').forEach(function (c) {
            c.classList.remove('active');
        });

        // Esconder todos os conteúdos
        document.querySelectorAll('#cfgPanel > div[id^="cfgSec-"]').forEach(function (el) {
            el.style.display = 'none';
        });

        // Ativar card e seção selecionados
        card.classList.add('active');
        var sec = document.getElementById('cfgSec-' + section);
        if (sec) sec.style.display = 'block';

        var panel = document.getElementById('cfgPanel');
        panel.style.display = 'block';
        // Re-disparar animação
        panel.style.animation = 'none';
        panel.offsetHeight; // reflow
        panel.style.animation = '';

        activeSection = section;
    };

    // Carregar valores salvos ao abrir a página
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

    // Validação client-side
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
