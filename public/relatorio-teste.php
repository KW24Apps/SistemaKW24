<?php
if (!defined('SYSTEM_ACCESS') && !isset($user_data)) {
    header('Location: /public/login.php'); exit;
}
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
<style>
/* ── Relatórios BI Hub ──────────────────────────────────────────────────── */
.rbi-wrap {
    display: flex;
    flex-direction: column;
    gap: 1.75rem;
    padding: .25rem 0;
}

/* Page header */
.rbi-page-header {
    display: flex;
    align-items: center;
    gap: .75rem;
}
.rbi-page-icon {
    font-size: 1.4rem;
    color: #0DC2FF;
}
.rbi-page-title {
    font-family: 'Rubik', sans-serif;
    font-size: 1.6rem;
    font-weight: 600;
    color: #fff;
    line-height: 1;
}

/* Cards row — mesmo padrão de .config-cards-row (public/configuracoes.php) */
.rbi-cards-row {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: flex-start;
}

/* Individual card — mesmo padrão de .config-card (public/configuracoes.php) */
.rbi-card {
    position: relative;
    width: 120px;
    height: 110px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    border-radius: 12px;
    background: rgba(255,255,255,0.07);
    border: 1.5px solid rgba(255,255,255,0.12);
    cursor: pointer;
    transition: border-color .18s, background .18s;
    text-decoration: none;
    user-select: none;
    flex-shrink: 0;
}
.rbi-card:hover {
    background: rgba(255,255,255,0.11);
}
.rbi-card:hover .rbi-card-icon {
    color: #0DC2FF;
}
.rbi-card:hover .rbi-card-name {
    color: #fff;
}
.rbi-card-icon {
    font-size: 1.6rem;
    color: #0DC2FF;
    transition: color .15s;
    line-height: 1;
}
.rbi-card-name {
    font-family: 'Inter', sans-serif;
    font-size: 0.72rem;
    font-weight: 500;
    color: #fff;
    text-align: center;
    line-height: 1.35;
    transition: color .15s;
    word-break: break-word;
}

/* Empty state */
.rbi-empty {
    color: rgba(255,255,255,0.25);
    font-size: .875rem;
    font-family: 'Inter', sans-serif;
    padding: 1rem 0;
}

/* ── Config Modal ─────────────────────────────────────────────────────────── */
.rbi-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(6,25,32,0.72);
    backdrop-filter: blur(4px);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}
.rbi-overlay.open {
    display: flex;
}

.rbi-modal {
    background: #0d1e2d;
    border: 1.5px solid rgba(13,194,255,0.25);
    border-radius: 16px;
    padding: 1.75rem 1.75rem 1.5rem;
    width: 100%;
    max-width: 400px;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    animation: rbiPop .18s ease;
}
@keyframes rbiPop {
    from { transform: scale(.92); opacity: 0; }
    to   { transform: scale(1);   opacity: 1; }
}

.rbi-modal-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.rbi-modal-title {
    font-family: 'Rubik', sans-serif;
    font-size: 1rem;
    font-weight: 600;
    color: #fff;
}
.rbi-modal-close {
    background: none;
    border: none;
    color: rgba(255,255,255,0.40);
    font-size: 1.1rem;
    cursor: pointer;
    padding: 2px 4px;
    line-height: 1;
    transition: color .12s;
}
.rbi-modal-close:hover { color: #fff; }

.rbi-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.rbi-field-label {
    font-family: 'Rubik', sans-serif;
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: rgba(255,255,255,0.40);
}
.rbi-field-input {
    background: rgba(255,255,255,0.07);
    border: 1.5px solid rgba(255,255,255,0.12);
    border-radius: 8px;
    padding: .55rem .8rem;
    color: #fff;
    font-family: 'Inter', sans-serif;
    font-size: .875rem;
    outline: none;
    transition: border-color .15s;
    width: 100%;
    box-sizing: border-box;
}
.rbi-field-input:focus { border-color: #0DC2FF; }

/* Visibility toggle */
.rbi-vis-row {
    display: flex;
    gap: 8px;
}
.rbi-vis-btn {
    flex: 1;
    padding: .45rem .75rem;
    border-radius: 8px;
    border: 1.5px solid rgba(255,255,255,0.12);
    background: rgba(255,255,255,0.05);
    color: rgba(255,255,255,0.45);
    font-family: 'Inter', sans-serif;
    font-size: .8rem;
    font-weight: 500;
    cursor: pointer;
    transition: border-color .15s, background .15s, color .15s;
    text-align: center;
}
.rbi-vis-btn.active-vis {
    border-color: #0DC2FF;
    background: rgba(13,194,255,0.12);
    color: #0DC2FF;
    font-weight: 600;
}
.rbi-vis-btn.active-oculto {
    border-color: rgba(255,255,255,0.25);
    background: rgba(255,255,255,0.08);
    color: rgba(255,255,255,0.65);
    font-weight: 600;
}

/* Modal footer */
.rbi-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    padding-top: .25rem;
}
.rbi-btn-cancel {
    background: transparent;
    border: 1.5px solid rgba(255,255,255,0.15);
    border-radius: 8px;
    color: rgba(255,255,255,0.50);
    font-family: 'Inter', sans-serif;
    font-size: .82rem;
    font-weight: 500;
    padding: .45rem 1rem;
    cursor: pointer;
    transition: border-color .15s, color .15s;
}
.rbi-btn-cancel:hover { border-color: rgba(255,255,255,0.35); color: #fff; }

.rbi-btn-save {
    background: #0DC2FF;
    border: none;
    border-radius: 8px;
    color: #061920;
    font-family: 'Inter', sans-serif;
    font-size: .82rem;
    font-weight: 700;
    padding: .45rem 1.1rem;
    cursor: pointer;
    transition: background .15s;
}
.rbi-btn-save:hover    { background: #08aadd; }
.rbi-btn-save:disabled { opacity: .55; cursor: not-allowed; }

.rbi-btn-open {
    display: block;
    width: 100%;
    background: #0DC2FF;
    border: none;
    border-radius: 8px;
    color: #061920;
    font-family: 'Inter', sans-serif;
    font-size: .82rem;
    font-weight: 700;
    padding: .55rem 1rem;
    cursor: pointer;
    text-align: center;
    transition: background .15s;
}
.rbi-btn-open:hover { background: #08aadd; }
</style>

<div class="rbi-wrap">

    <!-- Page header -->
    <div class="rbi-page-header">
        <i class="ti ti-chart-bar rbi-page-icon"></i>
        <span class="rbi-page-title">Relatórios BI</span>
    </div>

    <!-- Cards -->
    <div class="rbi-cards-row" id="rbi-cards-row">
        <span class="rbi-empty">Carregando...</span>
    </div>

</div>

<!-- Config modal -->
<div class="rbi-overlay" id="rbi-overlay">
    <div class="rbi-modal" id="rbi-modal">
        <div class="rbi-modal-head">
            <span class="rbi-modal-title">Configurar relatório</span>
            <button class="rbi-modal-close" id="rbi-modal-close" title="Fechar">&times;</button>
        </div>

        <input type="hidden" id="rbi-edit-id">
        <input type="hidden" id="rbi-edit-slug">

        <div class="rbi-field">
            <label class="rbi-field-label">Nome amigável</label>
            <input type="text" class="rbi-field-input" id="rbi-edit-nome" autocomplete="off">
        </div>

        <div class="rbi-field">
            <label class="rbi-field-label">Visibilidade</label>
            <div class="rbi-vis-row">
                <button class="rbi-vis-btn" id="rbi-vis-visivel" data-val="true">Visível</button>
                <button class="rbi-vis-btn" id="rbi-vis-oculto"  data-val="false">Oculto</button>
            </div>
        </div>

        <button class="rbi-btn-open" id="rbi-btn-open">
            <i class="ti ti-external-link" style="margin-right:.35rem"></i>Abrir relatório
        </button>

        <div class="rbi-modal-footer">
            <button class="rbi-btn-cancel" id="rbi-btn-cancel">Cancelar</button>
            <button class="rbi-btn-save"   id="rbi-btn-save">Salvar</button>
        </div>
    </div>
</div>

<script>
(function () {
    const row     = document.getElementById('rbi-cards-row');
    const overlay = document.getElementById('rbi-overlay');
    const modal   = document.getElementById('rbi-modal');
    const editId   = document.getElementById('rbi-edit-id');
    const editSlug = document.getElementById('rbi-edit-slug');
    const editNome = document.getElementById('rbi-edit-nome');
    const btnSave  = document.getElementById('rbi-btn-save');
    const btnOpen  = document.getElementById('rbi-btn-open');
    let visivel    = true;

    function setVis(v) {
        visivel = v;
        document.getElementById('rbi-vis-visivel').className =
            'rbi-vis-btn' + (v ? ' active-vis' : '');
        document.getElementById('rbi-vis-oculto').className =
            'rbi-vis-btn' + (!v ? ' active-oculto' : '');
    }

    function openModal(card) {
        editId.value   = card.id;
        editSlug.value = card.slug;
        editNome.value = card.nome_amigavel;
        setVis(card.visivel !== false);
        overlay.classList.add('open');
        editNome.focus();
    }

    function closeModal() {
        overlay.classList.remove('open');
    }

    function buildCard(r) {
        const card = document.createElement('div');
        card.className = 'rbi-card';
        card.innerHTML =
            '<i class="ti ti-file-analytics rbi-card-icon"></i>' +
            '<span class="rbi-card-name">' + escHtml(r.nome_amigavel) + '</span>';

        // Card click — open config modal
        card.addEventListener('click', function () { openModal(r); });

        return card;
    }

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function loadCards() {
        row.innerHTML = '<span class="rbi-empty">Carregando...</span>';
        fetch('/api/relatorios-bi.php?action=list')
            .then(function (r) { return r.json(); })
            .then(function (res) {
                row.innerHTML = '';
                if (!res.success || !res.data.length) {
                    row.innerHTML = '<span class="rbi-empty">Nenhum relatório disponível.</span>';
                    return;
                }
                res.data.forEach(function (r) { row.appendChild(buildCard(r)); });
            })
            .catch(function () {
                row.innerHTML = '<span class="rbi-empty" style="color:#e53e3e">Erro ao carregar relatórios.</span>';
            });
    }

    // Visibility toggle buttons
    document.getElementById('rbi-vis-visivel').addEventListener('click', function () { setVis(true); });
    document.getElementById('rbi-vis-oculto').addEventListener('click',  function () { setVis(false); });

    // Close modal
    document.getElementById('rbi-modal-close').addEventListener('click', closeModal);
    document.getElementById('rbi-btn-cancel').addEventListener('click', closeModal);

    // Open report in new tab
    btnOpen.addEventListener('click', function () {
        const slug = editSlug.value;
        if (slug) window.open('https://app.kw24.com.br/relatorios-bi/' + slug, '_blank', 'noopener');
    });

    // Overlay click outside modal
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) closeModal();
    });

    // Save
    btnSave.addEventListener('click', function () {
        const nome = editNome.value.trim();
        if (!nome) { editNome.focus(); return; }

        btnSave.disabled = true;
        fetch('/api/relatorios-bi.php?action=update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: parseInt(editId.value), nome_amigavel: nome, visivel: visivel })
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.success) {
                editSlug.value = res.slug;
                closeModal();
                loadCards();
            } else {
                alert('Erro ao salvar: ' + (res.erro || 'desconhecido'));
            }
        })
        .catch(function () { alert('Erro de rede ao salvar.'); })
        .finally(function () { btnSave.disabled = false; });
    });

    // Load on init
    loadCards();
})();
</script>
