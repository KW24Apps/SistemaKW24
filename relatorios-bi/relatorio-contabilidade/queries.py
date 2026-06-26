"""
Consultas do Relatório Contabilidade (ContaFarma).

Banco:  bx_sync_contabilidade
Tabela: tbl_onboard

Campos usados:
  - criado_em                  → data (filtro de período De/Até)
  - etapa                      → estágio (classifica a aba: Vendas Fechadas / Em Negociação)
  - valor                      → valor monetário do negócio
  - responsavel_pela_execucao  → vendedor (linha da tabela do Bloco 2)
  - parceiro_indicacao         → origem do negócio (própria x indicada — ver REGRAS)
  - tipo_de_contrato           → tipo de contrato (Bloco 3)

✅ Schema VERIFICADO em 2026-06-26 (via túnel SSH para o banco real):
     - Todas as 8 colunas usadas existem em tbl_onboard.
     - `valor` é NUMERIC → COALESCE(SUM(valor),0) direto está correto.
     - `criado_em` é TIMESTAMP → ::date no filtro de período está correto.
     - parceiro_indicacao: os dois nomes de venda própria batem EXATAMENTE
       ('CAPITON CONTABILIDADE S/S', 'FF CONTABILIDADE LTDA'); 9 NULLs, 0 vazios.
     - Mesmo verificado, as queries seguem DEFENSIVAS (UPPER/TRIM no parceiro,
       NULL/'' tratados, NULLIF/COALESCE) — robustas a novas linhas.
   ⚠️ Nota de dados: a etapa 'Não Fechados' (~142 linhas) NÃO está em nenhuma
      das duas abas (fora do escopo, por especificação). 'Solicitação' (aba Em
      Negociação) existe na spec mas hoje tem 0 linhas.

Notas técnicas:
  - psycopg2 com parâmetros nomeados %(nome)s; o mesmo parâmetro pode repetir.
  - Sem multi-tenant/portal aqui (relatório interno) — sem cláusula de parceiro.
"""

from db import fetch_all, fetch_one

# ── Conjuntos de etapa por aba ────────────────────────────────────────────────
# A estrutura das duas abas é idêntica; só muda o filtro de etapa.
ETAPAS = {
    "fechadas": [
        "Boas Vindas", "Constituição Empresa", "Delegação de Tarefas",
        "Conferência", "Concluídos",
    ],
    "negociacao": [
        "Solicitação", "Orçamento", "Gerar Proposta", "Gerar Contrato", "Click Sign",
    ],
}

# ── Classificação de origem (própria x indicada) ─────────────────────────────
# Regra de negócio:
#   'FF CONTABILIDADE LTDA'      → própria
#   'CAPITON CONTABILIDADE S/S'  → própria
#   NULL ou string vazia         → própria (não perder o registro)
#   qualquer outro valor não-vazio → indicada (o próprio valor é o indicador)
PARCEIROS_PROPRIOS = ("FF CONTABILIDADE LTDA", "CAPITON CONTABILIDADE S/S")

# Expressão SQL booleana: TRUE quando o registro é venda PRÓPRIA.
# Comparação case-insensitive e tolerante a espaços nas pontas.
EH_PROPRIA = """(
    COALESCE(TRIM(t.parceiro_indicacao), '') = ''
    OR UPPER(TRIM(t.parceiro_indicacao)) IN ('FF CONTABILIDADE LTDA', 'CAPITON CONTABILIDADE S/S')
)"""
# Indicada = não-própria (qualquer valor não-vazio fora da lista de próprios).
EH_INDICADA = f"NOT {EH_PROPRIA}"


# ── Helpers de cláusula ──────────────────────────────────────────────────────
def _etapa_clause(aba):
    """Cláusula de etapa para a aba. Gera placeholders nomeados %(et_0)s,..."""
    etapas = ETAPAS.get(aba, ETAPAS["fechadas"])
    params = {f"et_{i}": v for i, v in enumerate(etapas)}
    placeholders = ", ".join(f"%(et_{i})s" for i in range(len(etapas)))
    return f"t.etapa IN ({placeholders})", params


def _data_clause(data_de, data_ate):
    """Filtro por período em criado_em (aplicado SÓ quando AMBAS as datas vêm
    preenchidas). Usa ::date para incluir o dia inteiro nas duas pontas — mesmo
    comportamento do datepicker do relatorio-parceiros-tax."""
    if data_de and data_ate:
        return (" AND t.criado_em::date >= %(data_de)s AND t.criado_em::date <= %(data_ate)s",
                {"data_de": data_de, "data_ate": data_ate})
    return "", {}


def _base(aba, data_de, data_ate):
    """Monta (where, params) base = etapa da aba + período. Reutilizado por todas
    as visões da aba para garantir o MESMO escopo."""
    ec, ep = _etapa_clause(aba)
    dw, dp = _data_clause(data_de, data_ate)
    return f"{ec}{dw}", {**ep, **dp}


# ── Bloco 1: KPIs (Total / Próprias / Indicadas) ─────────────────────────────
def get_kpis(aba, data_de=None, data_ate=None):
    """Quantidade e soma de valor — total, próprias e indicadas. O ticket médio
    (total_valor / total_qtd) é calculado na camada de apresentação (app.py)."""
    where, params = _base(aba, data_de, data_ate)
    sql = f"""
        SELECT
            COUNT(*)                                            AS total_qtd,
            COALESCE(SUM(t.valor), 0)                           AS total_valor,
            COUNT(*) FILTER (WHERE {EH_PROPRIA})                AS propria_qtd,
            COALESCE(SUM(t.valor) FILTER (WHERE {EH_PROPRIA}), 0) AS propria_valor,
            COUNT(*) FILTER (WHERE {EH_INDICADA})               AS indicada_qtd,
            COALESCE(SUM(t.valor) FILTER (WHERE {EH_INDICADA}), 0) AS indicada_valor
        FROM tbl_onboard t
        WHERE {where}
    """
    return fetch_one(sql, params) or {
        "total_qtd": 0, "total_valor": 0,
        "propria_qtd": 0, "propria_valor": 0,
        "indicada_qtd": 0, "indicada_valor": 0,
    }


# ── Bloco 2: Tabela por vendedor (responsavel_pela_execucao) ─────────────────
def get_vendedores(aba, data_de=None, data_ate=None):
    """Uma linha por vendedor: qtd/valor próprios, qtd/valor indicados e total.
    Ordena pelo valor total desc. Linha expansível na UI (ver get_indicadas)."""
    where, params = _base(aba, data_de, data_ate)
    sql = f"""
        SELECT
            COALESCE(NULLIF(TRIM(t.responsavel_pela_execucao), ''), '(Sem responsável)') AS responsavel,
            COUNT(*) FILTER (WHERE {EH_PROPRIA})                  AS propria_qtd,
            COALESCE(SUM(t.valor) FILTER (WHERE {EH_PROPRIA}), 0)  AS propria_valor,
            COUNT(*) FILTER (WHERE {EH_INDICADA})                 AS indicada_qtd,
            COALESCE(SUM(t.valor) FILTER (WHERE {EH_INDICADA}), 0) AS indicada_valor,
            COUNT(*)                                              AS total_qtd,
            COALESCE(SUM(t.valor), 0)                             AS total_valor
        FROM tbl_onboard t
        WHERE {where}
        GROUP BY 1
        ORDER BY total_valor DESC, responsavel ASC
    """
    return fetch_all(sql, params)


# Campo do nome do cliente por aba (difere em tbl_onboard):
#   Vendas Fechadas → empresa        ·  Em Negociação → nome_da_empresa
NEGOCIO_COL = {
    "fechadas":   "empresa",
    "negociacao": "nome_da_empresa",
}


def get_indicadas(aba, data_de=None, data_ate=None):
    """Negócios INDICADOS (não-próprios) do escopo da aba — usados na expansão de
    cada linha de vendedor (Bloco 2). Retorna, por negócio: o vendedor, o nome do
    cliente (`negocio`), o indicador (parceiro_indicacao), o tipo de contrato, a
    data e o valor.

    O campo do nome do cliente muda por aba (ver NEGOCIO_COL): `empresa` em Vendas
    Fechadas, `nome_da_empresa` em Em Negociação. É exposto como `negocio` — chave
    que o app.py já exibe na primeira coluna da expansão."""
    if aba not in ETAPAS:
        aba = "fechadas"
    negocio_col = NEGOCIO_COL[aba]
    where, params = _base(aba, data_de, data_ate)
    sql = f"""
        SELECT
            COALESCE(NULLIF(TRIM(t.responsavel_pela_execucao), ''), '(Sem responsável)') AS responsavel,
            NULLIF(TRIM(t.{negocio_col}), '')                                AS negocio,
            TRIM(t.parceiro_indicacao)                                       AS indicador,
            COALESCE(NULLIF(TRIM(t.tipo_de_contrato), ''), '(Sem tipo)')     AS tipo_de_contrato,
            TO_CHAR(t.criado_em, 'DD/MM/YYYY')                               AS data,
            COALESCE(t.valor, 0)                                             AS valor
        FROM tbl_onboard t
        WHERE {where} AND {EH_INDICADA}
        ORDER BY t.valor DESC, responsavel ASC
    """
    return fetch_all(sql, params)


# ── Bloco 3: Tabela por tipo de contrato (tipo_de_contrato) ──────────────────
def get_contratos(aba, data_de=None, data_ate=None):
    """Tabela plana: tipo de contrato | quantidade | valor total."""
    where, params = _base(aba, data_de, data_ate)
    sql = f"""
        SELECT
            COALESCE(NULLIF(TRIM(t.tipo_de_contrato), ''), '(Sem tipo)') AS tipo_de_contrato,
            COUNT(*)                  AS qtd,
            COALESCE(SUM(t.valor), 0) AS valor_soma
        FROM tbl_onboard t
        WHERE {where}
        GROUP BY 1
        ORDER BY valor_soma DESC, tipo_de_contrato ASC
    """
    return fetch_all(sql, params)


# ── Agregador — roda todas as visões de uma aba de uma vez ───────────────────
def get_aba(aba="fechadas", data_de=None, data_ate=None):
    """Carrega Bloco 1 (KPIs), Bloco 2 (vendedores + indicadas para expansão) e
    Bloco 3 (contratos) para a aba pedida ('fechadas' | 'negociacao'). A MESMA
    lógica serve às duas abas — só muda o conjunto de etapas."""
    if aba not in ETAPAS:
        aba = "fechadas"
    return {
        "kpis":       get_kpis(aba, data_de, data_ate),
        "vendedores": get_vendedores(aba, data_de, data_ate),
        "indicadas":  get_indicadas(aba, data_de, data_ate),
        "contratos":  get_contratos(aba, data_de, data_ate),
    }
