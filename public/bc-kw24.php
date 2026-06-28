<?php
/**
 * bc-kw24.php — Base de Conhecimento — KW24
 * Included from base-conhecimento-public.php (empresa=kw24).
 * Starts directly with .bc-page divs (no HTML shell, no sidebar).
 * Automations not yet documented — sections use .bc-stage-desc only.
 */

function _kwSection($title, $body) { ?>
<div class="section">
    <div class="section-header" onclick="bcAuto.toggle(this)">
        <div class="section-title"><?= $title ?> <span class="section-badge badge-stage">Etapa</span></div>
        <div class="section-toggle">▾</div>
    </div>
    <div class="section-body"><?= $body ?></div>
</div>
<?php }

function _kwDesc($text) {
    return '<p class="bc-stage-desc">' . $text . '</p>';
}
?>


<!-- ██████████████████████████████████████████████████████████████████████
     ATENDIMENTO
█████████████████████████████████████████████████████████████████████ -->
<div class="bc-page active" id="kw24-atendimento">

<div class="bc-page-header">
    <h1>Atendimento</h1>
    <div class="bc-page-meta">Funil 1 · Triagem e primeiro atendimento das demandas</div>
    <div class="bc-page-intro">
        Responsável pela entrada e triagem das demandas, com três etapas.
        Demandas chegam pelo canal de suporte do Bitrix24 ou pelo WhatsApp,
        integrados a um robô de atendimento que realiza o primeiro contato.
    </div>
</div>

<?php _kwSection('Entrada de Solicitação', _kwDesc('A demanda é registrada e o primeiro contato com o cliente é realizado.')); ?>
<?php _kwSection('Solicitação sem Resposta', _kwDesc('Caso o cliente não responda em até 30 minutos, a demanda é movida para essa etapa, onde aguarda retorno.')); ?>
<?php _kwSection('Viabilidade', _kwDesc('Quando há necessidade de análise mais aprofundada para entender o problema ou avaliar se é possível atender. Utilizada quando o atendente precisa de apoio da equipe. Ao sair, a demanda é direcionada para o funil de Projetos ou diretamente para Execução.')); ?>

</div><!-- /kw24-atendimento -->


<!-- ██████████████████████████████████████████████████████████████████████
     PROJETOS
█████████████████████████████████████████████████████████████████████ -->
<div class="bc-page" id="kw24-projetos">

<div class="bc-page-header">
    <h1>Projetos</h1>
    <div class="bc-page-meta">Funil 2 · Planejamento, proposta formal e aprovação do cliente</div>
    <div class="bc-page-intro">
        Destinado a demandas que exigem planejamento, proposta e aprovação.
    </div>
</div>

<?php _kwSection('Solicitações de Projeto',              _kwDesc('Demandas que requerem desenvolvimento mais elaborado.')); ?>
<?php _kwSection('Solicitações de Contrato',             _kwDesc('Demandas que envolvem formalização contratual. Coexiste com Solicitações de Projeto — naturezas distintas, mesmo fluxo.')); ?>
<?php _kwSection('Elaboração de Projeto',                _kwDesc('Análise e planejamento da solução a ser entregue.')); ?>
<?php _kwSection('Elaboração de Projeto Rápido',         _kwDesc('Para demandas identificadas como simples, com menor necessidade de reuniões, agilizando a entrega da proposta.')); ?>
<?php _kwSection('Reunião Agendada',                     _kwDesc('Uma reunião com o cliente foi marcada para alinhamento.')); ?>
<?php _kwSection('Conferência da Proposta e Aprovação',  _kwDesc('Revisão interna da proposta antes de enviá-la ao cliente.')); ?>
<?php _kwSection('Aguardando Aprovação do Cliente',      _kwDesc('Proposta enviada e em análise pelo cliente.')); ?>
<?php _kwSection('Assinatura',                           _kwDesc('Proposta aprovada encaminhada para assinatura via ClickSign.')); ?>
<?php _kwSection('Assinado e Aprovado',                  _kwDesc('Contrato assinado, pronto para iniciar a execução.')); ?>
<?php _kwSection('Projetos Futuros',                     _kwDesc('Demandas em que o cliente demonstrou interesse mas optou por não contratar no momento.')); ?>

</div><!-- /kw24-projetos -->


<!-- ██████████████████████████████████████████████████████████████████████
     EXECUÇÃO
█████████████████████████████████████████████████████████████████████ -->
<div class="bc-page" id="kw24-execucao">

<div class="bc-page-header">
    <h1>Execução</h1>
    <div class="bc-page-meta">Funil 3 · Principal funil operacional da equipe</div>
    <div class="bc-page-intro">
        Organizado para dar visibilidade individual e coletiva da carga de trabalho,
        priorizando agilidade na resolução.
    </div>
</div>

<?php _kwSection('Fila de Desenvolvimento',         _kwDesc('Demandas em espera para execução, priorizadas em alinhamento com a equipe em reuniões diárias.')); ?>
<?php _kwSection('Fila de Suporte',                 _kwDesc('Atendimentos técnicos e de Bitrix24, resolvidos por quem estiver disponível, sem fila rígida, priorizando agilidade.')); ?>
<?php _kwSection('Demandas Internas e Programadas', _kwDesc('Solicitações recorrentes geradas automaticamente por automações do sistema, como cadastros e inventários.')); ?>
<?php _kwSection('Pendente Cliente',                _kwDesc('Demandas pausadas aguardando retorno do cliente por prazo mais longo.')); ?>
<?php /* Esta seção terá futuramente uma sub-seção por colaborador, com as demandas de cada membro visíveis individualmente. */
      _kwSection('Em Execução por Colaborador',     _kwDesc('Cada membro da equipe possui sua própria etapa, onde ficam as demandas que está executando no momento. Permite visibilidade individual da carga de trabalho.')); ?>
<?php _kwSection('Finalizados',                     _kwDesc('Demandas concluídas e encerradas.')); ?>
<?php _kwSection('Duplicado ou Não Possível',       _kwDesc('Chamados repetidos são unificados em um único atendimento. Demandas inviáveis são registradas e arquivadas.')); ?>

</div><!-- /kw24-execucao -->


<!-- ██████████████████████████████████████████████████████████████████████
     CANAIS DE ENTRADA
█████████████████████████████████████████████████████████████████████ -->
<div class="bc-page" id="kw24-canais">

<div class="bc-page-header">
    <h1>Canais de Entrada</h1>
    <div class="bc-page-meta">Como as demandas chegam à equipe</div>
    <div class="bc-page-intro">
        As demandas chegam pelo canal de suporte do Bitrix24 ou pelo WhatsApp,
        ambos integrados a um robô de atendimento que realiza o primeiro contato
        e encaminha o caso para a equipe.
    </div>
</div>

<div class="bc-empty">Conteúdo em construção. Em breve mais detalhes sobre os canais.</div>

</div><!-- /kw24-canais -->
