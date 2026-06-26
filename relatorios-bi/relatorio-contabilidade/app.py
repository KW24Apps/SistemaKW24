"""
ContaFarma — Relatório Contabilidade (Dash)

Duas abas de estrutura IDÊNTICA — só muda o conjunto de etapas:
  • Vendas Fechadas  → etapa IN (Boas Vindas, Constituição Empresa,
                        Delegação de Tarefas, Conferência, Concluídos)
  • Em Negociação    → etapa IN (Solicitação, Orçamento, Gerar Proposta,
                        Gerar Contrato, Click Sign)

Cada aba tem três blocos:
  Bloco 1 — 4 cards de KPI (Total · Próprias · Indicadas · Ticket médio)
  Bloco 2 — tabela por vendedor (responsavel_pela_execucao), linhas expansíveis
            que revelam os negócios INDICADOS daquele vendedor
  Bloco 3 — tabela por tipo de contrato (tipo_de_contrato), plana

Filtro de período (De/Até) no topo recarrega os três blocos — mesmo
comportamento do datepicker do relatorio-parceiros-tax.

Rodar local:   python run_local.py   (http://localhost:8051 — sem o prefixo de produção)
Produção:      gunicorn app:server -b 127.0.0.1:8051
               (servido pelo nginx sob /relatorios-bi/relatorio-contabilidade/)
"""

import os
import base64

from dash import Dash, dcc, html, Input, Output, State, callback, no_update, ALL, ctx

import queries


# ── Índice ASCII-safe para ids pattern-matching ──────────────────────────────
# O Dash quebra o matching de component-id quando o valor tem caracteres
# não-ASCII (acentos): o clique não dispara o callback. Por isso o "index" do
# <tr> é o valor codificado em base64 (ASCII), e o callback decodifica de volta.
def _enc(v):
    return base64.urlsafe_b64encode(str(v).encode("utf-8")).decode("ascii")


def _dec(v):
    return base64.urlsafe_b64decode(str(v).encode("ascii")).decode("utf-8")


# ── Helpers de formatação ────────────────────────────────────────────────────
def fmt_brl(v):
    try:
        v = float(v or 0)
    except (TypeError, ValueError):
        v = 0.0
    return "R$ " + f"{v:,.2f}".replace(",", "X").replace(".", ",").replace("X", ".")


def fmt_num(v):
    try:
        v = int(float(v or 0))
    except (TypeError, ValueError):
        v = 0
    return f"{v:,}".replace(",", ".")


def _f(v):
    """Decimal/None → float (Decimal não é serializável no dcc.Store)."""
    try:
        return float(v or 0)
    except (TypeError, ValueError):
        return 0.0


def _i(v):
    try:
        return int(float(v or 0))
    except (TypeError, ValueError):
        return 0


# ── Abas ─────────────────────────────────────────────────────────────────────
ABAS = [("fechadas", "Vendas Fechadas"), ("negociacao", "Em Negociação")]
ABA_DEFAULT = "fechadas"


# ── Componentes de layout ────────────────────────────────────────────────────
def card(title, children, icon="fa-table", extra_class=""):
    return html.Div(className=f"rt-card {extra_class}", children=[
        html.Div(className="rt-card-head", children=[
            html.I(className=f"fas {icon}"),
            html.Span(title),
        ]),
        html.Div(className="rt-card-body", children=children),
    ])


def kpi_card(label, icon, value_id, sub_id, accent_class):
    """Card de KPI com borda superior colorida (accent_class).
    value_id = valor principal (R$); sub_id = quantidade (texto secundário)."""
    return html.Div(className=f"rt-kpi {accent_class}", children=[
        html.Div(className="rt-kpi-icon", children=html.I(className=f"fas {icon}")),
        html.Div(className="rt-kpi-body", children=[
            html.Div(label, className="rt-kpi-label"),
            html.Div("—", id=value_id, className="rt-kpi-value"),
            html.Div("—", id=sub_id, className="rt-kpi-sub"),
        ]),
    ])


def kpi_row():
    return html.Div(className="rt-kpi-row", children=[
        kpi_card("Total",         "fa-layer-group",   "kpi-total-valor",    "kpi-total-qtd",    "kpi-accent-total"),
        kpi_card("Próprias",      "fa-house",         "kpi-propria-valor",  "kpi-propria-qtd",  "kpi-accent-propria"),
        kpi_card("Indicadas",     "fa-handshake",     "kpi-indicada-valor", "kpi-indicada-qtd", "kpi-accent-indicada"),
        kpi_card("Ticket Médio",  "fa-receipt",       "kpi-ticket",         "kpi-ticket-sub",   "kpi-accent-ticket"),
    ])


def data_filter_bar():
    """Barra de filtro de período (botão abre painel com De/Até + Limpar)."""
    return html.Div(className="rt-header-right", children=[
        html.Div(className="rt-datawrap", children=[
            html.Button([html.I(className="fas fa-calendar-days"), " Filtro Data"],
                        id="ct-data-btn", className="rt-refresh"),
            html.Div(id="ct-data-panel", className="rt-data-panel", children=[
                html.Div(className="rt-data-fields", children=[
                    html.Div(className="rt-data-field", children=[
                        html.Label("De (Início)", className="rt-data-flabel"),
                        dcc.DatePickerSingle(id="ct-data-de", display_format="DD/MM/YYYY",
                                             placeholder="dd/mm/aaaa", clearable=True),
                    ]),
                    html.Div(className="rt-data-field", children=[
                        html.Label("Até (Fim)", className="rt-data-flabel"),
                        dcc.DatePickerSingle(id="ct-data-ate", display_format="DD/MM/YYYY",
                                             placeholder="dd/mm/aaaa", clearable=True),
                    ]),
                ]),
                html.Div(id="ct-data-limpar-wrap", style={"display": "none"}, children=[
                    html.Button("Limpar", id="ct-data-limpar", className="rt-data-limpar"),
                ]),
            ]),
        ]),
        html.Button([html.I(className="fas fa-rotate"), " Atualizar"],
                    id="btn-refresh", className="rt-refresh"),
    ])


# ── Tabela por vendedor (Bloco 2) — HTML clicável, linhas expansíveis ────────
def build_vendedores_table(vendedores, indicadas_por_resp, expanded):
    """Constrói a tabela do Bloco 2. Cada linha de vendedor é clicável; quando
    expandida, insere logo abaixo uma linha-detalhe com os negócios INDICADOS
    daquele vendedor (negócio, indicador, valor).

    vendedores: lista de dicts já serializados (floats/ints).
    indicadas_por_resp: {responsavel: [ {indicador, tipo_de_contrato, data, valor} ]}.
    expanded: set/lista de chaves (base64 de responsavel) atualmente abertas.
    """
    if not vendedores:
        return html.P("Sem dados para o período.", className="rt-empty")

    expanded = set(expanded or [])
    head = html.Thead(html.Tr([
        html.Th("Vendedor", style={"textAlign": "left"}),
        html.Th("Qtd Próprias", style={"textAlign": "right"}),
        html.Th("Valor Próprias", style={"textAlign": "right"}),
        html.Th("Qtd Indicadas", style={"textAlign": "right"}),
        html.Th("Valor Indicadas", style={"textAlign": "right"}),
        html.Th("Total", style={"textAlign": "right"}),
    ]))

    body = []
    for r in vendedores:
        resp = r["responsavel"]
        key = _enc(resp)
        is_open = key in expanded
        n_ind = _i(r["indicada_qtd"])
        caret = "fa-chevron-down" if is_open else "fa-chevron-right"

        body.append(html.Tr(
            id={"type": "ct-vend-row", "index": key},
            n_clicks=0,
            className="rt-vend-row" + (" rt-vend-open" if is_open else ""),
            children=[
                html.Td([
                    html.I(className=f"fas {caret} rt-caret",
                           style={"opacity": 1 if n_ind else 0.25}),
                    html.Span(resp),
                ], style={"textAlign": "left"}),
                html.Td(fmt_num(r["propria_qtd"]),    style={"textAlign": "right"}),
                html.Td(fmt_brl(r["propria_valor"]),  style={"textAlign": "right"}),
                html.Td(fmt_num(r["indicada_qtd"]),   style={"textAlign": "right"}),
                html.Td(fmt_brl(r["indicada_valor"]), style={"textAlign": "right"}),
                html.Td(fmt_brl(r["total_valor"]),    style={"textAlign": "right", "fontWeight": 700}),
            ],
        ))

        if is_open:
            deals = indicadas_por_resp.get(resp, [])
            body.append(html.Tr(className="rt-vend-detail-row", children=[
                html.Td(colSpan=6, children=_detail_block(deals)),
            ]))

    return html.Table([head, html.Tbody(body)], className="rt-table rt-table-click")


def _detail_block(deals):
    """Mini-tabela dos negócios indicados de um vendedor (dentro da expansão)."""
    if not deals:
        return html.Div("Nenhum negócio indicado neste período.", className="rt-detail-empty")
    rows = [html.Tr([
        html.Td(d.get("negocio") or f'{d.get("tipo_de_contrato", "—")} · {d.get("data", "—")}',
                style={"textAlign": "left"}),
        html.Td(d.get("indicador") or "—", style={"textAlign": "left"}),
        html.Td(fmt_brl(d.get("valor")), style={"textAlign": "right"}),
    ]) for d in deals]
    return html.Table(className="rt-detail-table", children=[
        html.Thead(html.Tr([
            html.Th("Negócio", style={"textAlign": "left"}),
            html.Th("Indicador", style={"textAlign": "left"}),
            html.Th("Valor", style={"textAlign": "right"}),
        ])),
        html.Tbody(rows),
    ])


# ── Tabela por tipo de contrato (Bloco 3) — plana ────────────────────────────
def build_contratos_table(contratos):
    if not contratos:
        return html.P("Sem dados para o período.", className="rt-empty")
    head = html.Thead(html.Tr([
        html.Th("Tipo de Contrato", style={"textAlign": "left"}),
        html.Th("Quantidade", style={"textAlign": "right"}),
        html.Th("Valor Total", style={"textAlign": "right"}),
    ]))
    body = [html.Tr(className="rt-status-row", children=[
        html.Td(r["tipo_de_contrato"], style={"textAlign": "left"}),
        html.Td(fmt_num(r["qtd"]), style={"textAlign": "right"}),
        html.Td(fmt_brl(r["valor_soma"]), style={"textAlign": "right"}),
    ]) for r in contratos]
    return html.Table([head, html.Tbody(body)], className="rt-table rt-table-click")


# ── App ──────────────────────────────────────────────────────────────────────
app = Dash(
    __name__,
    title="ContaFarma — Relatório Contabilidade",
    external_stylesheets=[
        "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css",
    ],
    suppress_callback_exceptions=True,
    requests_pathname_prefix="/relatorios-bi/relatorio-contabilidade/",
)
server = app.server  # alvo do gunicorn

app.layout = html.Div(className="rt-app", children=[
    dcc.Location(id="url"),
    # Aba ativa (conjunto de etapas). As duas abas reaproveitam TODOS os componentes.
    dcc.Store(id="ct-aba", data=ABA_DEFAULT),
    # Dataset já serializado da aba atual (vendedores + indicadas agrupadas) — o
    # callback de expansão reconstrói a tabela sem reconsultar o banco.
    dcc.Store(id="ct-data", data={"vendedores": [], "indicadas": {}}),
    # Lista de vendedores expandidos (chaves base64). Resetada ao trocar aba/data.
    dcc.Store(id="ct-expanded", data=[]),

    # Cabeçalho
    html.Div(className="rt-header", children=[
        html.Div(className="rt-brand", children="ContaFarma"),
        html.Div(className="rt-tabs", children=[
            html.Button(rotulo, className="rt-tab" + (" rt-tab-active" if chave == ABA_DEFAULT else ""),
                        id={"type": "ct-tab", "index": chave})
            for chave, rotulo in ABAS
        ]),
        data_filter_bar(),
    ]),

    html.Div(id="error-banner"),

    # ── Bloco 1: KPIs ────────────────────────────────────────────────────────
    kpi_row(),

    # ── Bloco 2: por vendedor (expansível) ───────────────────────────────────
    card("Negócios por Vendedor", icon="fa-user-tie", extra_class="rt-col-full", children=[
        html.Div(id="ct-vendedores"),
    ]),

    # ── Bloco 3: por tipo de contrato ────────────────────────────────────────
    card("Negócios por Tipo de Contrato", icon="fa-file-signature", extra_class="rt-col-full", children=[
        html.Div(id="ct-contratos"),
    ]),
])


# ── Troca de aba: muda o conjunto de etapas (reseta expansão via load_data) ──
@callback(
    Output("ct-aba", "data"),
    Input({"type": "ct-tab", "index": ALL}, "n_clicks"),
    State("ct-aba", "data"),
    prevent_initial_call=True,
)
def switch_tab(_n, atual):
    if not ctx.triggered or not ctx.triggered[0]["value"]:
        return no_update
    nova = ctx.triggered_id["index"]
    return no_update if nova == atual else nova


# Destaque visual da aba ativa
@callback(
    Output({"type": "ct-tab", "index": ALL}, "className"),
    Input("ct-aba", "data"),
)
def highlight_tab(aba):
    return ["rt-tab" + (" rt-tab-active" if chave == aba else "") for chave, _ in ABAS]


# ── Filtro de data: abrir/fechar painel, mostrar "Limpar", limpar ────────────
@callback(
    Output("ct-data-panel", "className"),
    Input("ct-data-btn", "n_clicks"),
    State("ct-data-panel", "className"),
    prevent_initial_call=True,
)
def toggle_data_panel(_n, cls):
    return "rt-data-panel" if (cls and "open" in cls) else "rt-data-panel open"


@callback(
    Output("ct-data-limpar-wrap", "style"),
    Input("ct-data-de", "date"),
    Input("ct-data-ate", "date"),
)
def toggle_limpar(de, ate):
    return {"display": "block"} if (de or ate) else {"display": "none"}


@callback(
    Output("ct-data-de", "date"),
    Output("ct-data-ate", "date"),
    Input("ct-data-limpar", "n_clicks"),
    prevent_initial_call=True,
)
def limpar_datas(_n):
    return None, None


# ── Callback principal: carrega os três blocos da aba a partir do período ────
@callback(
    Output("kpi-total-valor", "children"),
    Output("kpi-total-qtd", "children"),
    Output("kpi-propria-valor", "children"),
    Output("kpi-propria-qtd", "children"),
    Output("kpi-indicada-valor", "children"),
    Output("kpi-indicada-qtd", "children"),
    Output("kpi-ticket", "children"),
    Output("kpi-ticket-sub", "children"),
    Output("ct-contratos", "children"),
    Output("ct-data", "data"),
    Output("ct-expanded", "data"),
    Output("error-banner", "children"),
    Input("ct-aba", "data"),
    Input("ct-data-de", "date"),
    Input("ct-data-ate", "date"),
    Input("btn-refresh", "n_clicks"),
)
def load_data(aba, data_de, data_ate, _n):
    # Período: ambos → intervalo De..Até; só um → aquele dia exato; nenhum → sem filtro.
    if data_de and data_ate:
        dd, da = data_de, data_ate
    elif data_de:
        dd = da = data_de
    elif data_ate:
        dd = da = data_ate
    else:
        dd = da = None

    try:
        d = queries.get_aba(aba or ABA_DEFAULT, data_de=dd, data_ate=da)
    except Exception as e:
        banner = html.Div(className="rt-error", children=[
            html.I(className="fas fa-triangle-exclamation"),
            f" Erro ao carregar os dados: {e}",
        ])
        empty = build_contratos_table([])
        return ("—", "—", "—", "—", "—", "—", "—", "—", empty,
                {"vendedores": [], "indicadas": {}}, [], banner)

    k = d["kpis"]
    total_qtd = _i(k.get("total_qtd"))
    total_valor = _f(k.get("total_valor"))
    ticket = (total_valor / total_qtd) if total_qtd else 0.0

    # Serializa vendedores (Decimal → float/int) para o dcc.Store.
    vendedores = [{
        "responsavel":    r["responsavel"],
        "propria_qtd":    _i(r["propria_qtd"]),
        "propria_valor":  _f(r["propria_valor"]),
        "indicada_qtd":   _i(r["indicada_qtd"]),
        "indicada_valor": _f(r["indicada_valor"]),
        "total_qtd":      _i(r["total_qtd"]),
        "total_valor":    _f(r["total_valor"]),
    } for r in d["vendedores"]]

    # Agrupa os negócios indicados por vendedor (para a expansão do Bloco 2).
    indicadas_por_resp = {}
    for it in d["indicadas"]:
        indicadas_por_resp.setdefault(it["responsavel"], []).append({
            "negocio":          it.get("negocio"),
            "indicador":        it.get("indicador"),
            "tipo_de_contrato": it.get("tipo_de_contrato"),
            "data":             it.get("data"),
            "valor":            _f(it.get("valor")),
        })

    store = {"vendedores": vendedores, "indicadas": indicadas_por_resp}
    contratos_tbl = build_contratos_table(d["contratos"])

    return (
        fmt_brl(total_valor), f"{fmt_num(total_qtd)} negócios",
        fmt_brl(k.get("propria_valor")), f"{fmt_num(k.get('propria_qtd'))} negócios",
        fmt_brl(k.get("indicada_valor")), f"{fmt_num(k.get('indicada_qtd'))} negócios",
        fmt_brl(ticket), "valor médio por negócio",
        contratos_tbl,
        store,
        [],          # reseta expansão a cada recarga (troca de aba ou período)
        None,
    )


# ── Render da tabela de vendedores (reage a dados + estado de expansão) ──────
@callback(
    Output("ct-vendedores", "children"),
    Input("ct-data", "data"),
    Input("ct-expanded", "data"),
)
def render_vendedores(data, expanded):
    data = data or {}
    return build_vendedores_table(
        data.get("vendedores", []),
        data.get("indicadas", {}),
        expanded or [],
    )


# ── Clique na linha do vendedor: expande/colapsa ─────────────────────────────
@callback(
    Output("ct-expanded", "data", allow_duplicate=True),
    Input({"type": "ct-vend-row", "index": ALL}, "n_clicks"),
    State("ct-expanded", "data"),
    prevent_initial_call=True,
)
def toggle_expand(_n, expanded):
    if not ctx.triggered or not ctx.triggered[0]["value"]:
        return no_update
    key = ctx.triggered_id["index"]
    expanded = list(expanded or [])
    if key in expanded:
        expanded.remove(key)
    else:
        expanded.append(key)
    return expanded


if __name__ == "__main__":
    app.run(
        host=os.getenv("APP_HOST", "127.0.0.1"),
        port=int(os.getenv("APP_PORT", "8051")),
        debug=os.getenv("APP_DEBUG", "true").lower() == "true",
    )
