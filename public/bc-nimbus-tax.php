<?php
/**
 * bc-nimbus-tax.php — Automações Bitrix24 — Nimbus TAX
 * Included from base-conhecimento.php (empresa=nimbus-tax)
 * and transparently from base-conhecimento-public.php via the same include chain.
 *
 * Tag mapping applied to source nimbus_tax_automacoes.html:
 *   tag-modelo  — Robô / BP / Automação de Etapa / Nativo (blue)
 *   tag-etapa   — Gatilho / "Via X" / tempo (green)
 *   tag-api-kw  — API própria KW24 (purple)
 *   tag-api-ext — API externa (ClickSign, Receita Federal, etc.) (amber)
 */

/* ── PHP helpers ──────────────────────────────────────────────────────────── */
function _ntSection($title, $badge, $badgeClass, $body, $toggle = true) { ?>
<div class="section">
    <div class="section-header<?= $toggle ? '' : ' no-toggle' ?>"<?= $toggle ? ' onclick="bcAuto.toggle(this)"' : '' ?>>
        <div class="section-title"><?= $title ?> <span class="section-badge <?= $badgeClass ?>"><?= $badge ?></span></div>
        <?php if ($toggle): ?><div class="section-toggle">▾</div><?php endif; ?>
    </div>
    <div class="section-body<?= $toggle ? '' : ' open' ?>"><?= $body ?></div>
</div>
<?php }

function _ntItem($name, $desc, $tags, $variant = '') { ?>
<div class="auto-item<?= $variant ? " $variant" : '' ?>">
    <div class="auto-name"><?= $name ?></div>
    <div class="auto-desc"><?= $desc ?></div>
    <?= $tags ?>
</div>
<?php }

function _ntTag($class, $label) {
    return '<span class="tag ' . $class . '">' . $label . '</span>';
}

function _ntEmpty($text = 'A preencher') {
    return '<div class="bc-empty">' . $text . '</div>';
}

function _ntAsideBox($title, $titleClass, $items) { ?>
<div class="aside-box">
    <div class="aside-title<?= $titleClass ? " $titleClass" : '' ?>" onclick="bcAuto.toggleAside(this)">
        <?= $title ?><span class="aside-title-arrow collapsed">▾</span>
    </div>
    <div class="aside-body collapsed">
        <?= $items ?>
    </div>
</div>
<?php }

function _ntAsideItem($name, $desc) { ?>
<div class="aside-item">
    <div class="aside-item-header" onclick="bcAuto.toggleField(this)">
        <div class="aside-item-name"><?= $name ?></div>
        <div class="aside-item-arrow">▾</div>
    </div>
    <div class="aside-item-desc"><?= $desc ?></div>
</div>
<?php }

function _ntCampoItem($name, $desc) { ?>
<div class="aside-item campos-item">
    <div class="aside-item-header" onclick="bcAuto.toggleField(this)">
        <div class="aside-item-name"><?= $name ?></div>
        <div class="aside-item-arrow">▾</div>
    </div>
    <div class="aside-item-desc"><?= $desc ?></div>
</div>
<?php }

/* ── Standard aside items (shared across most funnels) ────────────────────── */
function _ntStdAside($extras = '') {
    _ntAsideItem('Resumo do Negócio', 'Acionada sempre que o campo Comentário Resumo é preenchido e salvo. Captura a data, o nome do responsável e o texto digitado, e insere esse registro no topo do campo Resumo — mantendo o histórico de todos os comentários anteriores abaixo. Após gravar, o campo Comentário Resumo é limpo automaticamente para o próximo uso.<br><br>O campo Resumo é protegido contra edição manual: caso seja alterado diretamente, o sistema reverte ao conteúdo anterior de forma automática.');
    _ntAsideItem('Controle de Etapas', 'Fluxo de registro executado a cada mudança de etapa. Grava automaticamente no card: a data e hora da alteração, o nome do responsável pela mudança, e a transição de etapa (de qual etapa saiu e para qual foi). Permite rastrear o histórico completo de movimentações do card.');
    _ntAsideItem('Controle de Retorno de API', 'Fluxo que roda "por movimento" e fica monitorando os campos de retorno de API do card. Quando qualquer API externa registra um retorno, este fluxo identifica o tipo de retorno e executa a ação correspondente — funcionando como o receptor central de callbacks de todas as integrações do funil.');
    _ntAsideItem('Controle de Gatilhos por Etapa', 'Fluxo de otimização que monitora a etapa atual do card e decide quais fluxos de movimento devem ser executados. Como os gatilhos "por movimento" rodam a cada alteração de campo, percorrer fluxos extensos sem executar nada gera lentidão desnecessária. Este controle interrompe a execução dos fluxos pesados logo no início quando a etapa não requer processamento — permitindo que apenas os fluxos básicos (como Controle de Etapas) rodem. Em etapas de entrada, como um lead recém-criado, nenhum fluxo pesado é acionado.');
    _ntAsideItem('Controle de Nome do Card', 'Fluxo que padroniza automaticamente o nome do card conforme o funil e a etapa em que se encontra. Cada funil pode ter regras de construção de nome diferentes — o nome nunca é escrito manualmente, sempre é gerado e atualizado por este fluxo.');
    _ntAsideItem('Exclusão Automática (Lixeira)', 'Nenhum usuário tem permissão para excluir um card diretamente. Quando um card é movido para a etapa <strong>Lixeira</strong>, ele permanece lá por 3 dias e depois é excluído automaticamente pelo sistema. Isso garante um período de segurança antes da exclusão definitiva, evitando perdas acidentais.');
    echo $extras;
}
?>


<!-- ██████████████████████████████████████████████████████████████████████
     LEAD
█████████████████████████████████████████████████████████████████████ -->
<div class="bc-page active" id="bc-nt-lead">

<div class="bc-page-header">
    <h1>Lead</h1>
    <div class="bc-page-meta">Entidade: Lead (entityType 1) · Porta de entrada do funil comercial</div>
    <div class="bc-page-intro">
        O funil de Lead é a <strong>porta de entrada do processo comercial</strong>. Todo contato que demonstra potencial vira um lead aqui antes de qualquer avanço.<br><br>
        Os leads chegam por dois caminhos: importação de listas em Excel diretamente no Bitrix24, ou via campanhas externas integradas por API — que alimentam o funil automaticamente sem intervenção manual.<br><br>
        Daqui o time de SDR conduz uma cadência estruturada de e-mails e tarefas, com controle de filas de disparo, acompanhamento de visualizações e respostas, e gestão de contatos futuros. O objetivo é qualificar o lead e, quando isso acontece, um novo card é criado automaticamente no funil Closer para dar continuidade à negociação.
    </div>
</div>

<div class="bc-page-layout">
<div class="bc-page-stages">

<?php _ntSection('Prospect', 'Etapa', 'badge-stage', ob_start() . ob_get_clean() . implode('', [
    ob_start() . _ntItem('Entrada por Importe de Lista (Excel)',
        'Leads frios são importados manualmente via a ferramenta nativa de importação do Bitrix24, a partir de uma planilha Excel com os contatos mapeados.',
        _ntTag('tag-modelo','Nativo Bitrix24')) . ob_get_clean(),
    ob_start() . _ntItem('Entrada por Campanhas Externas via API Própria',
        'Leads originados de fontes externas (anúncios, formulários, integrações de terceiros) entram por um endpoint próprio desenvolvido para a empresa. Isso é necessário porque o Bitrix24 possui limitações em seus métodos nativos — nem todos os campos são suportados — e nem todas as ferramentas externas conseguem se conectar diretamente ao Bitrix. Com o endpoint próprio, qualquer ferramenta que consiga fazer uma requisição web já consegue enviar os dados, sem restrições.',
        _ntTag('tag-api-kw','API KW24'), 'api') . ob_get_clean(),
    ob_start() . _ntItem('Criação Automática de Contato',
        'Comportamento nativo do Bitrix24: quando o lead possui Nome, E-mail e/ou Telefone preenchidos, o sistema cria automaticamente um Contato vinculado com esses dados. No caso desta empresa, utiliza-se principalmente o Nome (que pode vir como nome simples ou nome e sobrenome juntos), E-mail e Telefone.',
        _ntTag('tag-modelo','Nativo Bitrix24')) . ob_get_clean(),
    ob_start() . _ntItem('Início de Campanha em Lote',
        'É possível selecionar um card na etapa Prospect e acionar o início de uma campanha via API externa. Todos os leads que compartilham o mesmo nome de campanha passam automaticamente para a etapa "Aguardando 1º Disparo", sem necessidade de mover cada card manualmente.',
        _ntTag('tag-api-ext','API Externa'), 'warning') . ob_get_clean(),
])); ?>

<?php _ntSection('Aguardando 1º Disparo', 'Etapa', 'badge-stage', implode('', [
    ob_start() . _ntItem('1. Configurações Iniciais',
        'Ao entrar na etapa, a primeira ação é chamar o modelo "Configurações Iniciais". O fluxo aguarda esse modelo terminar antes de continuar.<br><br>O modelo verifica se o campo "ID Parceiro" está preenchido. Caso esteja, localiza a empresa correspondente no Bitrix24 e faz o vínculo automático no campo "Parceiro Comercial" — que passa a exibir o nome da empresa clicável, com acesso direto ao cadastro.<br><br>Esta funcionalidade foi construída pensando em uso futuro: hoje os leads vêm de campanhas internas e o parceiro é sempre a própria equipe. Caso parceiros externos passem a utilizar a ferramenta, o ID Parceiro permite identificar e vincular cada lead à empresa correta, viabilizando controles como evitar duplicidade entre parceiros. Por enquanto, apenas o vínculo está implementado.',
        _ntTag('tag-modelo','Modelo')) . ob_get_clean(),
    ob_start() . _ntItem('2. Validação de E-mail',
        'Após as configurações iniciais, o sistema verifica se o campo E-mail do lead está preenchido. Se estiver vazio, o lead é movido automaticamente para a etapa "Sem E-mail" e o fluxo encerra. Não se trata de um lead perdido — apenas não há e-mail para disparar, então não faz sentido continuar no fluxo de prospecção.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
    ob_start() . _ntItem('3. Agendamento de Disparo (Controle de Cadência)',
        'O sistema calcula a data e hora do primeiro disparo com base em uma fila de envios. Os e-mails são agendados em intervalos de segundos pré-definidos com a equipe, respeitando horário útil e dias úteis configurados. Isso evita envios simultâneos em massa — se houver mil leads ou várias campanhas rodando ao mesmo tempo, o sistema cria uma fila e distribui os disparos ao longo do tempo, sem sobrecarregar o servidor de e-mails.<br><br>O lead entra em uma de duas filas conforme o campo <strong>Prioridade de Disparo</strong>: a fila Normal, usada para volumes maiores e campanhas do dia a dia, e a fila Alta, reservada para lotes menores e urgentes que precisam disparar rapidamente — sem precisar aguardar atrás de uma fila extensa já em andamento. O lead permanece nesta etapa até o momento agendado, que é informado no card.',
        _ntTag('tag-api-ext','API Externa'), 'warning') . ob_get_clean(),
    ob_start() . _ntItem('4. Avanço para Prospecção 1',
        'Quando chega a data/hora agendada, o lead é movido automaticamente para a etapa "Prospecção 1" — esse é o primeiro disparo. A mudança de etapa é o gatilho do envio.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
])); ?>

<?php _ntSection('Prospecção 1 / 2 / 3', 'Etapas', 'badge-stage', implode('', [
    ob_start() . _ntItem('Modelo Cadência',
        'Essas três etapas são controladas pelo modelo "Cadência", que define o ritmo de trabalho do time comercial. A lógica é flexível e configurável: ao entrar em cada etapa, é possível criar uma ou mais tarefas para o responsável (ligação, mensagem no WhatsApp, LinkedIn, etc.), pausar por um período determinado, criar novas tarefas após a pausa, pausar novamente, e assim por diante — até que o lead seja movido para a próxima etapa automaticamente.<br><br>A quantidade de tarefas, os tipos de atividade e os intervalos de pausa entre elas são definidos conforme a necessidade de cada etapa e podem ser ajustados a qualquer momento. O modelo também atualiza o campo Script/Status a cada mudança, orientando o responsável sobre o que fazer naquele momento.',
        _ntTag('tag-modelo','Modelo')) . ob_get_clean(),
    ob_start() . _ntItem('Disparo de E-mail por Solução de Interesse',
        'Em cada etapa, além das tarefas, são disparados e-mails condicionais com base no campo "Solução de Interesse" do lead. Conforme o valor desse campo, o sistema seleciona e envia o e-mail correspondente — permitindo comunicações personalizadas por produto, região, perfil ou qualquer critério definido pelo cliente.<br><br>Toda a configuração dos e-mails — conteúdo, condições, quantidade e timing — é feita diretamente pelo cliente, sem depender da equipe técnica. É possível ter mais de um e-mail por etapa, com datas e regras distintas. Esse controle se aplica a todas as etapas do funil.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
])); ?>

<?php _ntSection('Visualizados 1', 'Etapa', 'badge-stage', implode('', [
    ob_start() . _ntItem('Entrada por Gatilho — E-mail Visualizado',
        'Em qualquer momento durante as etapas Prospecção 1, 2 ou 3, se o lead abre o e-mail recebido, o fluxo em andamento é interrompido automaticamente e o lead é movido para Visualizados 1. Esse movimento é acionado por um gatilho de leitura de e-mail, não pela etapa em si.',
        _ntTag('tag-etapa','Gatilho'), 'trigger') . ob_get_clean(),
    ob_start() . _ntItem('Modelo Cadência',
        'O modelo Cadência roda aqui da mesma forma que nas etapas de Prospecção — tarefas, pausas e movimentações configuráveis. A saída desta etapa é uma escolha do cliente: pode ir automaticamente para Breakup após um tempo, ou permanecer aqui para que o responsável insista no contato direto (ligação, por exemplo). Como o lead que abriu o e-mail demonstra mais interesse, muitas vezes vale manter o esforço antes de encerrar o ciclo.',
        _ntTag('tag-modelo','Modelo')) . ob_get_clean(),
    ob_start() . _ntItem('Disparo de E-mail',
        'Ao entrar em Visualizados 1, ao menos um e-mail deve ser disparado — é ele que possibilita a existência de Visualizados 2, pois só há sentido em detectar uma segunda visualização se um segundo e-mail for enviado. O timing e as regras de Solução de Interesse seguem o mesmo padrão das demais etapas.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
])); ?>

<?php _ntSection('Visualizados 2', 'Etapa', 'badge-stage', implode('', [
    ob_start() . _ntItem('Entrada por Gatilho — Segundo E-mail Visualizado',
        'Se o lead abre o e-mail enviado em Visualizados 1, é movido automaticamente para Visualizados 2 via gatilho.',
        _ntTag('tag-etapa','Gatilho'), 'trigger') . ob_get_clean(),
    ob_start() . _ntItem('Modelo Cadência',
        'Mesma estrutura de Visualizados 1 — tarefas, pausas e saída configuráveis. O cliente decide se o lead avança automaticamente para Breakup ou permanece aqui para abordagem direta.',
        _ntTag('tag-modelo','Modelo')) . ob_get_clean(),
    ob_start() . _ntItem('Disparo de E-mail (opcional)',
        'Pode haver um disparo de e-mail nesta etapa, mas fica a critério do cliente configurar ou não.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
])); ?>

<?php _ntSection('E-mails Respondidos', 'Etapa', 'badge-stage', implode('', [
    ob_start() . _ntItem('Entrada por Gatilho — E-mail Respondido',
        'Quando o lead responde um e-mail, é movido automaticamente para esta etapa via gatilho.',
        _ntTag('tag-etapa','Gatilho'), 'trigger') . ob_get_clean(),
    ob_start() . _ntItem('Modelo Cadência (opcional)',
        'A cadência pode ou não ser configurada aqui. Se configurada, segue a mesma lógica das demais etapas — tarefas, pausas e saída automática ou manual, conforme o que o cliente achar mais adequado para tratar um lead que já respondeu.',
        _ntTag('tag-modelo','Modelo')) . ob_get_clean(),
    ob_start() . _ntItem('Disparo de E-mail (opcional)',
        'Pode haver um disparo de e-mail nesta etapa, mas fica a critério do cliente configurar ou não.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
])); ?>

<?php _ntSection('Em Qualificação / Discovery', 'Etapa', 'badge-stage', _ntEmpty('Etapa de controle sem automações. O responsável trabalha o lead manualmente conforme o andamento do contato.'), false); ?>
<?php _ntSection('Agendamento de Reunião', 'Etapa', 'badge-stage', _ntEmpty('Etapa de controle sem automações. O responsável trabalha o lead manualmente conforme o andamento do contato.'), false); ?>
<?php _ntSection('Reunião Agendada', 'Etapa', 'badge-stage', _ntEmpty('Etapa de controle sem automações. O responsável trabalha o lead manualmente conforme o andamento do contato.'), false); ?>
<?php _ntSection('No-Show', 'Etapa', 'badge-stage', _ntEmpty('Etapa de controle sem automações. O responsável trabalha o lead manualmente conforme o andamento do contato.'), false); ?>

<?php _ntSection('Contato Futuro', 'Etapa', 'badge-stage', implode('', [
    ob_start() . _ntItem('Campo Obrigatório — Data de Retorno',
        'Ao mover um lead para esta etapa, o campo <strong>Data Entrada Contato Futuro</strong> é exigido. É nele que o responsável informa quando o lead deve ser retomado — pode ser daqui a alguns dias, semanas ou meses, conforme a situação (cliente viajando, de férias, pediu para ligar depois, etc.).',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
    ob_start() . _ntItem('Retorno Automático para Em Qualificação / Discovery',
        'O sistema monitora a data informada e move o lead de volta para "Em Qualificação / Discovery" um dia útil antes — para que o responsável tenha tempo de se preparar e retomar o contato no momento certo. O prazo de antecedência é configurável conforme a necessidade do cliente.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
])); ?>

<?php _ntSection('Sem E-mail', 'Etapa', 'badge-stage', _ntEmpty('Etapa de controle sem automações. Leads que chegaram aqui não possuem e-mail cadastrado.'), false); ?>

<?php _ntSection('Breakup', 'Etapa', 'badge-stage', implode('', [
    ob_start() . _ntItem('Modelo Cadência',
        'O modelo Cadência também controla esta etapa, com a mesma flexibilidade das demais — tarefas, pausas e saída configuráveis conforme o cliente preferir.',
        _ntTag('tag-modelo','Modelo')) . ob_get_clean(),
    ob_start() . _ntItem('Disparo de E-mail (opcional)',
        'Pode haver um disparo de e-mail nesta etapa — normalmente um último contato antes do encerramento. Fica a critério do cliente configurar ou não, e com o conteúdo que quiser.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
])); ?>

<?php _ntSection('Qualificar', 'Etapa de Ganho', 'badge-stage', implode('', [
    ob_start() . _ntItem('Exigência de Dados de Qualificação',
        'Para avançar, o lead precisa ter os campos de qualificação preenchidos — os dados que confirmam que aquele contato é um lead válido e pronto para o funil comercial. Os campos exigidos são definidos pelo cliente conforme o que faz sentido para o seu processo.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
    ob_start() . _ntItem('Criação de Card no Funil Closer + Conclusão do Lead',
        'Com os dados preenchidos, o sistema cria automaticamente um novo card no funil Closer — Nimbus TAX. Como Lead e Negócio são entidades diferentes dentro do Bitrix24, não é possível simplesmente mover o card: um novo precisa ser criado. As informações trabalhadas no lead são repassadas para os campos do novo card. O trabalho no lead está encerrado — a etapa Qualificar já representa a conclusão.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
])); ?>

<?php _ntSection('Perdido', 'Etapa de perda', 'badge-empty', _ntEmpty('Etapa de encerramento negativo. Sem automações.'), false); ?>
<?php _ntSection('Não Visualizado', 'Etapa de perda', 'badge-empty', _ntEmpty('Etapa de encerramento negativo. Sem automações.'), false); ?>

</div><!-- /bc-page-stages -->

<div class="bc-page-aside">
<?php
ob_start();
_ntAsideItem('Automação de Script/Status', 'Acionada quando o campo Script/Status é alterado manualmente. O campo é revertido automaticamente ao texto original, mantendo sempre o conteúdo correto para a etapa.');
_ntAsideBox('Automações Gerais do Funil', '', ob_get_clean());

ob_start();
_ntCampoItem('Nome <span style="font-weight:400;color:#999">(nativo)</span>', 'Nome do lead. Pode vir como nome simples ou nome e sobrenome juntos. Usado para criar o Contato automaticamente.');
_ntCampoItem('Sobrenome <span style="font-weight:400;color:#999">(nativo)</span>', 'Sobrenome do lead. Campo nativo, utilizado em conjunto com o Nome na criação do Contato.');
_ntCampoItem('Telefone <span style="font-weight:400;color:#999">(nativo)</span>', 'Telefone do lead. Campo nativo, repassado para o Contato criado automaticamente.');
_ntCampoItem('Nome Campanha', 'Identificador da campanha à qual o lead pertence. É definido no momento do importe da lista ou automaticamente pela origem externa (ex: nome da campanha no Facebook, Instagram, etc.). Pode ser um nome livre ou numeração — serve como controle para agrupar leads e acionar ações em lote sobre todos os que compartilham o mesmo valor.');
_ntCampoItem('E-mail', 'Campo de e-mail do lead. Validado em "Aguardando 1º Disparo" — se vazio, o lead é direcionado para a etapa "Sem E-mail" e sai do fluxo de prospecção.');
_ntCampoItem('Script / Status', 'Campo de texto atualizado automaticamente pelo modelo Cadência conforme a etapa em que o lead se encontra. Exibe orientações e scripts para o responsável — o que fazer, o que falar, qual ação tomar naquele momento. Protegido contra edição manual via automação geral.');
_ntCampoItem('Solução de Interesse', 'Campo lista que identifica o produto, serviço, região ou perfil de interesse do lead. Define qual e-mail será disparado em cada etapa — cada valor da lista pode ter um e-mail específico configurado pelo cliente.');
_ntCampoItem('Prioridade de Disparo', 'Campo lista com dois valores: Normal e Alta. Define em qual fila de envio o lead entra. Normal é usada para listas grandes e leads frios do dia a dia. Alta é reservada para listas pequenas e urgentes (~50 leads) que precisam ser disparadas rapidamente, sem concorrer com o volume da fila normal.');
_ntCampoItem('Data Entrada Contato Futuro', 'Campo de data obrigatório ao mover o lead para "Contato Futuro". Define quando o lead deve ser retomado. O sistema usa esta data para calcular o retorno automático para "Em Qualificação / Discovery" com a antecedência configurada.');
_ntCampoItem('ID Parceiro', 'Identificador numérico do parceiro comercial. Informado via campanha ou importe. Usado pelo modelo "Configurações Iniciais" para localizar e vincular a empresa do parceiro no Bitrix24.');
_ntCampoItem('Parceiro Comercial', 'Campo vinculado à empresa do parceiro no Bitrix24. Preenchido automaticamente pelo modelo "Configurações Iniciais" com base no ID Parceiro. Exibe o nome da empresa clicável, com acesso direto ao cadastro.');
_ntCampoItem('CPF / CNPJ', 'Documento do lead. Campo de texto livre.');
_ntCampoItem('Estado', 'Estado do lead. Campo lista.');
_ntCampoItem('Segmento', 'Segmento de atuação do lead. Campo lista.');
_ntCampoItem('Regime de Tributação', 'Regime tributário do lead. Campo lista.');
_ntCampoItem('Ramo de Atividade', 'Ramo de atividade do lead. Campo lista.');
_ntCampoItem('Responsável SDR', 'Usuário responsável pela prospecção do lead.');
_ntCampoItem('Responsável Comercial (Closer)', 'Usuário responsável pelo fechamento comercial do lead.');
_ntCampoItem('Nome da Empresa', 'Nome da empresa do lead. Campo de texto livre.');
_ntCampoItem('Número de Funcionários', 'Quantidade de funcionários da empresa do lead.');
_ntCampoItem('Parâmetros UTM <span style="font-weight:400;color:#999">(nativo)</span>', 'Dados de rastreamento de origem do lead (UTM source, medium, campaign, etc.). Registram de qual campanha ou canal o lead veio.');
_ntCampoItem('Comentário', 'Campo de texto livre para anotações sobre o lead.');
_ntCampoItem('Fonte', 'Origem do lead — canal ou meio pelo qual ele foi captado.');
_ntAsideBox('Campos Utilizados', 'campos', ob_get_clean());
?>
</div><!-- /bc-page-aside -->
</div><!-- /bc-page-layout -->
</div><!-- /bc-nt-lead -->


<!-- ██████████████████████████████████████████████████████████████████████
     CLOSER
█████████████████████████████████████████████████████████████████████ -->
<div class="bc-page" id="bc-nt-closer">

<div class="bc-page-header">
    <h1>Closer — Nimbus TAX</h1>
    <div class="bc-page-meta">Funil: Deals · Categoria: 53 · ENTITY_ID de etapas: DEAL_STAGE_53</div>
    <div class="bc-page-intro">
        O funil Closer é o <strong>coração do processo comercial</strong>. Recebe os leads qualificados do funil de Lead e conduz o processo de negociação, diagnóstico e fechamento.<br><br>
        Cada card representa um negócio em andamento com um cliente. O closer acompanha todo o ciclo: da validação do CNPJ e apresentação das oportunidades, passando pela solicitação e execução do diagnóstico, até a assinatura do contrato.<br><br>
        Quando um negócio é concluído, o sistema automaticamente distribui todos os cards de Relatório Preliminar vinculados para os funis operacionais corretos — dando início à execução do trabalho contratado.
    </div>
</div>

<div class="bc-page-layout">
<div class="bc-page-stages">

<?php _ntSection('Oportunidades', 'Etapa', 'badge-stage', implode('', [
    ob_start() . _ntItem('1. Vínculo do Parceiro Comercial',
        'Ao entrar na etapa, o sistema lê o campo ID Parceiro e vincula automaticamente a empresa parceira correspondente no campo Parceiro Comercial do card. Isso se aplica tanto a leads que vieram do funil de Lead quanto a leads originados de formulários do portal de parceiros.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
    ob_start() . _ntItem('2. Validação e Cadastro/Vínculo de Empresa',
        'Valida se o CNPJ ou CPF informado no card é válido. Se for inválido, abre uma tarefa para o responsável fazer a correção antes de prosseguir.<br><br>Uma vez validado, verifica no Bitrix24 se já existe uma empresa cadastrada com esse CNPJ/CPF para evitar duplicidades. Se existir, vincula-a ao card; caso contrário, cria um novo cadastro e faz o vínculo. Ao final, grava a empresa no campo Empresa do card.',
        _ntTag('tag-modelo','Modelo')) . ob_get_clean(),
    ob_start() . _ntItem('3. Consulta Receita Federal',
        'Após a empresa estar vinculada, consulta a API da Receita Federal com o CNPJ para enriquecer o cadastro com dados atualizados: razão social, endereço, situação cadastral, entre outros. Esses dados são gravados diretamente na empresa cadastrada no Bitrix24.',
        _ntTag('tag-api-ext','API Externa'), 'warning') . ob_get_clean(),
    ob_start() . _ntItem('4. Normalização de Telefone',
        'Executado após a validação do CNPJ/CPF. Analisa o número de telefone cadastrado e corrige automaticamente erros comuns de formatação: ausência ou duplicidade do código do país (55), zero inicial indevido no DDD, e falta do nono dígito em números de celular. Não corrige números incorretos — apenas resolve inconsistências de formato que impediriam o contato adequado com o cliente.',
        _ntTag('tag-modelo','Modelo')) . ob_get_clean(),
    ob_start() . _ntItem('5. Conflito de Parceiros',
        'Executado ao término da validação do CNPJ/CPF. Verifica se a empresa recém vinculada já está associada a outro parceiro comercial no sistema. Caso haja conflito, cria uma tarefa de alerta para que a equipe analise e resolva a situação antes de prosseguir.',
        _ntTag('tag-modelo','Modelo')) . ob_get_clean(),
    ob_start() . _ntItem('6. Regularização de Oportunidade (Portal do Parceiro)',
        'Executado apenas quando o lead veio pelo formulário do portal de parceiros. Para que o formulário funcione corretamente, foram criados três campos de oportunidade — um por regime de tributação (Simples, Presumido e Real). Esta automação identifica qual dos três campos está preenchido e transfere a oportunidade selecionada para o campo interno Oportunidades Oferecidas, que é o utilizado pelo restante do sistema.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
])); ?>

<?php _ntSection('Contato / Reunião Agendada', 'Etapa', 'badge-stage', _ntEmpty('Etapa de controle. Sem automações.'), false); ?>
<?php _ntSection('Em Negociação', 'Etapa', 'badge-stage', _ntEmpty('Etapa de controle. Sem automações.'), false); ?>

<?php _ntSection('Solicitar Diagnóstico', 'Etapa', 'badge-stage', implode('', [
    ob_start() . _ntItem('1. Verificação de Pré-requisitos',
        'Ao entrar na etapa, o sistema verifica dois campos obrigatórios: se há uma empresa vinculada no card e se o campo Oportunidades Oferecidas está preenchido. Caso qualquer um deles esteja ausente, o sistema registra um comentário na timeline do card informando exatamente o que está faltando, encerra qualquer processo em andamento e retorna o card à etapa Oportunidades para que a correção seja feita antes de prosseguir.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
    ob_start() . _ntItem('2. Criação do Card Primário no Relatório Preliminar',
        'Cria automaticamente um card na etapa Triagem do funil Relatório Preliminar, carregando os seguintes campos do Closer: Empresa, Parceiro Comercial, Tipo de Processo Operacional, Resumo, Responsável SDR, e o campo Oportunidade preenchido com a primeira oportunidade do campo Oportunidades Oferecidas. O campo Negócio é vinculado ao card do Closer para facilitar o acesso entre os dois. Este card é marcado como <strong>Oportunidade Primária</strong>. Uma tarefa é criada neste card para que a equipe de triagem realize o diagnóstico — identificando oportunidades complementares com potencial de ganho.<br><br>Boa prática: ter apenas uma oportunidade em Oportunidades Oferecidas neste momento.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
    ob_start() . _ntItem('3. Retorno das Oportunidades Complementares',
        'Quando a tarefa de triagem é concluída no Relatório Preliminar, as oportunidades complementares identificadas pela equipe são copiadas de volta para o campo Oportunidades Complementares do card do Closer, consolidando no funil comercial tudo que foi mapeado no diagnóstico.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
    ob_start() . _ntItem('4. Criação de Cards de Oportunidades (API)',
        'Executa a API de criação de oportunidades descrita nas Automações Gerais. As oportunidades complementares são criadas no Relatório Preliminar na etapa <strong>Execução do Diagnóstico</strong>, com o campo Tipo de Oportunidade marcado como <strong>Complementar</strong>. O card principal já foi criado no item anterior e não é afetado por esta automação.',
        _ntTag('tag-api-ext','API Externa'), 'warning') . ob_get_clean(),
])); ?>

<?php _ntSection('Diagnóstico Parcialmente Realizado', 'Etapa', 'badge-stage', implode('', [
    ob_start() . _ntItem('Atualização parcial do diagnóstico',
        'O card é movido para esta etapa automaticamente pelo funil Relatório Preliminar quando ao menos uma das oportunidades conclui o diagnóstico, mas ainda há outras pendentes. A cada conclusão, o resumo do diagnóstico realizado e os arquivos gerados são trazidos para dentro deste card no Closer.',
        _ntTag('tag-etapa','Via Relatório Preliminar'), 'trigger') . ob_get_clean(),
])); ?>

<?php _ntSection('Diagnóstico Realizado', 'Etapa', 'badge-stage', implode('', [
    ob_start() . _ntItem('Conclusão total do diagnóstico',
        'O card é movido para esta etapa automaticamente pelo funil Relatório Preliminar quando todas as oportunidades concluem o diagnóstico. O resumo do último diagnóstico realizado e os arquivos correspondentes são trazidos para dentro deste card no Closer.',
        _ntTag('tag-etapa','Via Relatório Preliminar'), 'trigger') . ob_get_clean(),
])); ?>

<?php _ntSection('Apresentação de Resultado', 'Etapa', 'badge-stage', _ntEmpty('Etapa de controle. Sem automações.'), false); ?>

<?php _ntSection('Enviado p/ assinatura (ClickSign)', 'Etapa', 'badge-stage', implode('', [
    ob_start() . _ntItem('1. Envio para assinatura digital (ClickSign)',
        'Integração com a API da ClickSign que envia o arquivo informado no campo Arquivo a ser assinado para assinatura digital. Os signatários são definidos pelos campos de tipo: Contratada, Contratante, Testemunha e Parte — ao menos um deve ser selecionado. A data limite de assinatura define por quanto tempo o link ficará ativo na ClickSign. A cada assinatura recebida, os campos Signatários que já assinaram e Signatários a assinar são atualizados automaticamente, e uma notificação é enviada no card informando quem assinou.',
        _ntTag('tag-api-ext','API Externa'), 'warning') . ob_get_clean(),
    ob_start() . _ntItem('2. Extensão automática do prazo de assinatura',
        'Um dia útil antes de a data limite vencer, o sistema identifica que a assinatura ainda não foi concluída, estende automaticamente o prazo por mais um dia útil e notifica o responsável no card. Essa extensão ocorre uma única vez — se o prazo vencer novamente sem assinatura, a assinatura é cancelada por timeout.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
    ob_start() . _ntItem('3. Recebimento do documento assinado',
        'Quando todas as assinaturas são concluídas, o documento assinado é adicionado automaticamente ao campo Documentos Assinados do card. Este campo acumula todos os documentos assinados na negociação — contrato, NDA ou qualquer outro — permitindo acompanhar o histórico completo de assinaturas em um só lugar.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
])); ?>

<?php _ntSection('Enviado p/ assinatura (Outros)', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Contrato(s) Assinado(s)', 'Etapa', 'badge-stage', _ntEmpty('Etapa de controle. O card entra aqui automaticamente quando o contrato é assinado via ClickSign. Sem automações adicionais.'), false); ?>
<?php _ntSection('Stand-By', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Oportunidade Cross Sell', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Documentos Incompletos', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>

<?php _ntSection('Concluído', 'Ganho', 'badge-stage', implode('', [
    ob_start() . _ntItem('1. Coleta de dados de conclusão',
        'Ao entrar em Concluído, o sistema exige o preenchimento de uma série de campos obrigatórios antes de finalizar o negócio, incluindo a confirmação de contrato assinado — seja via ClickSign ou anexado manualmente. Campos específicos a detalhar futuramente.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
    ob_start() . _ntItem('2. Criação de Cards por Oportunidades Convertidas (API)',
        'Executa a API de criação de oportunidades lendo o campo Oportunidades Convertidas. Verifica quais cards já existem e cria apenas os que faltam.<br><br><strong>Se passou por Solicitar Diagnóstico:</strong> os cards são criados no funil Relatório Preliminar.<br><br><strong>Se não passou por Solicitar Diagnóstico</strong> (sem diagnóstico prévio): todos os cards vão direto para o funil Operacional, na etapa Triagem.',
        _ntTag('tag-api-ext','API Externa'), 'warning') . ob_get_clean(),
    ob_start() . _ntItem('3. Criação do Contato Comercial na Empresa',
        'Modelo que utiliza os dados do contato comercial já disponíveis no card para criar o contato no Bitrix24 e vinculá-lo à empresa no campo Contato Comercial, facilitando o acesso da equipe operacional.',
        _ntTag('tag-modelo','Modelo')) . ob_get_clean(),
    ob_start() . _ntItem('4. E-mail de Cadastro de Contatos <span style="font-size:11px;color:#b45309;font-weight:400;">(desativado)</span>',
        'Modelo que envia um e-mail ao cliente com um formulário para ele preencher todos os seus contatos (financeiro, comercial, etc.). Quando o cliente preenche e envia, os contatos são registrados automaticamente no Bitrix24 e vinculados à empresa. Está implementado mas desativado — aguardando revisão antes de ser reativado.',
        _ntTag('tag-modelo','Modelo')) . ob_get_clean(),
    ob_start() . _ntItem('5. Distribuição dos cards em Aguardando Closer',
        'Ao concluir o negócio, o sistema percorre todos os cards do Relatório Preliminar vinculados a este Closer que estejam na etapa Aguardando Closer e os distribui conforme o Tipo de Processo Operacional: oportunidades convertidas vão para Contencioso ou Operacional; oportunidades não convertidas são movidas para Perdido.',
        _ntTag('tag-etapa','Gatilho'), 'trigger') . ob_get_clean(),
])); ?>

<?php _ntSection('Encerramento Negativo', 'Lixeira · Perdidos · Sem valor · Fechado · Sem Interesse', 'badge-empty', _ntEmpty('Etapa de controle. Sem automações.'), false); ?>

</div><!-- /bc-page-stages -->

<div class="bc-page-aside">
<?php
ob_start();
_ntStdAside(
    ob_start() .
    _ntAsideItem('Geração de Documentos (Automações Comercial)', 'Automação geral disponível em qualquer etapa do card. Ao selecionar uma opção no campo Automações Comercial e salvar, o sistema abre um fluxo para preenchimento de dados e gera o documento automaticamente — entregando-o pronto no campo Arquivo a ser assinado. Algumas opções também criam contatos e organizam informações relacionadas automaticamente.<br><br>Documentos disponíveis: Contrato Nimbus, Contrato Nimbus e BGA, NDA Nimbus, Procuração BGA, Autorização Trier, Contrato ICMS ST KWCA.<br><br>Alternativa: o documento pode ser anexado manualmente no campo Arquivo a ser assinado, independente desta automação. As duas formas são compatíveis com o fluxo de assinatura via ClickSign.') .
    _ntAsideItem('Criação de Cards de Oportunidades', 'API externa acionada automaticamente ao entrar em Solicitar Diagnóstico e ao concluir o negócio, mas que pode ser chamada a qualquer momento. Lê os campos de oportunidade e cria os cards ainda não existentes. Em todos os cenários verifica quais já foram criados e cria apenas os que faltam.<br><br><strong>Destino dos cards — regras em ordem de prioridade:</strong><br><br><strong>1. Consultoria = Sim:</strong> todos os cards vão para o funil Consultoria, independente do tipo de processo.<br><br><strong>2. Tipo de Processo = Administrativo ou Administrativo (Anexo V):</strong> cards vão para o funil Operacional. Se passou por diagnóstico, o Relatório Preliminar conduz até o Operacional ao concluir. Se não passou, vai direto para Operacional.<br><br><strong>3. Tipo de Processo = Contencioso Ativo:</strong> cards vão para o funil Contencioso. O Contencioso gerará um card próprio e, ao resolver, enviará os casos para a etapa "Aguardando Contencioso" no Operacional.<br><br><strong>4. Tipo de Processo = Contencioso Passivo ou Contencioso Cível:</strong> cards vão para o funil Contencioso e permanecem lá.') .
    ob_get_clean()
);
_ntAsideBox('Automações Gerais do Funil', '', ob_get_clean());

ob_start();
_ntCampoItem('Consultoria', 'Campo Sim/Não que indica se o negócio deve seguir para o funil de Consultoria em vez do Relatório Preliminar. Quando marcado como Sim, a API de criação de oportunidades direciona todos os cards gerados para o funil Consultoria.');
_ntCampoItem('Tipo de Processo Operacional', 'Campo grupo que classifica o tipo de processo operacional associado ao negócio. É copiado para o card criado e define para qual funil ele será direcionado:<br><br><strong>Administrativo / Administrativo (Anexo V):</strong> card vai para o funil Operacional.<br><strong>Contencioso Ativo:</strong> card vai para o funil Contencioso, que ao concluir envia os casos para "Aguardando Contencioso" no Operacional.<br><strong>Contencioso Passivo / Contencioso Cível:</strong> card vai para o funil Contencioso e permanece lá.');
_ntCampoItem('Responsável SDR', 'Identifica o SDR responsável pelo lead original. Carregado automaticamente a partir do funil de Lead no momento em que o card é criado no Closer.');
_ntCampoItem('Responsável Closer', 'Identifica o Closer responsável pela oportunidade. Preenchido internamente conforme a atribuição do card no funil Closer.');
_ntCampoItem('Automações Comercial', 'Campo lista disponível em qualquer etapa. Permite selecionar o tipo de documento a gerar (contrato, NDA, procuração, autorização). Ao salvar, dispara o fluxo de geração automática do documento escolhido.');
_ntCampoItem('Ação ClickSign', 'Campo lista com ações disponíveis para assinaturas já enviadas à ClickSign: atualizar a data limite de assinatura ou cancelar a assinatura em andamento.');
_ntCampoItem('Data de Assinatura do Contrato', 'Data em que o contrato foi assinado. Campo do card do Closer, independente da integração com a ClickSign.');
_ntCampoItem('Signatários (Contratada / Contratante / Testemunha / Parte)', 'Quatro campos vinculados a contatos do Bitrix24, um por tipo de signatário. Ao menos um deve ser preenchido para o envio da assinatura. Definem quem receberá o documento para assinar e em qual papel.');
_ntCampoItem('Arquivo a ser assinado', 'Campo de anexo com o documento (PDF ou Word) que será enviado para assinatura digital via ClickSign.');
_ntCampoItem('Data limite de assinatura', 'Define até quando o link de assinatura ficará ativo na ClickSign. Não tem relação com a data do contrato. Pode ser alterada pela Ação ClickSign. Um dia útil antes do vencimento, o sistema estende automaticamente por mais um dia e notifica o responsável.');
_ntCampoItem('Signatários que já assinaram / a assinar', 'Dois campos vinculados a contatos, atualizados automaticamente a cada assinatura recebida. Permitem acompanhar em tempo real quem já concluiu a assinatura e quem ainda está pendente.');
_ntCampoItem('Documentos Assinados', 'Campo que acumula todos os documentos assinados digitalmente na negociação — contrato, NDA e outros. Preenchido automaticamente ao concluir cada assinatura.');
_ntCampoItem('Comentário Resumo', 'Campo de texto livre para registrar observações sobre o card. Ao salvar, dispara a automação Resumo do Negócio e em seguida é limpo automaticamente, ficando pronto para o próximo comentário.');
_ntCampoItem('Resumo', 'Histórico acumulado de comentários do card. Cada entrada registra data, responsável e texto do comentário, com os mais recentes sempre no topo. Não deve ser editado manualmente — o sistema reverte qualquer alteração direta ao conteúdo anterior.');
_ntCampoItem('ID Parceiro', 'Campo numérico que armazena o identificador do parceiro comercial. É preenchido automaticamente quando o lead vem de uma campanha de parceiro, e utilizado para localizar e vincular a empresa parceira correspondente na base do Bitrix24.');
_ntCampoItem('Parceiro Comercial', 'Campo de vínculo com a empresa do parceiro cadastrada no Bitrix24. Preenchido automaticamente a partir do ID Parceiro, associa o card ao parceiro responsável pela indicação do lead.');
_ntCampoItem('Empresa', 'Campo nativo do Bitrix24 que vincula o card a uma empresa cadastrada na base. Preenchido automaticamente pelo modelo de validação de CNPJ/CPF, que cria ou localiza a empresa e faz o vínculo.');
_ntCampoItem('Oportunidades Oferecidas', 'Campo múltiplo vinculado ao catálogo de Oportunidades/Produtos. Registra o(s) serviço(s) ou produto(s) ofertado(s) inicialmente ao cliente. Quando o lead vem pelo portal do parceiro, é preenchido automaticamente pela automação de regularização de oportunidade.');
_ntCampoItem('Oportunidades Complementares', 'Campo múltiplo vinculado ao catálogo de Oportunidades/Produtos. Preenchido pela equipe de triagem durante o diagnóstico com as oportunidades adicionais identificadas no cliente — além da que motivou o contato inicial.');
_ntCampoItem('Oportunidades Convertidas', 'Campo múltiplo vinculado ao catálogo de Oportunidades/Produtos. Registra as oportunidades efetivamente fechadas e convertidas com o cliente.');
_ntAsideBox('Campos Utilizados', 'campos', ob_get_clean());
?>
</div><!-- /bc-page-aside -->
</div><!-- /bc-page-layout -->
</div><!-- /bc-nt-closer -->


<!-- ██████████████████████████████████████████████████████████████████████
     RELATÓRIO PRELIMINAR
█████████████████████████████████████████████████████████████████████ -->
<div class="bc-page" id="bc-nt-rel-preliminar">

<div class="bc-page-header">
    <h1>Relatório Preliminar (Diagnóstico)</h1>
    <div class="bc-page-meta">Funil: Deals · Categoria: 17 · ENTITY_ID de etapas: DEAL_STAGE_17</div>
    <div class="bc-page-intro">
        O funil de Relatório Preliminar é o <strong>primeiro estágio da operação</strong>. Cada card representa uma oportunidade a ser diagnosticada — pode ser primária (a oportunidade principal do negócio) ou complementar (identificada durante o processo).<br><br>
        Os cards chegam aqui automaticamente após a conclusão do Closer, um por oportunidade. O fluxo passa por coleta de documentos, triagem e execução do diagnóstico — onde são levantados o valor e o relatório preliminar da oportunidade.<br><br>
        Ao concluir o diagnóstico, os dados são enviados ao Closer e o card aguarda a decisão comercial. Quando o Closer conclui o negócio, os cards são distribuídos automaticamente para o funil correto: <strong>Operacional</strong> (processos administrativos) ou <strong>Contencioso</strong> (processos judiciais/administrativos contenciosos).
    </div>
</div>

<div class="bc-page-layout">
<div class="bc-page-stages">

<?php _ntSection('Coleta de Documentos (Parceiro)', 'Etapa', 'badge-stage', implode('', [
    ob_start() . _ntItem('1. Apresentação das pendências da Triagem',
        'Ao entrar nesta etapa, todas as informações e documentos solicitados pela Triagem são apresentados no card — tanto a lista de documentos necessários quanto as observações e correções apontadas. O responsável pode então anexar os arquivos e dados solicitados diretamente no card.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
    ob_start() . _ntItem('2. Checklist de Coleta (Operação)',
        'O mesmo modelo de checklist que roda na Triagem também é executado aqui, garantindo que o processo de coleta siga o mesmo padrão de controle. O card só pode avançar após a conclusão do checklist — existe uma trava que impede o avanço sem que ele seja concluído.',
        _ntTag('tag-modelo','Modelo')) . ob_get_clean(),
    ob_start() . _ntItem('3. Retorno automático à Triagem',
        'Uma vez concluída a coleta e salvo o checklist, o card retorna automaticamente para a etapa Triagem para nova validação. A Triagem pode então aprovar, solicitar correções ou pedir documentos adicionais.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
])); ?>

<?php _ntSection('Triagem (CheckList Operação)', 'Etapa', 'badge-stage', implode('', [
    ob_start() . _ntItem('1. Checklist de Triagem (Operação)',
        'Modelo que executa o checklist de triagem, onde a equipe avalia o que é necessário para executar o trabalho da oportunidade — para a oportunidade primária, o que é preciso para a execução; para as complementares, o estudo de viabilidade. A equipe seleciona os documentos e dados que precisam ser fornecidos pelo cliente/parceiro. O card só pode avançar após a conclusão deste checklist — existe uma trava que impede o avanço sem sua conclusão.',
        _ntTag('tag-modelo','Modelo')) . ob_get_clean(),
    ob_start() . _ntItem('2. Solicitar Documentos → Coleta',
        'Quando a triagem identifica que documentos ou dados precisam ser coletados, aciona "Solicitar Documentos". O card é movido automaticamente para a etapa Coleta de Documentos, onde o responsável poderá anexar o que foi solicitado.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
    ob_start() . _ntItem('3. Aprovar → Relatório Preliminar',
        'Quando a triagem valida que todos os dados e documentos necessários estão corretos e completos, aprova o card. Ele avança automaticamente para a etapa Relatório Preliminar (Diagnóstico). Caso contrário, pode solicitar mais dados ou correções, retornando o card para Coleta de Documentos.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
    ob_start() . _ntItem('4. Trava de movimentação manual',
        'O card não pode ser movido manualmente para fora desta etapa enquanto o checklist não for concluído. Se houver tentativa de movimentação manual, o sistema reverte o card de volta à Triagem, garantindo que o processo de validação seja sempre cumprido.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
])); ?>

<?php _ntSection('Relatório Preliminar (Diagnóstico)', 'Etapa', 'badge-stage', implode('', [
    ob_start() . _ntItem('1. Geração de Tarefa de Diagnóstico',
        'Ao entrar nesta etapa, o sistema busca no cadastro da oportunidade vinculada (funil Oportunidades/Produtos) os dados necessários para a execução: responsável, participantes e o checklist de atividades definido para aquela oportunidade. Com esses dados, cria automaticamente uma tarefa atribuída às pessoas corretas e com o checklist já configurado.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
    ob_start() . _ntItem('2. Conclusão automática por preenchimento de campos',
        'A tarefa não precisa ser concluída manualmente — o sistema monitora dois campos do card: <strong>Valor de Diagnóstico</strong> e <strong>Anexo de Diagnóstico</strong>. Quando ambos são preenchidos, a tarefa é finalizada automaticamente e o card avança para a etapa <strong>Aguardando Closer</strong>.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
])); ?>

<?php _ntSection('Aguardando Closer', 'Etapa', 'badge-stage', implode('', [
    ob_start() . _ntItem('1. Envio dos dados do diagnóstico ao Closer',
        'Ao chegar nesta etapa, um modelo envia automaticamente para o card do Closer os arquivos, o valor e os dados do diagnóstico concluído. Em seguida, verifica quantos diagnósticos já foram concluídos:<br><br><strong>Se ainda há diagnósticos pendentes:</strong> move o Closer para a etapa <strong>Diagnóstico Parcialmente Realizado</strong>.<br><br><strong>Se é o último diagnóstico:</strong> move o Closer para <strong>Diagnóstico Realizado</strong>.<br><br>Este fluxo roda a cada diagnóstico concluído, acumulando os dados até que todos estejam completos.',
        _ntTag('tag-modelo','Modelo')) . ob_get_clean(),
    ob_start() . _ntItem('2. Verificação do status do Closer',
        'Após enviar os dados ao Closer, verifica se o card do Closer já está na etapa <strong>Concluído</strong>:<br><br><strong>Closer já concluído:</strong> o card não precisa esperar — segue imediatamente para o funil correto (Contencioso ou Operacional) conforme o Tipo de Processo Operacional.<br><br><strong>Closer ainda não concluído:</strong> o card permanece em Aguardando Closer, mesmo que todos os diagnósticos daquele Closer já tenham sido concluídos. Só avança quando o Closer for concluído.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
    ob_start() . _ntItem('3. Distribuição ao concluir o Closer',
        'Quando o card do Closer é movido para Concluído, um fluxo percorre todos os cards vinculados que estão em Aguardando Closer e os distribui:<br><br><strong>Oportunidades Convertidas (aprovadas):</strong> enviadas para o funil correto conforme Tipo de Processo Operacional — Contencioso ou Operacional.<br><br><strong>Oportunidades não convertidas:</strong> movidas para <strong>Perdido</strong>.',
        _ntTag('tag-etapa','Via Closer'), 'trigger') . ob_get_clean(),
    ob_start() . _ntItem('4. Gatilho de 30 dias',
        'Após 30 dias parado nesta etapa sem que o Closer tenha sido concluído, o card avança automaticamente para <strong>Aguardando Closer (+30 Dias)</strong>. Serve como marcador de tempo para identificar casos com processo comercial mais longo.',
        _ntTag('tag-etapa','Gatilho'), 'trigger') . ob_get_clean(),
])); ?>

<?php _ntSection('Aguardando Closer (+30 Dias)', 'Etapa', 'badge-stage', implode('', [
    ob_start() . _ntItem('Segue o mesmo processo de Aguardando Closer',
        'Esta etapa possui as mesmas automações de <strong>Aguardando Closer</strong>: o sistema continua monitorando o card do Closer vinculado e, quando ele for concluído, distribui os cards conforme o Tipo de Processo Operacional — oportunidades convertidas vão para Contencioso ou Operacional; não convertidas vão para Perdido.<br><br>A diferença é apenas de tempo: cards que chegam aqui ficaram 30 dias em Aguardando Closer sem que o Closer fosse concluído. O processo em si não muda.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
])); ?>

<?php _ntSection('Suspenso', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Proposta', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Proposta +30 Dias', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Documentos Incompletos', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>

<?php _ntSection('Operacional', 'Etapa', 'badge-stage', implode('', [
    ob_start() . _ntItem('1. Roteamento por Tipo de Processo Operacional',
        'Ao chegar nesta etapa — manualmente ou por automação — o sistema lê o campo Tipo de Processo Operacional e roteia o card para o funil correto: <strong>Contencioso</strong> (se Contencioso Ativo, Passivo ou Cível) ou <strong>Operacional</strong> (se Administrativo ou Administrativo Anexo V). O comportamento é o mesmo que ocorre na conclusão do Closer.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
])); ?>

<?php _ntSection('Concluído', 'Etapa', 'badge-stage', implode('', [
    ob_start() . _ntItem('1. Roteamento final',
        'Ao chegar em Concluído, o sistema faz a mesma análise que ocorre em Aguardando Closer: verifica o Tipo de Processo Operacional e distribui o card para o funil correto — Contencioso ou Operacional. Cobre os casos em que o card chega a Concluído manualmente ou por outros caminhos que não a conclusão do Closer.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
])); ?>

<?php _ntSection('Encerramento', 'Lixeira · Sem valor · Fechado · Sem Interesse · Perdidos', 'badge-empty', _ntEmpty('A preencher'), false); ?>

</div><!-- /bc-page-stages -->

<div class="bc-page-aside">
<?php
ob_start();
_ntStdAside(
    ob_start() .
    _ntAsideItem('Vinculação Nimbus TAX na Empresa', 'Automação geral que, quando o negócio passa pelo processo de diagnóstico ou operação, atualiza o campo <strong>Empresa Vinculada</strong> no cadastro da empresa do card, incluindo a Nimbus TAX na lista. Esse campo reúne todas as empresas do grupo que têm relacionamento com aquela empresa cliente.') .
    _ntAsideItem('Detalhes da Etapa', 'Fluxo que atualiza automaticamente o campo <strong>Detalhes da Etapa</strong> no card sempre que ele muda de etapa (apenas nas etapas configuradas, não em todas). O campo exibe um texto explicativo sobre o que aquela etapa representa e o que é esperado nela. Está funcionando mas aguarda revisão e atualização dos textos.') .
    _ntAsideItem('Controle de Acesso por Parceiro', 'Fluxo geral que garante a segmentação de acesso entre parceiros. Lê o campo Parceiro Comercial do card e busca o campo Supervisor do Parceiro no cadastro desse parceiro — um colaborador da equipe do parceiro cadastrado no Bitrix24. Atribui essa pessoa como responsável pelo card, dando acesso automático a ela e a todo o departamento ao qual pertence. Com isso, cada parceiro enxerga apenas os próprios casos, sem acesso aos de outros parceiros.') .
    _ntAsideItem('APIs do Tributário', 'Conjunto de aproximadamente 13 chamadas de API, todas seguindo o mesmo padrão: a automação monta o endpoint com os dados e arquivos do card/pastas do negócio, envia para processamento externo, aguarda o retorno via campo de retorno de API e, ao receber, organiza os arquivos e resultados de volta no card.<br><br>Não está vinculada a nenhuma etapa específica — pode ser acionada em qualquer momento dentro do funil.<br><br><em>Desenvolvida e mantida por Léo e Hélio (equipe externa).</em>') .
    _ntAsideItem('Controle de Datas de Etapa', 'Fluxo que registra automaticamente a data de entrada e saída em etapas específicas do funil (não em todas). Não é um gatilho por etapa — roda de forma independente e monitora as movimentações relevantes para fins de controle de prazo e histórico. As etapas controladas serão detalhadas futuramente.') .
    _ntAsideItem('Atualização do Valor Principal', 'Fluxo que mantém o campo nativo Valor do Bitrix24 sempre atualizado com o dado mais preciso disponível. Os valores são preenchidos em ordem crescente de precisão: Valor de Diagnóstico (primeiro estimativa, menos preciso) → Valor Operacional (refinado durante a execução) → Valor de Certificação (dado final, mais preciso). Sempre que um campo mais atualizado é preenchido, a automação sobrescreve o campo Valor com esse novo dado — garantindo que o card reflita o valor mais correto em cada momento do processo. Não está vinculada a nenhuma etapa específica.') .
    ob_get_clean()
);
_ntAsideBox('Automações Gerais do Funil', '', ob_get_clean());

ob_start();
_ntCampoItem('Oportunidade', 'A oportunidade específica sendo trabalhada neste card, vinculada ao cadastro do funil Oportunidades/Produtos. Define o escopo do trabalho, o responsável e o checklist de execução.');
_ntCampoItem('Tipo de Oportunidade', 'Indica se esta oportunidade é <strong>Primária</strong> (primeira oportunidade do negócio, criada na Triagem) ou <strong>Complementar</strong> (identificada durante o diagnóstico, criada na Execução do Diagnóstico).');
_ntCampoItem('Negócio', 'Vínculo ao card do Closer que originou este card. Permite navegar diretamente entre o funil comercial e o operacional.');
_ntCampoItem('Empresa', 'Empresa cliente vinculada ao card. Copiada do Closer na criação do card.');
_ntCampoItem('Parceiro Comercial', 'Parceiro responsável pela indicação do cliente. Usado pelo Controle de Acesso por Parceiro para definir quem pode ver este card.');
_ntCampoItem('Responsável SDR', 'Responsável pelo atendimento comercial do lead. Copiado do Closer na criação do card para manter rastreabilidade da origem comercial.');
_ntCampoItem('Tipo de Processo Operacional', 'Determina para qual funil o card será enviado ao concluir o diagnóstico: <strong>Administrativo / Administrativo Anexo V</strong> → Operacional; <strong>Contencioso Ativo</strong> → Contencioso (depois Operacional); <strong>Contencioso Passivo / Cível</strong> → Contencioso (permanente). Copiado do Closer.');
_ntCampoItem('Valor de Diagnóstico', 'Valor estimado ao realizar o diagnóstico. É o primeiro e menos preciso dos valores — será refinado nas etapas seguintes. Quando preenchido junto com o Anexo de Diagnóstico, finaliza a tarefa e move o card para Aguardando Closer.');
_ntCampoItem('Anexo de Diagnóstico', 'Arquivo com o resultado do diagnóstico. Quando preenchido junto com o Valor de Diagnóstico, o sistema finaliza automaticamente a tarefa de diagnóstico e avança o card para Aguardando Closer.');
_ntCampoItem('Comentário Resumo', 'Campo de entrada para registrar observações. Ao ser salvo, aciona a automação Resumo do Negócio, que insere o conteúdo no campo Resumo com carimbo de data/hora e limpa este campo automaticamente.');
_ntCampoItem('Resumo', 'Histórico acumulativo de todos os comentários registrados no card, mantido pela automação Resumo do Negócio. Não deve ser editado manualmente.');
_ntCampoItem('Detalhes da Etapa', 'Texto explicativo atualizado automaticamente conforme o card muda de etapa. Descreve o que a etapa atual representa e o que se espera nela.');
_ntAsideBox('Campos Utilizados', 'campos', ob_get_clean());
?>
</div><!-- /bc-page-aside -->
</div><!-- /bc-page-layout -->
</div><!-- /bc-nt-rel-preliminar -->


<!-- ██████████████████████████████████████████████████████████████████████
     OPERACIONAL
█████████████████████████████████████████████████████████████████████ -->
<div class="bc-page" id="bc-nt-operacional">

<div class="bc-page-header">
    <h1>Operacional</h1>
    <div class="bc-page-meta">Funil: Deals · Categoria: 15 · ENTITY_ID de etapas: DEAL_STAGE_15</div>
    <div class="bc-page-intro">
        O funil Operacional é onde acontece a <strong>execução do trabalho tributário</strong>. Os cards chegam aqui vindos do Relatório Preliminar, após o Closer concluir o negócio e confirmar as oportunidades convertidas.<br><br>
        O fluxo segue a mesma estrutura do Relatório Preliminar — coleta de documentos, triagem e execução — mas com um nível de análise mais completo e técnico. Em vez de um diagnóstico superficial, aqui é feita a execução real: análise aprofundada dos documentos da oportunidade e elaboração do relatório operacional definitivo.<br><br>
        Ao preencher o Valor Operacional e o Anexo Operacional, o card avança automaticamente para <strong>Autorização do Cliente</strong>, iniciando o processo de entrega do resultado.
    </div>
</div>

<div class="bc-page-layout">
<div class="bc-page-stages">

<?php _ntSection('Coleta de Documentos (Parceiro)', 'Etapa', 'badge-stage', implode('', [
    ob_start() . _ntItem('1. Apresentação das pendências da Triagem',
        'Ao entrar nesta etapa, todas as informações e documentos solicitados pela Triagem são apresentados no card — tanto a lista de documentos necessários quanto as observações e correções apontadas. O responsável pode então anexar os arquivos e dados solicitados diretamente no card.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
    ob_start() . _ntItem('2. Checklist de Coleta (Operação)',
        'O mesmo modelo de checklist que roda na Triagem também é executado aqui, garantindo que o processo de coleta siga o mesmo padrão de controle. O card só pode avançar após a conclusão do checklist — existe uma trava que impede o avanço sem que ele seja concluído.',
        _ntTag('tag-modelo','Modelo')) . ob_get_clean(),
    ob_start() . _ntItem('3. Retorno automático à Triagem',
        'Uma vez concluída a coleta e salvo o checklist, o card retorna automaticamente para a etapa Triagem para nova validação. A Triagem pode então aprovar, solicitar correções ou pedir documentos adicionais.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
])); ?>

<?php _ntSection('Triagem (CheckList Operação)', 'Etapa', 'badge-stage', implode('', [
    ob_start() . _ntItem('1. Checklist de Triagem (Operação)',
        'Modelo que executa o checklist de triagem, onde a equipe avalia o que é necessário para executar o trabalho da oportunidade. A equipe seleciona os documentos e dados que precisam ser fornecidos pelo cliente/parceiro. O card só pode avançar após a conclusão deste checklist — existe uma trava que impede o avanço sem sua conclusão.',
        _ntTag('tag-modelo','Modelo')) . ob_get_clean(),
    ob_start() . _ntItem('2. Solicitar Documentos → Coleta',
        'Quando a triagem identifica que documentos ou dados precisam ser coletados, aciona "Solicitar Documentos". O card é movido automaticamente para a etapa Coleta de Documentos, onde o responsável poderá anexar o que foi solicitado.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
    ob_start() . _ntItem('3. Aprovar → Execução Operação',
        'Quando a triagem valida que todos os dados e documentos necessários estão corretos e completos, aprova o card. Ele avança automaticamente para a etapa Execução Operação. Caso contrário, pode solicitar mais dados ou correções, retornando o card para Coleta de Documentos.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
    ob_start() . _ntItem('4. Trava de movimentação manual',
        'O card não pode ser movido manualmente para fora desta etapa enquanto o checklist não for concluído. Se houver tentativa de movimentação manual, o sistema reverte o card de volta à Triagem, garantindo que o processo de validação seja sempre cumprido.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
])); ?>

<?php _ntSection('Execução Operação', 'Etapa', 'badge-stage', implode('', [
    ob_start() . _ntItem('1. Geração de Tarefa de Execução',
        'Ao entrar nesta etapa, o sistema busca no cadastro da oportunidade vinculada (funil Oportunidades/Produtos) os dados necessários para a execução: responsável, participantes e o checklist de atividades definido para aquela oportunidade. Com esses dados, cria automaticamente uma tarefa atribuída às pessoas corretas e com o checklist já configurado.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
    ob_start() . _ntItem('2. Conclusão automática por preenchimento de campos',
        'A tarefa não precisa ser concluída manualmente — o sistema monitora dois campos do card: <strong>Valor Operacional</strong> e <strong>Anexo Operacional</strong>. Quando ambos são preenchidos, a tarefa é finalizada automaticamente e o card avança para a etapa <strong>Autorização do Cliente</strong>.',
        _ntTag('tag-modelo','Automação de Etapa')) . ob_get_clean(),
])); ?>

<?php _ntSection('Execução Operação Externa', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Autorização do Cliente', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Reunião de Entrega', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Retificação', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Suspenso', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Aguardando Contencioso (Parceiro)', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Aguardando Contencioso', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Documentos Incompletos', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Encerramento', 'Concluido · Lixeira · Sem valor · Fechado · Sem Interesse · Perdidos', 'badge-empty', _ntEmpty('A preencher'), false); ?>

</div><!-- /bc-page-stages -->

<div class="bc-page-aside">
<?php
ob_start();
_ntStdAside(
    ob_start() .
    _ntAsideItem('Vinculação Nimbus TAX na Empresa', 'Automação geral que, quando o negócio passa pelo processo de diagnóstico ou operação, atualiza o campo <strong>Empresa Vinculada</strong> no cadastro da empresa do card, incluindo a Nimbus TAX na lista. Esse campo reúne todas as empresas do grupo que têm relacionamento com aquela empresa cliente.') .
    _ntAsideItem('Detalhes da Etapa', 'Fluxo que atualiza automaticamente o campo <strong>Detalhes da Etapa</strong> no card sempre que ele muda de etapa (apenas nas etapas configuradas, não em todas). O campo exibe um texto explicativo sobre o que aquela etapa representa e o que é esperado nela. Está funcionando mas aguarda revisão e atualização dos textos.') .
    _ntAsideItem('Controle de Acesso por Parceiro', 'Fluxo geral que garante a segmentação de acesso entre parceiros. Lê o campo Parceiro Comercial do card e busca o campo Supervisor do Parceiro no cadastro desse parceiro — um colaborador da equipe do parceiro cadastrado no Bitrix24. Atribui essa pessoa como responsável pelo card, dando acesso automático a ela e a todo o departamento ao qual pertence. Com isso, cada parceiro enxerga apenas os próprios casos, sem acesso aos de outros parceiros.') .
    _ntAsideItem('APIs do Tributário', 'Conjunto de aproximadamente 13 chamadas de API, todas seguindo o mesmo padrão: a automação monta o endpoint com os dados e arquivos do card/pastas do negócio, envia para processamento externo, aguarda o retorno via campo de retorno de API e, ao receber, organiza os arquivos e resultados de volta no card.<br><br>Não está vinculada a nenhuma etapa específica — pode ser acionada em qualquer momento dentro do funil.<br><br><em>Desenvolvida e mantida por Léo e Hélio (equipe externa).</em>') .
    _ntAsideItem('Controle de Datas de Etapa', 'Fluxo que registra automaticamente a data de entrada e saída em etapas específicas do funil (não em todas). Não é um gatilho por etapa — roda de forma independente e monitora as movimentações relevantes para fins de controle de prazo e histórico. As etapas controladas serão detalhadas futuramente.') .
    _ntAsideItem('Atualização do Valor Principal', 'Fluxo que mantém o campo nativo Valor do Bitrix24 sempre atualizado com o dado mais preciso disponível. Os valores são preenchidos em ordem crescente de precisão: Valor de Diagnóstico → Valor Operacional → Valor de Certificação. Sempre que um campo mais atualizado é preenchido, a automação sobrescreve o campo Valor com esse novo dado. Não está vinculada a nenhuma etapa específica.') .
    ob_get_clean()
);
_ntAsideBox('Automações Gerais do Funil', '', ob_get_clean());
?>
</div><!-- /bc-page-aside -->
</div><!-- /bc-page-layout -->
</div><!-- /bc-nt-operacional -->


<!-- ██████████████████████████████████████████████████████████████████████
     RETIFICAÇÃO & FATURAMENTO
█████████████████████████████████████████████████████████████████████ -->
<div class="bc-page" id="bc-nt-retificacao">

<div class="bc-page-header">
    <h1>Retificação &amp; Faturamento</h1>
    <div class="bc-page-meta">Funil: Deals · Categoria: 51 · ENTITY_ID de etapas: DEAL_STAGE_51</div>
</div>

<div class="bc-page-layout">
<div class="bc-page-stages">
<?php _ntSection('Distribuição de trabalhos', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Informações Faltando', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Aguardando Negócio', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Retificação de Declarações', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Pendências Contencioso', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Pendências Administrativas', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Abertura de Processo', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Liberação de Crédito / Liquidação', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Liberação de Crédito (+360 dias)', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Habilitação de Crédito', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Defesa / Complementação', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Conferência da Retificação', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Compensação de Crédito', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Acompanhamento Administrativo', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Suspenso', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Documentos Incompletos', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Encerramento', 'Concluido · Lixeira · Sem valor · Fechado · Sem Interesse · Perdidos', 'badge-empty', _ntEmpty('A preencher'), false); ?>
</div><!-- /bc-page-stages -->

<div class="bc-page-aside">
<?php
ob_start();
_ntStdAside(
    ob_start() .
    _ntAsideItem('Vinculação Nimbus TAX na Empresa', 'Automação geral que, quando o negócio passa pelo processo de diagnóstico ou operação, atualiza o campo <strong>Empresa Vinculada</strong> no cadastro da empresa do card, incluindo a Nimbus TAX na lista.') .
    _ntAsideItem('Detalhes da Etapa', 'Fluxo que atualiza automaticamente o campo <strong>Detalhes da Etapa</strong> no card sempre que ele muda de etapa (apenas nas etapas configuradas, não em todas).') .
    _ntAsideItem('Controle de Acesso por Parceiro', 'Fluxo geral que garante a segmentação de acesso entre parceiros. Lê o campo Parceiro Comercial do card e busca o campo Supervisor do Parceiro no cadastro desse parceiro, atribuindo essa pessoa como responsável pelo card.') .
    _ntAsideItem('APIs do Tributário', 'Conjunto de aproximadamente 13 chamadas de API. Não está vinculada a nenhuma etapa específica — pode ser acionada em qualquer momento dentro do funil.<br><br><em>Desenvolvida e mantida por Léo e Hélio (equipe externa).</em>') .
    _ntAsideItem('Controle de Datas de Etapa', 'Fluxo que registra automaticamente a data de entrada e saída em etapas específicas do funil (não em todas).') .
    _ntAsideItem('Atualização do Valor Principal', 'Fluxo que mantém o campo nativo Valor do Bitrix24 sempre atualizado com o dado mais preciso disponível. Não está vinculada a nenhuma etapa específica.') .
    ob_get_clean()
);
_ntAsideBox('Automações Gerais do Funil', '', ob_get_clean());
?>
</div><!-- /bc-page-aside -->
</div><!-- /bc-page-layout -->
</div><!-- /bc-nt-retificacao -->


<!-- ██████████████████████████████████████████████████████████████████████
     CONSULTORIA
█████████████████████████████████████████████████████████████████████ -->
<div class="bc-page" id="bc-nt-consultoria">

<div class="bc-page-header">
    <h1>Consultoria</h1>
    <div class="bc-page-meta">Funil: Deals · Categoria: 77 · ENTITY_ID de etapas: DEAL_STAGE_77</div>
</div>

<div class="bc-page-layout">
<div class="bc-page-stages">
<?php _ntSection('Triagem', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Em Andamento', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Suspenso', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Encerramento', 'Concluído/Entregue · Negócios Não Fechados', 'badge-empty', _ntEmpty('A preencher'), false); ?>
</div><!-- /bc-page-stages -->

<div class="bc-page-aside">
<?php
ob_start();
_ntStdAside(
    ob_start() .
    _ntAsideItem('Controle de Acesso por Parceiro', 'Fluxo geral que garante a segmentação de acesso entre parceiros. Lê o campo Parceiro Comercial do card e busca o campo Supervisor do Parceiro no cadastro desse parceiro, atribuindo essa pessoa como responsável pelo card e dando acesso ao departamento ao qual pertence.') .
    ob_get_clean()
);
_ntAsideBox('Automações Gerais do Funil', '', ob_get_clean());
?>
</div><!-- /bc-page-aside -->
</div><!-- /bc-page-layout -->
</div><!-- /bc-nt-consultoria -->


<!-- ██████████████████████████████████████████████████████████████████████
     CONTENCIOSO
█████████████████████████████████████████████████████████████████████ -->
<div class="bc-page" id="bc-nt-contencioso">

<div class="bc-page-header">
    <h1>Contencioso</h1>
    <div class="bc-page-meta">Funil: Deals · Categoria: 55 · ENTITY_ID de etapas: DEAL_STAGE_55</div>
</div>

<div class="bc-page-layout">
<div class="bc-page-stages">
<?php _ntSection('Triagem', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Solicitação de Documentos', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Elaboração de Petição', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Protocolado — 1º Grau', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('2º Grau — Tribunais', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Tribunais Superiores', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Processos Administrativos', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Casos com TAX VISION / BGL', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Encerramento', 'Encerrado Favorável · Desfavorável · Sem solução', 'badge-empty', _ntEmpty('A preencher'), false); ?>
</div><!-- /bc-page-stages -->

<div class="bc-page-aside">
<?php
ob_start();
_ntStdAside(
    ob_start() .
    _ntAsideItem('Vinculação Nimbus TAX na Empresa', 'Automação geral que atualiza o campo <strong>Empresa Vinculada</strong> no cadastro da empresa do card, incluindo a Nimbus TAX na lista.') .
    _ntAsideItem('Detalhes da Etapa', 'Fluxo que atualiza automaticamente o campo <strong>Detalhes da Etapa</strong> no card sempre que ele muda de etapa (apenas nas etapas configuradas, não em todas).') .
    ob_get_clean()
);
_ntAsideBox('Automações Gerais do Funil', '', ob_get_clean());
?>
</div><!-- /bc-page-aside -->
</div><!-- /bc-page-layout -->
</div><!-- /bc-nt-contencioso -->


<!-- ██████████████████████████████████████████████████████████████████████
     PARCEIROS
█████████████████████████████████████████████████████████████████████ -->
<div class="bc-page" id="bc-nt-parceiros">

<div class="bc-page-header">
    <h1>Parceiros</h1>
    <div class="bc-page-meta">Funil: Deals · Categoria: 59 · ENTITY_ID de etapas: DEAL_STAGE_59</div>
</div>

<div class="bc-page-layout">
<div class="bc-page-stages">
<?php _ntSection('Lead', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('1º Contato', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('E-mail Visualizado', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('E-mail Respondido', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Breakup', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Em contato', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Reunião de Fechamento', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Cadastro de Parceiro', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Enviado para Assinatura', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Contratos Assinados', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Novos Parceiros', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Acompanhamento Parceria', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Suspenso', 'Etapa', 'badge-stage', _ntEmpty('A preencher'), false); ?>
<?php _ntSection('Encerramento', 'Parceria Declinada', 'badge-empty', _ntEmpty('A preencher'), false); ?>
</div><!-- /bc-page-stages -->

<div class="bc-page-aside">
<?php
ob_start();
_ntStdAside();
_ntAsideBox('Automações Gerais do Funil', '', ob_get_clean());
?>
</div><!-- /bc-page-aside -->
</div><!-- /bc-page-layout -->
</div><!-- /bc-nt-parceiros -->

