<?php
if (!defined('SYSTEM_ACCESS') && !isset($user_data)) {
    header('Location: /public/login.php'); exit;
}
require_once __DIR__ . '/../helpers/Database.php';

try {
    $db = Database::getInstance();

    $app   = $db->fetchOne("SELECT id FROM aplicacoes WHERE slug = 'nimbus_parceiros'");
    $appId = $app ? (int)$app['id'] : null;

    $configs = [];
    if ($appId) {
        $configs = $db->fetchAll("
            SELECT ca.id         AS ca_id,
                   ca.chave,
                   ca.webhook_bitrix AS webhook,
                   ca.valor,
                   ca.ativo,
                   ca.descricao,
                   ca.config_extra,
                   ca.created_at,
                   c.id         AS cliente_id,
                   c.nome       AS cliente_nome
            FROM cliente_aplicacoes ca
            JOIN clientes c ON c.id = ca.cliente_id
            WHERE ca.aplicacao_id = :app_id
            ORDER BY ca.ativo DESC, c.nome ASC
        ", ['app_id' => $appId]);
    }

    $clientes = $db->fetchAll(
        "SELECT id, nome FROM clientes WHERE ativo = TRUE ORDER BY nome ASC"
    );

} catch (Exception $e) {
    echo '<div style="color:#e53e3e;padding:2rem">Erro: ' . htmlspecialchars($e->getMessage()) . '</div>';
    return;
}

$diasLabel = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
?>

<div class="page-header">
    <h1 class="page-title"><i class="fas fa-users"></i> Nimbus Partners Report</h1>
    <div class="page-header-actions">
        <?php if ($appId): ?>
        <button onclick="nprAbrirCadastro()"
                style="padding:.45rem 1rem;font-size:.85rem;border:none;border-radius:8px;background:#0DC2FF;color:#fff;font-weight:600;cursor:pointer">
            <i class="fas fa-plus"></i> Adicionar
        </button>
        <?php endif; ?>
    </div>
</div>

<?php if (!$appId): ?>
<div style="padding:2rem;background:#fff5f5;border-radius:10px;color:#c53030;font-size:.875rem">
    <i class="fas fa-exclamation-triangle"></i>
    App <code>nimbus_parceiros</code> não encontrada no banco. Execute a migration <code>20260630_nimbus_parceiros_app.sql</code>.
</div>

<?php elseif (empty($configs)): ?>
<div style="text-align:center;padding:4rem 2rem;color:#a0aec0">
    <i class="fas fa-users" style="font-size:2.5rem;display:block;margin-bottom:1rem;opacity:.4"></i>
    <p style="margin:0 0 1rem;font-size:.9rem">Nenhuma configuração cadastrada ainda.</p>
    <button onclick="nprAbrirCadastro()"
            style="padding:.55rem 1.25rem;border:none;border-radius:8px;background:#0DC2FF;color:#fff;font-size:.85rem;font-weight:600;cursor:pointer">
        <i class="fas fa-plus"></i> Adicionar configuração
    </button>
</div>

<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(380px,1fr));gap:1rem">
    <?php foreach ($configs as $cfg):
        $extra   = json_decode($cfg['config_extra'] ?? '{}', true) ?? [];
        $dias    = $extra['dias_semana'] ?? [];
        $hora    = $extra['horario']     ?? null;
        $diasStr = empty($dias)
            ? '—'
            : implode(', ', array_map(fn($d) => $diasLabel[(int)$d] ?? "Dia $d", $dias));
    ?>
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden"
         id="npm-card-<?= $cfg['ca_id'] ?>">

        <!-- cabeçalho do card -->
        <div style="padding:.9rem 1.25rem;border-bottom:1px solid #f0f4f8;display:flex;align-items:center;justify-content:space-between">
            <div>
                <div style="font-weight:700;font-size:.95rem;color:#1a202c">
                    <?= htmlspecialchars($cfg['cliente_nome']) ?>
                </div>
                <div style="font-size:.75rem;color:#a0aec0">
                    <?= htmlspecialchars($cfg['descricao'] ?? '—') ?>
                </div>
            </div>
            <span style="font-size:.72rem;font-weight:700;padding:.2rem .55rem;border-radius:20px;
                background:<?= $cfg['ativo'] ? '#f0fff4' : '#fff5f5' ?>;
                color:<?= $cfg['ativo'] ? '#276749' : '#c53030' ?>">
                <?= $cfg['ativo'] ? 'Ativo' : 'Inativo' ?>
            </span>
        </div>

        <!-- corpo do card -->
        <div style="padding:1rem 1.25rem;display:grid;gap:.65rem;font-size:.82rem">

            <div>
                <div style="color:#a0aec0;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.25rem">
                    Chave de acesso
                </div>
                <div style="display:flex;align-items:center;gap:.4rem">
                    <code style="font-size:.78rem;color:#2d3748;background:#f7fafc;padding:.25rem .5rem;
                                 border-radius:4px;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        <?= htmlspecialchars($cfg['chave'] ?? '—') ?>
                    </code>
                    <?php if ($cfg['chave']): ?>
                    <button onclick="nprCopiar(<?= json_encode($cfg['chave']) ?>, this)"
                            style="border:1px solid #e2e8f0;background:#fff;border-radius:6px;padding:.2rem .45rem;
                                   cursor:pointer;font-size:.75rem;color:#4a5568;flex-shrink:0"
                            title="Copiar chave">
                        <i class="fas fa-copy"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.4rem;color:#4a5568">
                <div>
                    <i class="fas fa-calendar-week" style="width:13px;color:#a0aec0"></i>
                    Dias: <strong><?= htmlspecialchars($diasStr) ?></strong>
                </div>
                <div>
                    <i class="fas fa-clock" style="width:13px;color:#a0aec0"></i>
                    Horário: <strong><?= $hora ? htmlspecialchars($hora) : '—' ?></strong>
                </div>
                <div>
                    <i class="fas fa-dollar-sign" style="width:13px;color:#a0aec0"></i>
                    Valor: <strong>
                        <?= $cfg['valor']
                            ? 'R$ ' . number_format((float)$cfg['valor'], 2, ',', '.')
                            : '—' ?>
                    </strong>
                </div>
                <div>
                    <i class="fas fa-link" style="width:13px;color:#a0aec0"></i>
                    Webhook:
                    <strong style="color:<?= $cfg['webhook'] ? '#2d3748' : '#e53e3e' ?>">
                        <?= $cfg['webhook'] ? 'Configurado' : 'Não informado' ?>
                    </strong>
                </div>
            </div>
        </div>

        <!-- rodapé do card -->
        <div style="padding:.75rem 1.25rem;border-top:1px solid #f0f4f8;display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
            <button onclick="nprDisparar(<?= $cfg['ca_id'] ?>, this)"
                    style="padding:.38rem .85rem;border:none;border-radius:8px;background:#0DC2FF;
                           color:#fff;font-size:.8rem;cursor:pointer;font-weight:600">
                <i class="fas fa-play"></i> Disparar agora
            </button>
            <button onclick="nprAbrirEditar(<?= $cfg['ca_id'] ?>)"
                    style="padding:.38rem .85rem;border:1px solid #e2e8f0;border-radius:8px;
                           background:#fff;color:#4a5568;font-size:.8rem;cursor:pointer">
                <i class="fas fa-edit"></i> Editar
            </button>
            <span id="npr-msg-<?= $cfg['ca_id'] ?>"
                  style="font-size:.78rem;margin-left:.25rem;flex:1;min-width:0"></span>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ==================== Overlay: Cadastro ==================== -->
<div id="npr-cadastro-overlay"
     style="display:none;position:fixed;inset:0;background:rgba(6,25,32,.6);
            backdrop-filter:blur(4px);z-index:9999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:16px;padding:2rem;width:460px;max-width:92vw;
                box-shadow:0 24px 60px rgba(0,0,0,.25);animation:kwPop .18s ease">
        <h3 style="font-family:'Rubik',sans-serif;font-size:1rem;font-weight:700;color:#1a202c;margin:0 0 1.25rem">
            Nova configuração
        </h3>
        <div style="display:grid;gap:.75rem">
            <div>
                <label style="display:block;font-size:.72rem;font-weight:700;color:#4a5568;
                              text-transform:uppercase;letter-spacing:.05em;margin-bottom:.35rem">
                    Cliente *
                </label>
                <select id="npr-cad-cliente"
                        style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:.6rem .75rem;
                               font-size:.875rem;color:#2d3748;background:#fff;outline:none;box-sizing:border-box">
                    <option value="">Selecione...</option>
                    <?php foreach ($clientes as $cl): ?>
                    <option value="<?= $cl['id'] ?>"><?= htmlspecialchars($cl['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="display:block;font-size:.72rem;font-weight:700;color:#4a5568;
                              text-transform:uppercase;letter-spacing:.05em;margin-bottom:.35rem">
                    Webhook Bitrix24 *
                </label>
                <input id="npr-cad-webhook" type="url" placeholder="https://..."
                       style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:.6rem .75rem;
                              font-size:.875rem;color:#2d3748;outline:none;box-sizing:border-box">
            </div>
            <div>
                <label style="display:block;font-size:.72rem;font-weight:700;color:#4a5568;
                              text-transform:uppercase;letter-spacing:.05em;margin-bottom:.35rem">
                    Descrição *
                </label>
                <input id="npr-cad-descricao" type="text" placeholder="Ex: Nimbus TAX – Parceiros"
                       style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:.6rem .75rem;
                              font-size:.875rem;color:#2d3748;outline:none;box-sizing:border-box">
            </div>
            <p id="npr-cad-erro" style="color:#e53e3e;font-size:.8rem;margin:0;display:none"></p>
        </div>
        <div style="display:flex;gap:.75rem;margin-top:1.25rem">
            <button onclick="nprFecharCadastro()"
                    style="flex:1;padding:.65rem;border:1px solid #e2e8f0;border-radius:8px;
                           background:#fff;color:#718096;font-size:.875rem;cursor:pointer;font-weight:500">
                Cancelar
            </button>
            <button id="npr-cad-btn" onclick="nprSalvarCadastro()"
                    style="flex:1;padding:.65rem;border:none;border-radius:8px;
                           background:#0DC2FF;color:#fff;font-size:.875rem;cursor:pointer;font-weight:700">
                Salvar
            </button>
        </div>
    </div>
</div>

<!-- ==================== Overlay: Editar ==================== -->
<div id="npr-editar-overlay"
     style="display:none;position:fixed;inset:0;background:rgba(6,25,32,.6);
            backdrop-filter:blur(4px);z-index:9999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:16px;padding:2rem;width:480px;max-width:92vw;
                box-shadow:0 24px 60px rgba(0,0,0,.25);animation:kwPop .18s ease">
        <h3 id="npr-edit-title"
            style="font-family:'Rubik',sans-serif;font-size:1rem;font-weight:700;color:#1a202c;margin:0 0 1.25rem">
            Editar configuração
        </h3>
        <input type="hidden" id="npr-edit-ca-id">
        <div style="display:grid;gap:.75rem">
            <div>
                <label style="display:block;font-size:.72rem;font-weight:700;color:#4a5568;
                              text-transform:uppercase;letter-spacing:.05em;margin-bottom:.35rem">
                    Webhook Bitrix24
                </label>
                <input id="npr-edit-webhook" type="url" placeholder="https://..."
                       style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:.6rem .75rem;
                              font-size:.875rem;color:#2d3748;outline:none;box-sizing:border-box">
            </div>
            <div>
                <label style="display:block;font-size:.72rem;font-weight:700;color:#4a5568;
                              text-transform:uppercase;letter-spacing:.05em;margin-bottom:.35rem">
                    Descrição
                </label>
                <input id="npr-edit-descricao" type="text"
                       style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:.6rem .75rem;
                              font-size:.875rem;color:#2d3748;outline:none;box-sizing:border-box">
            </div>
            <div>
                <label style="display:block;font-size:.72rem;font-weight:700;color:#4a5568;
                              text-transform:uppercase;letter-spacing:.05em;margin-bottom:.35rem">
                    Dias da semana
                </label>
                <div style="display:flex;gap:.4rem;flex-wrap:wrap">
                    <?php foreach ($diasLabel as $idx => $nome): ?>
                    <label style="display:flex;align-items:center;gap:.3rem;font-size:.82rem;color:#4a5568;
                                  cursor:pointer;background:#f7fafc;border:1px solid #e2e8f0;
                                  border-radius:6px;padding:.3rem .55rem">
                        <input type="checkbox" class="npr-dia-cb" value="<?= $idx ?>">
                        <?= $nome ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
                <div>
                    <label style="display:block;font-size:.72rem;font-weight:700;color:#4a5568;
                                  text-transform:uppercase;letter-spacing:.05em;margin-bottom:.35rem">
                        Horário
                    </label>
                    <input id="npr-edit-horario" type="time"
                           style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:.6rem .75rem;
                                  font-size:.875rem;color:#2d3748;outline:none;box-sizing:border-box">
                </div>
                <div>
                    <label style="display:block;font-size:.72rem;font-weight:700;color:#4a5568;
                                  text-transform:uppercase;letter-spacing:.05em;margin-bottom:.35rem">
                        Valor (R$)
                    </label>
                    <input id="npr-edit-valor" type="number" step="0.01" min="0" placeholder="Opcional"
                           style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:.6rem .75rem;
                                  font-size:.875rem;color:#2d3748;outline:none;box-sizing:border-box">
                </div>
            </div>
            <p id="npr-edit-erro" style="color:#e53e3e;font-size:.8rem;margin:0;display:none"></p>
        </div>
        <div style="display:flex;gap:.75rem;margin-top:1.25rem">
            <button onclick="nprFecharEditar()"
                    style="flex:1;padding:.65rem;border:1px solid #e2e8f0;border-radius:8px;
                           background:#fff;color:#718096;font-size:.875rem;cursor:pointer;font-weight:500">
                Cancelar
            </button>
            <button id="npr-edit-btn" onclick="nprSalvarEditar()"
                    style="flex:1;padding:.65rem;border:none;border-radius:8px;
                           background:#0DC2FF;color:#fff;font-size:.875rem;cursor:pointer;font-weight:700">
                Salvar
            </button>
        </div>
    </div>
</div>

<script>
// Mapa de configurações carregadas do PHP (por ca_id)
const nprConfigs = <?= json_encode(
    array_column($configs, null, 'ca_id'),
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP
) ?>;
const NPR_APP_ID = <?= $appId ?? 'null' ?>;

// ——— Utilitários ———
function nprCopiar(texto, btn) {
    navigator.clipboard.writeText(texto).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        btn.style.color = '#38a169';
        setTimeout(() => { btn.innerHTML = orig; btn.style.color = ''; }, 1500);
    });
}

// ——— Cadastro ———
function nprAbrirCadastro() {
    document.getElementById('npr-cad-cliente').value  = '';
    document.getElementById('npr-cad-webhook').value  = '';
    document.getElementById('npr-cad-descricao').value = '';
    document.getElementById('npr-cad-erro').style.display = 'none';
    const ov = document.getElementById('npr-cadastro-overlay');
    ov.style.display = 'flex';
}
function nprFecharCadastro() {
    document.getElementById('npr-cadastro-overlay').style.display = 'none';
}
function nprSalvarCadastro() {
    const clienteId = document.getElementById('npr-cad-cliente').value;
    const webhook   = document.getElementById('npr-cad-webhook').value.trim();
    const descricao = document.getElementById('npr-cad-descricao').value.trim();
    const erro      = document.getElementById('npr-cad-erro');
    const btn       = document.getElementById('npr-cad-btn');

    if (!clienteId || !webhook || !descricao) {
        erro.textContent = 'Todos os campos são obrigatórios.';
        erro.style.display = 'block';
        return;
    }
    erro.style.display = 'none';
    btn.disabled = true; btn.textContent = 'Salvando...';

    fetch('/api/cliente-ativar-app.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({
            cliente_id:    parseInt(clienteId),
            aplicacao_id:  NPR_APP_ID,
            webhook_bitrix: webhook,
            descricao:     descricao
        })
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false; btn.textContent = 'Salvar';
        if (data.erro) {
            erro.textContent = data.erro;
            erro.style.display = 'block';
            return;
        }
        nprFecharCadastro();
        location.reload();
    })
    .catch(() => {
        btn.disabled = false; btn.textContent = 'Salvar';
        erro.textContent = 'Erro de conexão.';
        erro.style.display = 'block';
    });
}

// ——— Editar ———
function nprAbrirEditar(caId) {
    const cfg   = nprConfigs[caId] || {};
    const extra = cfg.config_extra ? JSON.parse(cfg.config_extra) : {};

    document.getElementById('npr-edit-ca-id').value     = caId;
    document.getElementById('npr-edit-title').textContent =
        'Editar — ' + (cfg.cliente_nome || '');
    document.getElementById('npr-edit-webhook').value   = cfg.webhook  || '';
    document.getElementById('npr-edit-descricao').value = cfg.descricao || '';
    document.getElementById('npr-edit-horario').value   = extra.horario || '';
    document.getElementById('npr-edit-valor').value     = cfg.valor    || '';
    document.getElementById('npr-edit-erro').style.display = 'none';

    const dias = extra.dias_semana || [];
    document.querySelectorAll('.npr-dia-cb').forEach(cb => {
        cb.checked = dias.includes(parseInt(cb.value));
    });

    document.getElementById('npr-editar-overlay').style.display = 'flex';
}
function nprFecharEditar() {
    document.getElementById('npr-editar-overlay').style.display = 'none';
}
function nprSalvarEditar() {
    const caId    = parseInt(document.getElementById('npr-edit-ca-id').value);
    const webhook = document.getElementById('npr-edit-webhook').value.trim();
    const desc    = document.getElementById('npr-edit-descricao').value.trim();
    const horario = document.getElementById('npr-edit-horario').value;
    const valor   = document.getElementById('npr-edit-valor').value;
    const erro    = document.getElementById('npr-edit-erro');
    const btn     = document.getElementById('npr-edit-btn');

    const dias = [];
    document.querySelectorAll('.npr-dia-cb:checked').forEach(cb => {
        dias.push(parseInt(cb.value));
    });

    erro.style.display = 'none';
    btn.disabled = true; btn.textContent = 'Salvando...';

    fetch('/api/nimbus-config-salvar.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ ca_id: caId, webhook, descricao: desc, dias_semana: dias, horario, valor })
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false; btn.textContent = 'Salvar';
        if (data.erro) {
            erro.textContent = data.erro;
            erro.style.display = 'block';
            return;
        }
        nprFecharEditar();
        location.reload();
    })
    .catch(() => {
        btn.disabled = false; btn.textContent = 'Salvar';
        erro.textContent = 'Erro de conexão.';
        erro.style.display = 'block';
    });
}

// ——— Disparar agora ———
function nprDisparar(caId, btn) {
    const msg = document.getElementById('npr-msg-' + caId);
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Executando...';
    msg.style.color = '#718096';
    msg.textContent = 'Aguarde, pode levar alguns minutos...';

    fetch('/api/nimbus-executar.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ ca_id: caId })
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-play"></i> Disparar agora';
        if (data.sucesso) {
            msg.style.color = '#38a169';
            msg.textContent = '✓ ' + (data.message || 'Executado com sucesso');
        } else {
            msg.style.color = '#e53e3e';
            msg.textContent = '✗ ' + (data.erro || data.message || 'Erro desconhecido');
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-play"></i> Disparar agora';
        msg.style.color = '#e53e3e';
        msg.textContent = '✗ Erro de conexão';
    });
}
</script>
