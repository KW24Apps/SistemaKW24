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

# ── Parâmetros do funil ──────────────────────────────────────────────────────
PIPELINE_DIAGNOSTICO    = "RELATÓRIO PRELIMINAR (DIAGNOST)"
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
    ELSE '99 - ' || n.etapa
END
"""


# ── Helpers de cláusula ──────────────────────────────────────────────────────
def _parceiro_clause(parceiro):
    """Filtro opcional por parceiro (multi-tenant)."""
    if parceiro and PARCEIRO_COLUMN:
        return f" AND n.{PARCEIRO_COLUMN} = %(parceiro)s", {"parceiro": parceiro}
    return "", {}


def _status_clause(status_filter):
    """Cross-filter de status (clique na tabela B)."""
    if status_filter:
        return f" AND ({STATUS_CASE}) = %(status_filter)s", {"status_filter": status_filter}
    return "", {}


# Expressão do produto (mesma do donut), usada no cross-filter de produto.
PRODUTO_EXPR = "COALESCE(NULLIF(TRIM(o.nome_nova_oportunidade_produto), ''), '(Sem Produto)')"


def _produto_clause(produto, already_joined=False):
    """Cross-filter de produto (clique no donut). Retorna (join, where, params).
    'Outros' é um agregado (não é um produto único) → não filtra. Para tabelas que
    ainda não fazem join em tbl_oportunidades, injeta o LEFT JOIN (alias o)."""
    if not produto or produto == "Outros":
        return "", "", {}
    join = "" if already_joined else \
        " LEFT JOIN tbl_oportunidades o ON o.bitrix_id::text = n.oportunidade_id"
    where = f" AND {PRODUTO_EXPR} = %(produto)s"
    return join, where, {"produto": produto}


# ── A: Tabela de etapas — "Nome da Etapa Numerado" ───────────────────────────
def get_etapa_table(pipeline, status_filter=None, parceiro=None, produto=None):
    sc, sp = _status_clause(status_filter)
    pc, pp = _parceiro_clause(parceiro)
    pj, pw, pdp = _produto_clause(produto)
    sql = f"""
        SELECT
            {ETAPA_ORDENADA_CASE}     AS etapa_ordenada,
            COUNT(n.bitrix_id)        AS total,
            COALESCE(SUM(n.valor), 0) AS valor_soma
        FROM tbl_negocio n {pj}
        WHERE n.pipeline = %(pipeline)s {sc} {pc} {pw}
        GROUP BY etapa_ordenada
        ORDER BY etapa_ordenada
    """
    return fetch_all(sql, {"pipeline": pipeline, **sp, **pp, **pdp})


# ── B: Resumo por status — "Etapas Oportunidades" (fonte do cross-filter) ────
# NÃO aplica status_filter (é a fonte do filtro de status), mas aplica parceiro
# e o cross-filter de produto (vindo do donut).
def get_status_table(pipeline, parceiro=None, produto=None):
    pc, pp = _parceiro_clause(parceiro)
    pj, pw, pdp = _produto_clause(produto)
    # Subselect para que o ORDER BY enxergue "status" como coluna real
    # (Postgres não resolve alias dentro de expressão no ORDER BY).
    sql = f"""
        SELECT status, total, valor_soma
        FROM (
            SELECT
                {STATUS_CASE}             AS status,
                COUNT(n.bitrix_id)        AS total,
                COALESCE(SUM(n.valor), 0) AS valor_soma
            FROM tbl_negocio n {pj}
            WHERE n.pipeline = %(pipeline)s {pc} {pw}
            GROUP BY status
        ) t
        ORDER BY CASE status
            WHEN 'Suspenso'         THEN 1
            WHEN 'Sem Oportunidade' THEN 2
            WHEN 'Em Diagnóstico'   THEN 3
            WHEN 'Com Oportunidade' THEN 4
        END
    """
    return fetch_all(sql, {"pipeline": pipeline, **pp, **pdp})


# ── C: KPIs (Total de Oportunidades / Valor Total) ───────────────────────────
def get_kpis(pipeline, status_filter=None, parceiro=None, produto=None):
    sc, sp = _status_clause(status_filter)
    pc, pp = _parceiro_clause(parceiro)
    pj, pw, pdp = _produto_clause(produto)
    sql = f"""
        SELECT
            COUNT(n.bitrix_id)        AS total,
            COALESCE(SUM(n.valor), 0) AS valor_soma
        FROM tbl_negocio n {pj}
        WHERE n.pipeline = %(pipeline)s {sc} {pc} {pw}
    """
    return fetch_one(sql, {"pipeline": pipeline, **sp, **pp, **pdp}) or {"total": 0, "valor_soma": 0}


# ── D: Donut — Top 9 produtos + "Outros" ─────────────────────────────────────
def get_donut(pipeline, status_filter=None, parceiro=None):
    sc, sp = _status_clause(status_filter)
    pc, pp = _parceiro_clause(parceiro)
    sql = f"""
        WITH produto_counts AS (
            SELECT
                COALESCE(NULLIF(TRIM(o.nome_nova_oportunidade_produto), ''), '(Sem Produto)')
                    AS produto,
                COUNT(n.bitrix_id) AS total
            FROM tbl_negocio n
            LEFT JOIN tbl_oportunidades o
                   ON o.bitrix_id::text = n.oportunidade_id
            WHERE n.pipeline = %(pipeline)s {sc} {pc}
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
    return fetch_all(sql, {"pipeline": pipeline, **sp, **pp})


# ── E: Tabela detalhe (máx. 500) ─────────────────────────────────────────────
def get_detalhe(pipeline, status_filter=None, parceiro=None, produto=None):
    sc, sp = _status_clause(status_filter)
    pc, pp = _parceiro_clause(parceiro)
    _, pw, pdp = _produto_clause(produto, already_joined=True)  # já há LEFT JOIN o
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
        WHERE n.pipeline = %(pipeline)s {sc} {pc} {pw}
        ORDER BY n.bitrix_id DESC
        LIMIT 500
    """
    return fetch_all(sql, {"pipeline": pipeline, **sp, **pp, **pdp})


# ── Agregador ────────────────────────────────────────────────────────────────
def get_diagnostico(parceiro=None, status_filter=None, produto=None):
    """Roda todas as visões do Funil Diagnóstico de uma vez.

    Cross-filter (BI): cada visual aplica TODOS os filtros ativos, MENOS o que ele
    próprio é a fonte. O donut é a fonte do filtro de produto → não aplica `produto`
    (mostra todos). A tabela de status é a fonte do filtro de status → não aplica
    `status_filter`. Ambas aplicam o filtro da outra (compõem em AND), como no Power BI.
    """
    pipeline = PIPELINE_DIAGNOSTICO
    return {
        "etapa_table":  get_etapa_table(pipeline, status_filter, parceiro, produto),
        "status_table": get_status_table(pipeline, parceiro, produto),
        "kpis":         get_kpis(pipeline, status_filter, parceiro, produto),
        "donut":        get_donut(pipeline, status_filter, parceiro),
        "detalhe":      get_detalhe(pipeline, status_filter, parceiro, produto),
    }
