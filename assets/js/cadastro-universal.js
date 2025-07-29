/**
 * Sistema Universal de Cadastro
 * Funções genéricas reutilizáveis para todos os cadastros (clientes, contatos, aplicações, etc.)
 */

// =================== SISTEMA DE ALERTAS UNIVERSAL ===================

/**
 * Mostra alerta no topo da tela (estilo login)
 * @param {string} mensagem - Mensagem a ser exibida
 * @param {string} tipo - Tipo do alerta: 'success', 'error', 'warning', 'info'
 */
function mostrarAlertaUniversal(mensagem, tipo = 'success') {
    // Remove alerta anterior se existir
    const alertaAnterior = document.querySelector('.alert-top');
    if (alertaAnterior) {
        alertaAnterior.remove();
    }

    // Cria novo alerta
    const alerta = document.createElement('div');
    alerta.className = `alert-top alert-${tipo}`;
    
    // Define ícone baseado no tipo
    let icone;
    switch(tipo) {
        case 'success': icone = 'check-circle'; break;
        case 'error': icone = 'exclamation-triangle'; break;
        case 'warning': icone = 'exclamation-triangle'; break;
        case 'info': icone = 'info-circle'; break;
        default: icone = 'check-circle';
    }
    
    alerta.innerHTML = `
        <i class="fa fa-${icone}"></i>
        ${mensagem}
    `;

    // Adiciona ao body com animação
    document.body.appendChild(alerta);
    
    // Animação inicial (estilo login)
    alerta.style.opacity = '0';
    alerta.style.transform = 'translateY(-30px)';
    
    setTimeout(() => {
        alerta.style.transition = 'opacity 0.5s, transform 0.5s';
        alerta.style.opacity = '1';
        alerta.style.transform = 'translateY(0)';
    }, 100);

    // Remove após 4 segundos com animação suave
    setTimeout(() => {
        alerta.style.opacity = '0';
        alerta.style.transform = 'translateY(-30px)';
        setTimeout(() => {
            if (alerta && alerta.parentNode) {
                alerta.remove();
            }
        }, 500);
    }, 4000);
}

// =================== SISTEMA DE VERIFICAÇÃO DE MUDANÇAS UNIVERSAL ===================

/**
 * Verifica se houve alterações em um formulário
 * @param {HTMLFormElement} form - Formulário a ser verificado
 * @param {boolean} isCriacao - Se é modo criação (true) ou edição (false)
 * @param {string} campoObrigatorio - Nome do campo obrigatório para modo criação (ex: 'nome')
 * @returns {boolean} - true se houver alterações
 */
function verificarAlteracoesUniversal(form, isCriacao = false, campoObrigatorio = 'nome') {
    const inputs = form.querySelectorAll('input[type="text"]:not([disabled]), input[type="email"], textarea, select');
    let hasChanges = false;
    
    inputs.forEach(inp => {
        const orig = inp.getAttribute('data-original') || '';
        const atual = inp.value.trim();
        if (orig !== atual) {
            hasChanges = true;
        }
    });

    // Para modo criação, verifica se há dados no campo obrigatório
    if (isCriacao && !hasChanges) {
        const campoInput = form.querySelector(`input[name="${campoObrigatorio}"], textarea[name="${campoObrigatorio}"], select[name="${campoObrigatorio}"]`);
        if (campoInput && campoInput.value.trim()) {
            hasChanges = true;
        }
    }

    return hasChanges;
}

/**
 * Tenta fechar modal verificando alterações
 * @param {HTMLElement} modal - Modal a ser fechado
 * @param {HTMLFormElement} form - Formulário do modal
 * @param {string} tipoEntidade - Tipo da entidade (ex: 'contato', 'cliente', 'aplicacao')
 * @param {string} campoObrigatorio - Campo obrigatório para verificar criação
 */
function tentarFecharModalUniversal(modal, form, tipoEntidade, campoObrigatorio = 'nome') {
    console.log(`Tentando fechar modal de ${tipoEntidade}, verificando alterações...`);
    
    // Detecta se é modo criação
    const inputId = form.querySelector('input[disabled]');
    const isCriacao = !inputId;
    
    const hasChanges = verificarAlteracoesUniversal(form, isCriacao, campoObrigatorio);
    
    console.log('Tem alterações:', hasChanges);

    if (hasChanges) {
        // Mostra modal de confirmação customizado
        mostrarModalConfirmacaoUniversal(modal, tipoEntidade, isCriacao);
    } else {
        // Fecha diretamente se não há alterações
        modal.style.display = 'none';
    }
}

// =================== SISTEMA DE MODAL DE CONFIRMAÇÃO UNIVERSAL ===================

/**
 * Mostra modal de confirmação para salvamento/descarte
 * @param {HTMLElement} modalOriginal - Modal original que será fechado
 * @param {string} tipoEntidade - Tipo da entidade (ex: 'contato', 'cliente', 'aplicacao')
 * @param {boolean} isCriacao - Se é modo criação ou edição
 * @param {Function} funcaoSalvar - Função de callback para salvar (opcional, para edição)
 */
function mostrarModalConfirmacaoUniversal(modalOriginal, tipoEntidade, isCriacao, funcaoSalvar = null) {
    // Remove TODOS os modais de confirmação existentes
    const modaisAnteriores = document.querySelectorAll('.modal-confirmacao-salvar');
    modaisAnteriores.forEach(modal => modal.remove());

    // Define textos baseados no tipo e modo
    const textos = {
        titulo: isCriacao ? 'Descartar dados inseridos?' : 'Alterações não salvas',
        mensagem: isCriacao 
            ? `Você preencheu alguns dados do ${tipoEntidade}. O que deseja fazer?`
            : `Você fez alterações no ${tipoEntidade} que não foram salvas. O que deseja fazer?`,
        btnContinuar: isCriacao ? 'Continuar preenchendo' : 'Continuar editando'
    };

    // Cria modal de confirmação
    const modalConfirmacao = document.createElement('div');
    modalConfirmacao.id = `modal-confirmacao-salvar-${tipoEntidade}`;
    modalConfirmacao.className = 'modal-confirmacao-salvar';
    modalConfirmacao.innerHTML = `
        <div class="modal-confirmacao-overlay"></div>
        <div class="modal-confirmacao-content">
            <div class="modal-confirmacao-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>${textos.titulo}</h3>
            </div>
            <div class="modal-confirmacao-body">
                <p>${textos.mensagem}</p>
            </div>
            <div class="modal-confirmacao-footer">
                ${!isCriacao && funcaoSalvar ? `
                    <button type="button" id="btn-salvar-e-fechar-${tipoEntidade}" class="btn-salvar-e-fechar">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                ` : ''}
                <button type="button" id="btn-descartar-e-fechar-${tipoEntidade}" class="btn-descartar-e-fechar">
                    <i class="fas fa-times"></i> Descartar
                </button>
                <button type="button" id="btn-cancelar-fechamento-${tipoEntidade}" class="btn-cancelar-fechamento">
                    <i class="fas fa-arrow-left"></i> ${textos.btnContinuar}
                </button>
            </div>
        </div>
    `;

    // Adiciona ao body
    document.body.appendChild(modalConfirmacao);

    // Força o reflow antes de adicionar a classe show
    modalConfirmacao.offsetHeight;

    // Mostra o modal
    setTimeout(() => {
        modalConfirmacao.classList.add('show');
    }, 10);

    // Event listeners
    const btnSalvarEFechar = document.getElementById(`btn-salvar-e-fechar-${tipoEntidade}`);
    const btnDescartarEFechar = document.getElementById(`btn-descartar-e-fechar-${tipoEntidade}`);
    const btnCancelarFechamento = document.getElementById(`btn-cancelar-fechamento-${tipoEntidade}`);

    // Salvar e fechar (apenas para edição)
    if (btnSalvarEFechar && !isCriacao && funcaoSalvar) {
        btnSalvarEFechar.addEventListener('click', function() {
            // Mostra loader enquanto salva
            btnSalvarEFechar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
            btnSalvarEFechar.disabled = true;
            
            // Chama a função de salvar passada como parâmetro
            funcaoSalvar();
            modalConfirmacao.remove();
        });
    }

    // Descartar e fechar
    if (btnDescartarEFechar) {
        btnDescartarEFechar.addEventListener('click', function() {
            modalOriginal.style.display = 'none';
            modalConfirmacao.remove();
        });
    }

    // Cancelar fechamento (continuar editando/preenchendo)
    if (btnCancelarFechamento) {
        btnCancelarFechamento.addEventListener('click', function() {
            modalConfirmacao.remove();
        });
    }

    // Fechar ao clicar fora
    const overlay = modalConfirmacao.querySelector('.modal-confirmacao-overlay');
    if (overlay) {
        overlay.addEventListener('click', function() {
            modalConfirmacao.remove();
        });
    }
}

// =================== SISTEMA DE MONITORAMENTO DE MUDANÇAS UNIVERSAL ===================

/**
 * Configura monitoramento de mudanças em formulário para mostrar/ocultar botões de ação
 * @param {HTMLFormElement} form - Formulário a monitorar
 * @param {HTMLElement} modalActions - Container dos botões de ação
 * @param {boolean} isCriacao - Se é modo criação
 */
function configurarMonitoramentoMudancasUniversal(form, modalActions, isCriacao) {
    if (isCriacao || !modalActions) return; // Não monitora no modo criação
    
    const inputs = form.querySelectorAll('input[type="text"]:not([disabled]), input[type="email"], textarea, select');
    
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            const hasChanges = verificarAlteracoesUniversal(form, false);
            
            if (hasChanges && modalActions.style.display === 'none') {
                modalActions.style.display = 'flex';
                modalActions.style.opacity = '0';
                setTimeout(() => {
                    modalActions.style.opacity = '1';
                }, 10);
            } else if (!hasChanges && modalActions.style.display !== 'none') {
                modalActions.style.opacity = '0';
                setTimeout(() => {
                    modalActions.style.display = 'none';
                }, 300);
            }
        });
    });
}

// =================== SISTEMA DE RESET DE DADOS ORIGINAIS UNIVERSAL ===================

/**
 * Reseta os atributos data-original após salvar com sucesso
 * @param {HTMLFormElement} form - Formulário que foi salvo
 */
function resetarDadosOriginaisUniversal(form) {
    const inputs = form.querySelectorAll('input[type="text"]:not([disabled]), input[type="email"], textarea, select');
    inputs.forEach(input => {
        input.setAttribute('data-original', input.value.trim());
    });
}

// =================== SISTEMA DE EVENTOS UNIVERSAIS PARA MODAL ===================

/**
 * Configura eventos universais para modal de cadastro
 * @param {Object} config - Configuração do modal
 * @param {HTMLElement} config.modal - Modal principal
 * @param {HTMLFormElement} config.form - Formulário do modal
 * @param {string} config.tipoEntidade - Tipo da entidade
 * @param {boolean} config.isCriacao - Se é modo criação
 * @param {Function} config.funcaoSalvar - Função para salvar (edição)
 * @param {Function} config.funcaoCriar - Função para criar (criação)
 * @param {string} config.campoObrigatorio - Campo obrigatório
 * @param {string} config.mensagemCampoObrigatorio - Mensagem de validação
 */
function configurarEventosModalUniversal(config) {
    const {
        modal, form, tipoEntidade, isCriacao,
        funcaoSalvar, funcaoCriar, campoObrigatorio = 'nome',
        mensagemCampoObrigatorio = `O ${campoObrigatorio} é obrigatório!`
    } = config;
    
    const modalActions = document.getElementById('modal-actions');
    const btnSalvar = document.getElementById('btn-salvar-modal');
    const btnCancelar = document.getElementById('btn-cancelar-modal');
    
    // Configura monitoramento de mudanças
    configurarMonitoramentoMudancasUniversal(form, modalActions, isCriacao);
    
    // Botão salvar/criar
    if (btnSalvar) {
        btnSalvar.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (isCriacao) {
                // Validação para criação
                const campoInput = form.querySelector(`input[name="${campoObrigatorio}"], textarea[name="${campoObrigatorio}"], select[name="${campoObrigatorio}"]`);
                if (!campoInput.value.trim()) {
                    mostrarAlertaUniversal(mensagemCampoObrigatorio, 'error');
                    campoInput.focus();
                    return;
                }
                if (funcaoCriar) funcaoCriar(form, modal);
            } else {
                // Validação para edição
                const hasChanges = verificarAlteracoesUniversal(form, false);
                
                if (hasChanges) {
                    if (funcaoSalvar) funcaoSalvar(form, modal);
                } else {
                    modal.style.display = 'none';
                }
            }
        });
    }
    
    // Botão cancelar
    if (btnCancelar) {
        btnCancelar.addEventListener('click', function() {
            tentarFecharModalUniversal(modal, form, tipoEntidade, campoObrigatorio);
        });
    }
    
    // Botão fechar (X)
    const btnFechar = document.querySelector('.contato-detail-close, .cliente-detail-close, .aplicacao-detail-close');
    if (btnFechar) {
        btnFechar.onclick = function() {
            tentarFecharModalUniversal(modal, form, tipoEntidade, campoObrigatorio);
        };
    }
    
    // Fechar ao clicar fora da área
    const overlay = modal.querySelector('.contato-detail-overlay, .cliente-detail-overlay, .aplicacao-detail-overlay');
    if (overlay) {
        overlay.addEventListener('click', function() {
            tentarFecharModalUniversal(modal, form, tipoEntidade, campoObrigatorio);
        });
    }
}

// =================== FUNÇÕES DE CALLBACK PARA INTEGRAÇÃO ===================

/**
 * Cria uma função de salvar que usa o sistema universal
 * @param {Function} funcaoSalvarOriginal - Função original de salvar
 * @returns {Function} - Função de callback para usar no modal de confirmação
 */
function criarCallbackSalvarUniversal(funcaoSalvarOriginal) {
    return function() {
        const form = document.getElementById('contato-form') || 
                     document.getElementById('cliente-form') || 
                     document.getElementById('aplicacao-form');
        const modal = document.getElementById('contato-detail-modal') || 
                      document.getElementById('cliente-detail-modal') || 
                      document.getElementById('aplicacao-detail-modal');
        
        if (form && modal && funcaoSalvarOriginal) {
            funcaoSalvarOriginal(form, modal);
        }
    };
}

// Torna as funções disponíveis globalmente
window.CadastroUniversal = {
    mostrarAlerta: mostrarAlertaUniversal,
    verificarAlteracoes: verificarAlteracoesUniversal,
    tentarFecharModal: tentarFecharModalUniversal,
    mostrarModalConfirmacao: mostrarModalConfirmacaoUniversal,
    configurarMonitoramentoMudancas: configurarMonitoramentoMudancasUniversal,
    resetarDadosOriginais: resetarDadosOriginaisUniversal,
    configurarEventosModal: configurarEventosModalUniversal,
    criarCallbackSalvar: criarCallbackSalvarUniversal
};
