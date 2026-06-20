"""
Consultas do Funil Diagnóstico (NimbusTax) — réplica da lógica do Power BI.

Notas:
- psycopg2 com parâmetros nomeados %(nome)s; o mesmo parâmetro pode repetir sem erro.
- As expressões de status/etapa (STATUS_CASE / ETAPA_ORDENADA_CASE) usam alias "n."
  → todas as queries usam FROM tbl_negocio n.
- Filtro por parceiro: PARCEIRO_COLUMN ligado (multi-tenant via ?parceiro= na URL).
- valor NULL é tratado como 0 via COALESCE(SUM(...), 0).

⚠️ Chaves de saída mantidas como `total` / `valor_soma` / `etapa_ordenada` / `status`
   porque o app.py consome esses nomes. A lógica (CASE, COUNT/SUM, ordenação) é a do Power BI.
"""

from db import fetch_all, fetch_one

# ── Pipelines (funis) ────────────────────────────────────────────────────────
# Chave usada na URL/store  →  nome do pipeline em tbl_negocio.pipeline.
PIPELINES = {
    "diagnostico": "RELATÓRIO PRELIMINAR (DIAGNOST)",
    "operacional": "OPERACIONAL",
    "retificacao": "RETIFICAÇÃO & FATURAMENTO",
}
PIPELINE_DIAGNOSTICO    = PIPELINES["diagnostico"]
PIPELINE_ID_DIAGNOSTICO = 17  # (referência; não usado nas queries atuais)

# Coluna que identifica o parceiro em tbl_negocio (multi-tenant).
PARCEIRO_COLUMN = "parceiro_comercial_id"

# ── Status (réplica exata do Power BI) ───────────────────────────────────────
STATUS_CASE = """
CASE
    WHEN n.etapa IN (
        'Sem Interesse','Sem valor de crédito','Perdidos',
        'Fechado com outra empresa','Lixeira','Documentos Incompletos'
    ) THEN 'Sem Oportunidade'
    WHEN n.etapa = 'Suspenso' THEN 'Suspenso'
    WHEN n.pipeline = 'RELATÓRIO PRELIMINAR (DIAGNOST)' THEN 'Em Diagnóstico'
    ELSE 'Com Oportunidade'
END
"""

# ── Etapa Ordenada (réplica da coluna "Etapa Ordenada" do Power BI) ──────────
ETAPA_ORDENADA_CASE = """
CASE
    WHEN n.pipeline = 'RELATÓRIO PRELIMINAR (DIAGNOST)' THEN
        CASE n.etapa
            WHEN 'Coleta de Documentos (Parceiro)'      THEN '01 - Coleta de documentos'
            WHEN 'Triagem (CheckList Operação)'          THEN '02 - Triagem'
            WHEN 'Relatório Preliminar (Diagnóstico)'    THEN '03 - Relatório preliminar'
            WHEN 'Aguardando Closer'                     THEN '04 - Aguard. closer'
            WHEN 'Aguardando Closer (+ 30 Dias)'         THEN '05 - Aguard. closer +30d'
            WHEN 'Suspenso'                              THEN '06 - Suspenso'
            WHEN 'Proposta'                              THEN '07 - Proposta'
            WHEN 'Proposta +30 Dias'                     THEN '08 - Proposta +30d'
            WHEN 'Contrato enviado p/ assinatura'        THEN '09 - Contrato p/ assinatura'
            WHEN 'Operacional'                           THEN '10 - Operacional'
            WHEN 'Lixeira'                               THEN '12 - Lixeira'
            WHEN 'Sem valor de crédito'                  THEN '13 - Sem valor de crédito'
            WHEN 'Fechado com outra empresa'             THEN '14 - Fechado c/ outra empresa'
            WHEN 'Sem Interesse'                         THEN '15 - Sem interesse'
            WHEN 'Documentos Incompletos'                THEN '16 - Docs incompletos'
            WHEN 'Perdidos'                              THEN '17 - Perdidos'
            WHEN 'Concluído'                             THEN '18 - Concluído'
            WHEN 'Concluido'                             THEN '18 - Concluído'
            ELSE '99 - ' || n.etapa
        END
    WHEN n.pipeline = 'OPERACIONAL' THEN
        CASE n.etapa
            WHEN 'Coleta de Documentos (Parceiro)'   THEN '01 - Coleta de Documentos (Parceiro)'
            WHEN 'Triagem (CheckList Operação)'      THEN '02 - Triagem (CheckList Operação)'
            WHEN 'Execução Operação'                 THEN '03 - Execução Operação'
            WHEN 'Autorização do Cliente'            THEN '04 - Autorização do Cliente'
            WHEN 'Suspenso'                          THEN '05 - Suspenso'
            WHEN 'Aguardando Contencioso (Parceiro)' THEN '06 - Aguardando Contencioso (Parceiro)'
            WHEN 'Aguardando Contencioso'            THEN '07 - Aguardando Contencioso'
            WHEN 'Lixeira'                           THEN '08 - Lixeira'
            WHEN 'Sem valor de crédito'              THEN '09 - Sem valor de crédito'
            WHEN 'Fechado com outra empresa'         THEN '10 - Fechado com outra empresa'
            WHEN 'Sem Interesse'                     THEN '11 - Sem Interesse'
            WHEN 'Documentos Incompletos'            THEN '12 - Documentos Incompletos'
            WHEN 'Perdidos'                          THEN '13 - Perdidos'
            ELSE '99 - ' || n.etapa
        END
    WHEN n.pipeline = 'RETIFICAÇÃO & FATURAMENTO' THEN
        CASE n.etapa
            WHEN 'Distribuição de trabalhos'         THEN '01 - Distribuição de trabalhos'
            WHEN 'Informações Faltando'              THEN '02 - Informações Faltando'
            WHEN 'Retificação de Declarações'        THEN '03 - Retificação de Declarações'
            WHEN 'Pendências Contencioso'            THEN '04 - Pendências Contencioso'
            WHEN 'Pendências Administrativas'        THEN '05 - Pendências Administrativas'
            WHEN 'Abertura de Processo'              THEN '06 - Abertura de Processo'
            WHEN 'Liberação de Crédito/Liquidação'   THEN '07 - Liberação de Crédito/Liquidação'
            WHEN 'Liberação de Crédito (+ 360 dias)' THEN '08 - Liberação de Crédito (+ 360 dias)'
            WHEN 'Habilitação de Credito'            THEN '09 - Habilitação de Credito'
            WHEN 'Defesa/Complementação'             THEN '10 - Defesa/Complementação'
            WHEN 'Compensação de Crédito'            THEN '11 - Compensação de Crédito'
            WHEN 'Acompanhamento Administrativo'     THEN '12 - Acompanhamento Administrativo'
            WHEN 'Suspenso'                          THEN '13 - Suspenso'
            WHEN 'Concluido'                         THEN '14 - Concluido'
            WHEN 'Concluído'                         THEN '14 - Concluido'
            WHEN 'Sem valor de crédito'              THEN '15 - Sem valor de crédito'
            WHEN 'Sem Interesse'                     THEN '16 - Sem Interesse'
            WHEN 'Perdidos'                          THEN '17 - Perdidos'
            ELSE '99 - ' || n.etapa
        END
    ELSE '99 - ' || n.etapa
END
"""

# Etapas de 'Sem Oportunidade' agrupadas de forma PIPELINE-AGNOSTIC. A aba
# 'Sem Oportunidade' combina os três funis, então a MESMA etapa precisa cair no
# MESMO rótulo (senão "Sem valor de crédito" apareceria 3x, um por pipeline, já
# que o ETAPA_ORDENADA_CASE numera diferente em cada um). O prefixo "N - " é só
# ordenação; a UI o remove na exibição.
ETAPA_SEM_OP_CASE = """
CASE n.etapa
    WHEN 'Sem Interesse'              THEN '1 - Sem interesse'
    WHEN 'Sem valor de crédito'       THEN '2 - Sem valor de crédito'
    WHEN 'Documentos Incompletos'     THEN '3 - Documentos incompletos'
    WHEN 'Fechado com outra empresa'  THEN '4 - Fechado c/ outra empresa'
    WHEN 'Lixeira'                    THEN '5 - Lixeira'
    WHEN 'Perdidos'                   THEN '6 - Perdidos'
    ELSE '9 - ' || n.etapa
END
"""


# ── Helpers de cláusula ──────────────────────────────────────────────────────
def _parceiro_clause(parceiro):
    """Filtro opcional por parceiro (multi-tenant)."""
    if parceiro and PARCEIRO_COLUMN:
        return f" AND n.{PARCEIRO_COLUMN} = %(parceiro)s", {"parceiro": parceiro}
    return "", {}


def _data_clause(data_de, data_ate):
    """Filtro por período em criado_em (aplicado SÓ quando AMBAS as datas vêm
    preenchidas). Aplica a TODAS as visões, combinando com o cross-filter.
    Usa ::date para incluir o dia inteiro nas duas pontas."""
    if data_de and data_ate:
        return (" AND n.criado_em::date >= %(data_de)s AND n.criado_em::date <= %(data_ate)s",
                {"data_de": data_de, "data_ate": data_ate})
    return "", {}


# Etapas que compõem o status 'Sem Oportunidade' (igual ao 1º WHEN do STATUS_CASE).
SEM_OP_ETAPAS = ("'Sem Interesse','Sem valor de crédito','Perdidos',"
                 "'Fechado com outra empresa','Lixeira','Documentos Incompletos'")


def _base_clause(pipeline, modo, alias="n"):
    """Condição base (logo após WHERE) = filtro de pipeline + escopo de status.
    Retorna (condição, params).

    - 'normal'  → filtra o pipeline ativo e EXCLUI 'Sem Oportunidade'
                  (funis Diagnóstico / Operacional / Retificação).
    - 'sem_op'  → aba 'Sem Oportunidade': mostra SÓ 'Sem Oportunidade' e NÃO filtra
                  pipeline — combina os TRÊS funis (não existe filtro de funil nessa aba).

    Em 'sem_op' o param `pipeline` não entra (não há %(pipeline)s no SQL), evitando
    erro de parâmetro não usado no psycopg2."""
    if modo == "sem_op":
        return f"{alias}.etapa IN ({SEM_OP_ETAPAS})", {}
    return (f"{alias}.pipeline = %(pipeline)s AND {alias}.etapa NOT IN ({SEM_OP_ETAPAS})",
            {"pipeline": pipeline})


def _etapa_expr(modo):
    """Expressão de etapa ordenada. Em 'sem_op' usa o CASE pipeline-agnostic
    (combina os funis); caso contrário o CASE por pipeline."""
    return ETAPA_SEM_OP_CASE if modo == "sem_op" else ETAPA_ORDENADA_CASE


# Expressão do produto (mesma do donut), usada no cross-filter de produto.
PRODUTO_EXPR = "COALESCE(NULLIF(TRIM(o.nome_nova_oportunidade_produto), ''), '(Sem Produto)')"


def _filtro_clause(filtro, skip_tipo=None, already_joined=False, modo="normal", pipeline=None):
    """Cross-filter central — UM filtro ativo por vez.

    `filtro` = {"tipo": "etapa"|"status"|"produto", "valor": <v>} ou None.
    Retorna (join, where, params).
    - skip_tipo: o componente que é a FONTE daquele tipo não filtra a si mesmo.
    - already_joined: a query já faz LEFT JOIN tbl_oportunidades (alias o)?
    - modo/pipeline: para o filtro de etapa usar o CASE certo e o complemento
      'Outros' respeitar o mesmo escopo base (pipeline em 'normal', todos em 'sem_op').
    """
    if not filtro or not filtro.get("valor"):
        return "", "", {}
    tipo, valor = filtro.get("tipo"), filtro.get("valor")
    if tipo == skip_tipo:
        return "", "", {}
    params = {"f_valor": valor}
    if tipo == "status":
        return "", f" AND ({STATUS_CASE}) = %(f_valor)s", params
    if tipo == "etapa":
        return "", f" AND ({_etapa_expr(modo)}) = %(f_valor)s", params
    if tipo == "produto":
        join = "" if already_joined else \
            " LEFT JOIN tbl_oportunidades o ON o.bitrix_id::text = n.oportunidade_id"
        if valor == "Outros":
            # 'Outros' = produtos FORA do Top 9 (mesma regra do donut). Filtra pelo
            # complemento: produto NOT IN (Top 9 por contagem). O Top 9 respeita o
            # MESMO escopo base da página (pipeline em 'normal'; todos os funis,
            # só Sem Oportunidade, em 'sem_op'). Aliases n2/o2 p/ não colidir.
            base2, _ = _base_clause(pipeline, modo, "n2")
            prod_sub = "COALESCE(NULLIF(TRIM(o2.nome_nova_oportunidade_produto), ''), '(Sem Produto)')"
            where = f""" AND {PRODUTO_EXPR} NOT IN (
                SELECT prod FROM (
                    SELECT {prod_sub} AS prod,
                           ROW_NUMBER() OVER (ORDER BY COUNT(n2.bitrix_id) DESC) AS rn
                    FROM tbl_negocio n2
                    LEFT JOIN tbl_oportunidades o2 ON o2.bitrix_id::text = n2.oportunidade_id
                    WHERE {base2}
                    GROUP BY {prod_sub}
                ) tt WHERE rn <= 9
            )"""
            return join, where, {}
        return join, f" AND {PRODUTO_EXPR} = %(f_valor)s", params
    return "", "", {}


# ── A: Tabela de etapas — "Nome da Etapa Numerado" (fonte do filtro de etapa) ─
def get_etapa_table(pipeline, filtro=None, parceiro=None, data_de=None, data_ate=None, modo="normal"):
    fj, fw, fp = _filtro_clause(filtro, skip_tipo="etapa", modo=modo, pipeline=pipeline)
    pc, pp = _parceiro_clause(parceiro)
    dw, dp = _data_clause(data_de, data_ate)
    base, bp = _base_clause(pipeline, modo)
    sql = f"""
        SELECT
            {_etapa_expr(modo)}       AS etapa_ordenada,
            COUNT(n.bitrix_id)        AS total,
            COALESCE(SUM(n.valor), 0) AS valor_soma
        FROM tbl_negocio n {fj}
        WHERE {base} {pc} {fw} {dw}
        GROUP BY etapa_ordenada
        ORDER BY etapa_ordenada
    """
    return fetch_all(sql, {**bp, **pp, **fp, **dp})


# ── B: Resumo por status — "Etapas Oportunidades" (fonte do filtro de status) ─
def get_status_table(pipeline, filtro=None, parceiro=None, data_de=None, data_ate=None, modo="normal"):
    fj, fw, fp = _filtro_clause(filtro, skip_tipo="status", modo=modo, pipeline=pipeline)
    pc, pp = _parceiro_clause(parceiro)
    dw, dp = _data_clause(data_de, data_ate)
    base, bp = _base_clause(pipeline, modo)
    # Subselect para que o ORDER BY enxergue "status" como coluna real
    # (Postgres não resolve alias dentro de expressão no ORDER BY).
    sql = f"""
        SELECT status, total, valor_soma
        FROM (
            SELECT
                {STATUS_CASE}             AS status,
                COUNT(n.bitrix_id)        AS total,
                COALESCE(SUM(n.valor), 0) AS valor_soma
            FROM tbl_negocio n {fj}
            WHERE {base} {pc} {fw} {dw}
            GROUP BY status
        ) t
        ORDER BY CASE status
            WHEN 'Suspenso'         THEN 1
            WHEN 'Sem Oportunidade' THEN 2
            WHEN 'Em Diagnóstico'   THEN 3
            WHEN 'Com Oportunidade' THEN 4
        END
    """
    return fetch_all(sql, {**bp, **pp, **fp, **dp})


# ── C: KPIs (Total de Oportunidades / Valor Total) — aplica qualquer filtro ──
def get_kpis(pipeline, filtro=None, parceiro=None, data_de=None, data_ate=None, modo="normal"):
    fj, fw, fp = _filtro_clause(filtro, modo=modo, pipeline=pipeline)
    pc, pp = _parceiro_clause(parceiro)
    dw, dp = _data_clause(data_de, data_ate)
    base, bp = _base_clause(pipeline, modo)
    sql = f"""
        SELECT
            COUNT(n.bitrix_id)        AS total,
            COALESCE(SUM(n.valor), 0) AS valor_soma
        FROM tbl_negocio n {fj}
        WHERE {base} {pc} {fw} {dw}
    """
    return fetch_one(sql, {**bp, **pp, **fp, **dp}) or {"total": 0, "valor_soma": 0}


# ── C2: KPIs por período (Criados/Concluídos nos últimos 7 e 30 dias) ─────────
def get_kpi_periodico(funil, parceiro=None):
    """Returns created/concluded counts for the last 7 and 30 days.
    Only applies to 'diagnostico', 'operacional' and 'retificacao' — None for others.
    'Concluídos' exclui etapas de desfecho negativo (SEM_OP_ETAPAS)."""

    CAMPOS = {
        "diagnostico": {
            "criado":    "data_entrada_diagnostico",
            "concluido": "data_fim_diagnostico",
        },
        "operacional": {
            "criado":    "data_entrada_execucao",
            "concluido": "data_fim_execucao",
        },
        "retificacao": {
            "criado":    "data_de_entrada_retificacao",
            "concluido": "data_saida_retificacao",
        },
    }

    if funil not in CAMPOS:
        return None

    campos = CAMPOS[funil]
    pipeline = PIPELINES.get(funil)
    pc, pp = _parceiro_clause(parceiro)

    sql = f"""
        SELECT
            COUNT(*) FILTER (WHERE {campos['criado']}::date  >= CURRENT_DATE - 6)  AS criados_7,
            COUNT(*) FILTER (WHERE {campos['criado']}::date  >= CURRENT_DATE - 29) AS criados_30,
            COUNT(*) FILTER (WHERE {campos['concluido']}::date >= CURRENT_DATE - 6
                               AND {campos['concluido']} IS NOT NULL
                               AND n.etapa NOT IN ({SEM_OP_ETAPAS}))                AS concluidos_7,
            COUNT(*) FILTER (WHERE {campos['concluido']}::date >= CURRENT_DATE - 29
                               AND {campos['concluido']} IS NOT NULL
                               AND n.etapa NOT IN ({SEM_OP_ETAPAS}))                AS concluidos_30
        FROM tbl_negocio n
        WHERE n.pipeline = %(pipeline)s {pc}
    """
    return fetch_one(sql, {"pipeline": pipeline, **pp})


# ── D: Donut — Top 9 produtos + "Outros" (fonte do filtro de produto) ────────
def get_donut(pipeline, filtro=None, parceiro=None, data_de=None, data_ate=None, modo="normal"):
    fj, fw, fp = _filtro_clause(filtro, skip_tipo="produto", already_joined=True, modo=modo, pipeline=pipeline)
    pc, pp = _parceiro_clause(parceiro)
    dw, dp = _data_clause(data_de, data_ate)
    base, bp = _base_clause(pipeline, modo)
    sql = f"""
        WITH produto_counts AS (
            SELECT
                {PRODUTO_EXPR} AS produto,
                COUNT(n.bitrix_id) AS total
            FROM tbl_negocio n
            LEFT JOIN tbl_oportunidades o
                   ON o.bitrix_id::text = n.oportunidade_id
            WHERE {base} {pc} {fw} {dw}
            GROUP BY o.nome_nova_oportunidade_produto
        ),
        ranked AS (
            SELECT produto, total,
                   ROW_NUMBER() OVER (ORDER BY total DESC) AS rn
            FROM produto_counts
        )
        SELECT
            CASE WHEN rn <= 9 THEN produto ELSE 'Outros' END AS produto,
            SUM(total) AS total
        FROM ranked
        GROUP BY CASE WHEN rn <= 9 THEN produto ELSE 'Outros' END
        ORDER BY SUM(total) DESC
    """
    return fetch_all(sql, {**bp, **pp, **fp, **dp})


# ── E: Tabela detalhe (máx. 500) — aplica qualquer filtro ────────────────────
def get_detalhe(pipeline, filtro=None, parceiro=None, data_de=None, data_ate=None, modo="normal"):
    _, fw, fp = _filtro_clause(filtro, already_joined=True, modo=modo, pipeline=pipeline)  # já há LEFT JOIN o
    pc, pp = _parceiro_clause(parceiro)
    dw, dp = _data_clause(data_de, data_ate)
    base, bp = _base_clause(pipeline, modo)
    sql = f"""
        SELECT
            n.bitrix_id,
            COALESCE(NULLIF(TRIM(emp.titulo), ''), n.empresa, '—')           AS cliente,
            COALESCE(NULLIF(TRIM(o.nome_nova_oportunidade_produto), ''), '—') AS oportunidade,
            n.etapa,
            COALESCE(n.observacoes, '')                                       AS observacoes,
            COALESCE(n.valor, 0)                                              AS valor,
            'https://gnapp.bitrix24.com.br/crm/deal/details/' || n.bitrix_id || '/'
                AS link_deal
        FROM tbl_negocio n
        LEFT JOIN tbl_empresas      emp ON emp.bitrix_id::text = n.empresa_id
        LEFT JOIN tbl_oportunidades o   ON o.bitrix_id::text   = n.oportunidade_id
        WHERE {base} {pc} {fw} {dw}
        ORDER BY n.bitrix_id DESC
        LIMIT 500
    """
    return fetch_all(sql, {**bp, **pp, **fp, **dp})


# ── Agregador ────────────────────────────────────────────────────────────────
def get_funil(funil="diagnostico", parceiro=None, filtro=None, data_de=None, data_ate=None, modo="normal"):
    """Roda todas as visões de UM funil de uma vez. Recebe o funil como parâmetro
    (chave em PIPELINES) — a MESMA lógica serve para os três pipelines, só muda o
    valor do pipeline. STATUS_CASE é idêntico; ETAPA_ORDENADA_CASE tem o ramo de
    cada pipeline.

    `modo`: 'normal' (exclui 'Sem Oportunidade') ou 'sem_op' (só 'Sem Oportunidade').
    Escopo de status GLOBAL — aplica a todas as visões.

    Cross-filter central (UM filtro por vez): `filtro` = {"tipo","valor"} ou None.
    Cada visual aplica o filtro ativo, MENOS quando ele próprio é a fonte daquele tipo.
    Filtro de período (data_de/data_ate em criado_em) é GLOBAL — aplica a todas as
    visões e COMBINA com o cross-filter (ambos ativos ao mesmo tempo).
    """
    pipeline = PIPELINES.get(funil, PIPELINE_DIAGNOSTICO)
    return {
        "etapa_table":   get_etapa_table(pipeline, filtro, parceiro, data_de, data_ate, modo),
        "status_table":  get_status_table(pipeline, filtro, parceiro, data_de, data_ate, modo),
        "kpis":          get_kpis(pipeline, filtro, parceiro, data_de, data_ate, modo),
        "donut":         get_donut(pipeline, filtro, parceiro, data_de, data_ate, modo),
        "detalhe":       get_detalhe(pipeline, filtro, parceiro, data_de, data_ate, modo),
        "kpi_periodico": get_kpi_periodico(funil, parceiro),
    }


# Compat: mantém get_diagnostico chamando o genérico.
def get_diagnostico(parceiro=None, filtro=None):
    return get_funil("diagnostico", parceiro, filtro)


# ── Dashboard — resumo de todos os funis numa página ─────────────────────────
def get_dashboard(parceiro=None):
    """Loads summary data for all panels on the Dashboard tab.
    Returns a dict with keys: diagnostico, operacional, retificacao, sem_op, suspenso.
    Each value has: total, valor_soma, donut, and optionally kpi_periodico."""
    pc, pp = _parceiro_clause(parceiro)

    def _summary(where_clause, params):
        sql = f"""
            SELECT
                COUNT(n.bitrix_id)        AS total,
                COALESCE(SUM(n.valor), 0) AS valor_soma
            FROM tbl_negocio n
            WHERE {where_clause} {pc}
        """
        return fetch_one(sql, {**params, **pp}) or {"total": 0, "valor_soma": 0}

    def _donut(where_clause, params):
        sql = f"""
            WITH produto_counts AS (
                SELECT
                    {PRODUTO_EXPR} AS produto,
                    COUNT(n.bitrix_id) AS total
                FROM tbl_negocio n
                LEFT JOIN tbl_oportunidades o
                       ON o.bitrix_id::text = n.oportunidade_id
                WHERE {where_clause} {pc}
                GROUP BY o.nome_nova_oportunidade_produto
            ),
            ranked AS (
                SELECT produto, total,
                       ROW_NUMBER() OVER (ORDER BY total DESC) AS rn
                FROM produto_counts
            )
            SELECT
                CASE WHEN rn <= 9 THEN produto ELSE 'Outros' END AS produto,
                SUM(total) AS total
            FROM ranked
            GROUP BY CASE WHEN rn <= 9 THEN produto ELSE 'Outros' END
            ORDER BY SUM(total) DESC
        """
        return fetch_all(sql, {**params, **pp})

    # Diagnóstico — normal (exclude SEM_OP_ETAPAS)
    diag_w = f"n.pipeline = %(p_diag)s AND n.etapa NOT IN ({SEM_OP_ETAPAS})"
    diag_p = {"p_diag": PIPELINES["diagnostico"]}

    # Operacional — normal (exclude SEM_OP_ETAPAS)
    oper_w = f"n.pipeline = %(p_oper)s AND n.etapa NOT IN ({SEM_OP_ETAPAS})"
    oper_p = {"p_oper": PIPELINES["operacional"]}

    # Retificação — normal (exclude SEM_OP_ETAPAS)
    reti_w = f"n.pipeline = %(p_reti)s AND n.etapa NOT IN ({SEM_OP_ETAPAS})"
    reti_p = {"p_reti": PIPELINES["retificacao"]}

    # Sem Oportunidade — all pipelines, only negative stages
    sem_op_w = f"n.etapa IN ({SEM_OP_ETAPAS})"
    sem_op_p = {}

    # Suspenso — all pipelines, only etapa = 'Suspenso'
    susp_w = "n.etapa = 'Suspenso'"
    susp_p = {}

    return {
        "diagnostico": {
            **_summary(diag_w, diag_p),
            "donut": _donut(diag_w, diag_p),
            "kpi_periodico": get_kpi_periodico("diagnostico", parceiro=parceiro),
        },
        "operacional": {
            **_summary(oper_w, oper_p),
            "donut": _donut(oper_w, oper_p),
            "kpi_periodico": get_kpi_periodico("operacional", parceiro=parceiro),
        },
        "retificacao": {
            **_summary(reti_w, reti_p),
            "donut": _donut(reti_w, reti_p),
            "kpi_periodico": get_kpi_periodico("retificacao", parceiro=parceiro),
        },
        "sem_op": {
            **_summary(sem_op_w, sem_op_p),
            "donut": _donut(sem_op_w, sem_op_p),
            "kpi_periodico": None,
        },
        "suspenso": {
            **_summary(susp_w, susp_p),
            "donut": _donut(susp_w, susp_p),
            "kpi_periodico": None,
        },
    }
