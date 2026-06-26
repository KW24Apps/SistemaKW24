"""
ContaFarma — Relatório Contabilidade (Dash)

Duas abas de estrutura IDÊNTICA — só muda o conjunto de etapas:
  • Vendas Fechadas  → etapa IN (Boas Vindas, Constituição Empresa,
                        Delegação de Tarefas, Conferência, Concluídos)
  • Em Negociação    → etapa IN (Solicitação, Orçamento, Gerar Proposta,
                        Gerar Contrato, Click Sign)

Layout (por aba):
  Bloco 1 — 4 cards de KPI, largura cheia (Total · Internas · Indicadas · Ticket)
  Donut   — sunburst de dois anéis (vendedor → Interno/Indicado) + legenda
  Bloco 2 — duas tabelas lado a lado:
              esquerda  (~65%) Negócios por Vendedor (expansível)
              direita   (~35%) Negócios por Tipo de Contrato
  Bloco 3 — Detalhamento (tabela full-width, 1 linha por negócio, ID clicável)

Cross-filter (dcc.Store id="cf-store" = {vendedor, tipo_venda}):
  clique no donut (anel interno → vendedor; anéis externos → vendedor+tipo),
  na legenda ou na linha de vendedor filtra as 3 tabelas; reclicar limpa (toggle).

Terminologia: "Interno/Internas" = venda própria; "Indicado/Indicadas" = indicação.
(Internamente os aliases SQL/keys seguem `propria_*` — interno == propria.)

Rodar local:   python run_local.py   (http://localhost:8051 — sem o prefixo de produção)
Produção:      gunicorn app:server -b 127.0.0.1:8051
               (servido pelo nginx sob /relatorios-bi/relatorio-contabilidade/)
"""

import os
import base64

import plotly.graph_objects as go
from dash import Dash, dcc, html, dash_table, Input, Output, State, callback, no_update, ALL, ctx

import queries


# ── Índice ASCII-safe para ids pattern-matching ──────────────────────────────
# O Dash quebra o matching de component-id quando o valor tem caracteres
# não-ASCII (acentos): o clique não dispara o callback. Por isso o "index" do
# elemento é o valor codificado em base64 (ASCII), e o callback decodifica.
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


# ── Abas + terminologia ───────────────────────────────────────────────────────
ABAS = [("fechadas", "Vendas Fechadas"), ("negociacao", "Em Negociação")]
ABA_DEFAULT = "fechadas"
TIPO_VENDA_LABEL = {"interno": "Interno", "indicado": "Indicado"}

# ── Cores do donut ─────────────────────────────────────────────────────────────
COR_INTERNO = "#00BBBC"   # anel externo — porção Interna (teal ContaFarma)
COR_INDICADO = "#f6ad55"  # anel externo — porção Indicada (âmbar)
# Tons distintos de teal/verde-azulado para o anel interno (um por vendedor).
TEAL_SHADES = [
    "#00BBBC", "#0E9AA7", "#13868C", "#2CC9CA", "#0A6E73",
    "#3FD0D1", "#1FB2B3", "#56D6D7", "#089BA0", "#6FE0E0",
    "#0C5E63", "#85E8E8", "#17A2A8", "#9CEFEF",
]


def _hex_to_rgba(hex_color, alpha):
    h = hex_color.lstrip("#")
    r, g, b = int(h[0:2], 16), int(h[2:4], 16), int(h[4:6], 16)
    return f"rgba({r},{g},{b},{alpha})"


def empty_fig(msg="Sem dados"):
    fig = go.Figure()
    fig.add_annotation(text=msg, showarrow=False, font=dict(size=14, color="#a0aec0"))
    fig.update_layout(margin=dict(l=0, r=0, t=0, b=0),
                      xaxis=dict(visible=False), yaxis=dict(visible=False),
                      paper_bgcolor="rgba(0,0,0,0)", plot_bgcolor="rgba(0,0,0,0)")
    return fig


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
        kpi_card("Total",        "fa-layer-group", "kpi-total-valor",    "kpi-total-qtd",    "kpi-accent-total"),
        kpi_card("Internas",     "fa-house",       "kpi-propria-valor",  "kpi-propria-qtd",  "kpi-accent-propria"),
        kpi_card("Indicadas",    "fa-handshake",   "kpi-indicada-valor", "kpi-indicada-qtd", "kpi-accent-indicada"),
        kpi_card("Ticket Médio", "fa-receipt",     "kpi-ticket",         "kpi-ticket-sub",   "kpi-accent-ticket"),
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


# ── Donut sunburst (dois anéis go.Pie concêntricos e alinhados) ──────────────
def _vend_donut_rows(vendedores):
    """Vendedores com pelo menos 1 negócio, ordenados por nº de negócios desc.
    A MESMA ordem é usada no anel interno, no anel externo e na legenda — o item
    N da legenda corresponde à fatia interna N e às externas 2N/2N+1 (mapeamento
    usado pelo JS de hover e pelos índices de clique)."""
    vs = [v for v in vendedores if _i(v["total_qtd"]) > 0]
    return sorted(vs, key=lambda r: _i(r["total_qtd"]), reverse=True)


def build_donut(vendedores, cf):
    """Figura com dois traces Pie:
      trace 0 = anel EXTERNO  (2N fatias: Interno/Indicado por vendedor)
      trace 1 = anel INTERNO  (N fatias: um por vendedor, domínio menor → no centro)
    Ordenação idêntica + sort=False → os anéis ficam radialmente alinhados.
    `cf` (cross-filter) destaca o vendedor selecionado (pull) e esmaece os demais."""
    vs = _vend_donut_rows(vendedores)
    if not vs:
        return empty_fig("Sem dados para o período")

    names   = [v["responsavel"] for v in vs]
    totals  = [_i(v["total_qtd"]) for v in vs]
    intern  = [_i(v["propria_qtd"]) for v in vs]
    indic   = [_i(v["indicada_qtd"]) for v in vs]
    grand   = sum(totals) or 1

    sel      = (cf or {}).get("vendedor")
    sel_tipo = (cf or {}).get("tipo_venda")
    inner_shades = [TEAL_SHADES[i % len(TEAL_SHADES)] for i in range(len(vs))]

    def keep(name, tipo=None):
        """True = realce/normal; False = esmaecer (há filtro e este não é o alvo)."""
        if not sel:
            return True
        if name != sel:
            return False
        return (not sel_tipo) or (tipo is None) or (sel_tipo == tipo)

    def col(c, on):
        return c if on else _hex_to_rgba(c, 0.22)

    # Anel interno
    inner_colors = [col(inner_shades[i], keep(names[i])) for i in range(len(vs))]
    inner_pull   = [0.10 if (sel and names[i] == sel) else 0 for i in range(len(vs))]
    inner_custom = [[n] for n in names]

    # Anel externo (2N) — [int0, ind0, int1, ind1, ...]
    o_labels, o_vals, o_colors, o_pull, o_custom, o_text = [], [], [], [], [], []
    for i in range(len(vs)):
        for tipo, val, base in (("interno", intern[i], COR_INTERNO),
                                ("indicado", indic[i], COR_INDICADO)):
            on = keep(names[i], tipo)
            o_labels.append(TIPO_VENDA_LABEL[tipo])
            o_vals.append(val)
            o_colors.append(col(base, on))
            o_pull.append(0.10 if (sel and names[i] == sel and ((not sel_tipo) or sel_tipo == tipo)) else 0)
            o_custom.append([names[i], tipo])
            pct = val / grand * 100
            o_text.append(f"{pct:.0f}%" if pct >= 4 else "")   # esconde rótulo de arco pequeno

    fig = go.Figure()
    # trace 0 — externo
    fig.add_trace(go.Pie(
        labels=o_labels, values=o_vals, customdata=o_custom,
        marker=dict(colors=o_colors, line=dict(color="#ffffff", width=1)),
        hole=0.62, pull=o_pull, sort=False, direction="clockwise", rotation=0,
        text=o_text, textinfo="text", textposition="inside",
        insidetextfont=dict(color="#06343a", size=10), texttemplate="%{text}",
        hovertemplate="%{customdata[0]} · %{label}<br>%{value} negócios (%{percent})<extra></extra>",
        domain=dict(x=[0, 1], y=[0, 1]), showlegend=False, name="outer",
    ))
    # trace 1 — interno (domínio menor, fica no centro)
    fig.add_trace(go.Pie(
        labels=names, values=totals, customdata=inner_custom,
        marker=dict(colors=inner_colors, line=dict(color="#ffffff", width=1)),
        hole=0.35, pull=inner_pull, sort=False, direction="clockwise", rotation=0,
        textinfo="none",
        hovertemplate="%{label}<br>%{value} negócios (%{percent})<extra></extra>",
        domain=dict(x=[0.19, 0.81], y=[0.19, 0.81]), showlegend=False, name="inner",
    ))
    fig.update_layout(margin=dict(t=8, b=8, l=8, r=8),
                      paper_bgcolor="rgba(0,0,0,0)", showlegend=False)
    return fig


def build_donut_legend(vendedores, cf):
    """Legenda HTML: por vendedor → swatch (tom do anel interno), nome, % do total
    e uma barra dividida (teal Interno / âmbar Indicado) proporcional ao split.
    Cada item é clicável (cross-filter por vendedor) e reage ao hover via JS."""
    vs = _vend_donut_rows(vendedores)
    if not vs:
        return [html.Div("Sem dados para o período.", className="rt-empty")]
    grand = sum(_i(v["total_qtd"]) for v in vs) or 1
    sel = (cf or {}).get("vendedor")
    items = []
    for i, v in enumerate(vs):
        name = v["responsavel"]
        tot = _i(v["total_qtd"])
        interno = _i(v["propria_qtd"])
        indicado = _i(v["indicada_qtd"])
        ipct = (interno / tot * 100) if tot else 0
        dpct = (indicado / tot * 100) if tot else 0
        dim = bool(sel) and name != sel
        active = name == sel
        items.append(html.Div(
            id={"type": "ct-leg", "index": _enc(name)}, n_clicks=0,
            className="ct-leg-item" + (" ct-dim" if dim else "") + (" ct-leg-active" if active else ""),
            children=[
                html.Span(className="ct-leg-dot", style={"backgroundColor": inner_shade(i)}),
                html.Div(className="ct-leg-main", children=[
                    html.Div(className="ct-leg-top", children=[
                        html.Span(name, className="ct-leg-name", title=name),
                        html.Span(f"{tot / grand * 100:.0f}%", className="ct-leg-pct"),
                    ]),
                    html.Div(className="ct-leg-bar", children=[
                        html.Span(className="ct-leg-bar-seg",
                                  style={"width": f"{ipct:.1f}%", "backgroundColor": COR_INTERNO}),
                        html.Span(className="ct-leg-bar-seg",
                                  style={"width": f"{dpct:.1f}%", "backgroundColor": COR_INDICADO}),
                    ]),
                ]),
            ],
        ))
    return items


def inner_shade(i):
    return TEAL_SHADES[i % len(TEAL_SHADES)]


# ── Tabela por vendedor (Bloco 2 esquerda) — HTML clicável, expansível ───────
def build_vendedores_table(vendedores, indicadas_por_resp, cf):
    """Tabela do Bloco 2. Filtrada ao vendedor do cross-filter (quando há) e a
    linha do vendedor ativo aparece EXPANDIDA, revelando seus negócios indicados.
    Clicar numa linha aplica/limpa o cross-filter por vendedor (toggle)."""
    if not vendedores:
        return html.P("Sem dados para o período.", className="rt-empty")

    sel = (cf or {}).get("vendedor")
    rows_src = [v for v in vendedores if (not sel or v["responsavel"] == sel)]
    if not rows_src:
        return html.P("Sem dados para o filtro atual.", className="rt-empty")

    head = html.Thead(html.Tr([
        html.Th("Vendedor", style={"textAlign": "left"}),
        html.Th("Qtd Internas", style={"textAlign": "right"}),
        html.Th("Valor Internas", style={"textAlign": "right"}),
        html.Th("Qtd Indicadas", style={"textAlign": "right"}),
        html.Th("Valor Indicadas", style={"textAlign": "right"}),
        html.Th("Total", style={"textAlign": "right"}),
    ]))

    body = []
    for r in rows_src:
        resp = r["responsavel"]
        is_open = (resp == sel)
        n_ind = _i(r["indicada_qtd"])
        caret = "fa-chevron-down" if is_open else "fa-chevron-right"
        body.append(html.Tr(
            id={"type": "ct-vend-row", "index": _enc(resp)},
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
            body.append(html.Tr(className="rt-vend-detail-row", children=[
                html.Td(colSpan=6, children=_detail_block(indicadas_por_resp.get(resp, []))),
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


# ── Tabela por tipo de contrato (Bloco 2 direita) — derivada do `detalhe` ────
def build_contratos_table(detalhe, cf):
    """Reagrupa `detalhe` (já filtrado pelo cross-filter) por tipo_de_contrato."""
    filt = _filter_detalhe(detalhe, cf)
    if not filt:
        return html.P("Sem dados para o filtro atual.", className="rt-empty")
    agg = {}
    for d in filt:
        tipo = d.get("tipo_de_contrato") or "(Sem tipo)"
        a = agg.setdefault(tipo, {"qtd": 0, "valor": 0.0})
        a["qtd"] += 1
        a["valor"] += _f(d.get("valor"))
    linhas = sorted(agg.items(), key=lambda kv: (-kv[1]["valor"], kv[0]))
    head = html.Thead(html.Tr([
        html.Th("Tipo de Contrato", style={"textAlign": "left"}),
        html.Th("Qtd", style={"textAlign": "right"}),
        html.Th("Valor Total", style={"textAlign": "right"}),
    ]))
    body = [html.Tr(className="rt-status-row", children=[
        html.Td(tipo, style={"textAlign": "left"}),
        html.Td(fmt_num(v["qtd"]), style={"textAlign": "right"}),
        html.Td(fmt_brl(v["valor"]), style={"textAlign": "right"}),
    ]) for tipo, v in linhas]
    return html.Table([head, html.Tbody(body)], className="rt-table rt-table-click")


# ── Detalhamento (Bloco 3) — DataTable com ID em markdown clicável ───────────
DEAL_URL = "https://gnapp.bitrix24.com.br/crm/deal/details/{id}/"
DETALHE_COLS = [
    {"name": "ID", "id": "id", "presentation": "markdown"},
    {"name": "Cliente", "id": "cliente"},
    {"name": "Vendedor", "id": "vendedor"},
    {"name": "Tipo de Venda", "id": "tipo_venda"},
    {"name": "Etapa", "id": "etapa"},
    {"name": "Tipo de Contrato", "id": "tipo_de_contrato"},
    {"name": "Valor", "id": "valor"},
]
DETALHE_STYLE_CELL_COND = [
    {"if": {"column_id": "valor"}, "textAlign": "right"},
    {"if": {"column_id": "id"}, "textAlign": "left", "minWidth": "70px"},
    {"if": {"column_id": "cliente"}, "minWidth": "180px"},
    {"if": {"column_id": "vendedor"}, "minWidth": "150px"},
]


def _filter_detalhe(detalhe, cf):
    cf = cf or {}
    v = cf.get("vendedor")
    t = cf.get("tipo_venda")
    out = []
    for d in detalhe or []:
        if v and d.get("vendedor") != v:
            continue
        if t and d.get("tipo_venda") != t:
            continue
        out.append(d)
    return out


def build_detalhamento_data(detalhe, cf):
    rows = []
    for d in _filter_detalhe(detalhe, cf):
        bid = d.get("bitrix_id")
        rows.append({
            "id": f"[{bid}]({DEAL_URL.format(id=bid)})" if bid is not None else "—",
            "cliente": d.get("cliente") or "—",
            "vendedor": d.get("vendedor") or "—",
            "tipo_venda": TIPO_VENDA_LABEL.get(d.get("tipo_venda"), "—"),
            "etapa": d.get("etapa") or "—",
            "tipo_de_contrato": d.get("tipo_de_contrato") or "—",
            "valor": fmt_brl(d.get("valor")),
        })
    return rows


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
    # Aba ativa (conjunto de etapas).
    dcc.Store(id="ct-aba", data=ABA_DEFAULT),
    # Dataset serializado da aba/período (vendedores + indicadas agrupadas + detalhe).
    dcc.Store(id="ct-data", data={"vendedores": [], "indicadas": {}, "detalhe": []}),
    # Cross-filter central: {vendedor, tipo_venda}. Resetado ao trocar aba/período.
    dcc.Store(id="cf-store", data={"vendedor": None, "tipo_venda": None}),

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

    # ── Bloco 1: KPIs (largura cheia, 4 colunas iguais) ──────────────────────
    kpi_row(),

    # ── Donut sunburst de dois anéis + legenda (linha própria) ───────────────
    html.Div(className="rt-card rt-col-full ct-donut-card", children=[
        html.Div(className="rt-card-head", children=[
            html.I(className="fas fa-chart-pie"),
            html.Span("Distribuição por Vendedor (Interno × Indicado)"),
            # Chip de filtro ativo (à direita do cabeçalho)
            html.Div(id="cf-chip-wrap", className="ct-chip-wrap", style={"display": "none"}, children=[
                html.I(className="fas fa-filter"),
                html.Span(id="cf-chip-text", className="ct-chip-text"),
                html.Button("×", id="cf-chip-clear", className="ct-chip-x", title="Limpar filtro"),
            ]),
        ]),
        html.Div(className="rt-card-body", children=[
            html.Div(className="ct-donut", children=[
                html.Div(className="ct-donut-circle", children=dcc.Graph(
                    id="ct-donut", figure=empty_fig("Carregando…"),
                    config={"displayModeBar": False},
                    style={"height": "320px", "width": "320px"})),
                html.Div(id="ct-donut-legend", className="ct-donut-legend"),
            ]),
        ]),
    ]),

    # ── Bloco 2: duas tabelas lado a lado ────────────────────────────────────
    html.Div(className="ct-two-col", children=[
        card("Negócios por Vendedor", icon="fa-user-tie", extra_class="ct-col-vend", children=[
            html.Div(id="ct-vendedores"),
        ]),
        card("Negócios por Tipo de Contrato", icon="fa-file-signature", extra_class="ct-col-contrato", children=[
            html.Div(id="ct-contratos"),
        ]),
    ]),

    # ── Bloco 3: Detalhamento (full width) ───────────────────────────────────
    card("Detalhamento", icon="fa-table-list", extra_class="rt-col-full", children=[
        dash_table.DataTable(
            id="tbl-detalhamento",
            columns=DETALHE_COLS,
            data=[],
            markdown_options={"link_target": "_blank"},
            cell_selectable=False,
            page_size=25,
            sort_action="native",
            style_as_list_view=True,
            style_table={"overflowX": "auto"},
            style_cell={"fontFamily": "Inter, sans-serif", "fontSize": "12.5px",
                        "padding": "8px 10px", "border": "none", "textAlign": "left"},
            style_header={"backgroundColor": "#f8fafc", "fontWeight": "600",
                          "color": "#475569", "textTransform": "uppercase",
                          "fontSize": "10.5px", "letterSpacing": "0.04em"},
            style_data={"borderBottom": "1px solid #f1f5f9"},
            style_cell_conditional=DETALHE_STYLE_CELL_COND,
        ),
    ]),
])


# ── Troca de aba: muda o conjunto de etapas (reseta cross-filter via load_data) ─
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


# ── Carga de dados: KPIs (período) + dataset da aba; reseta o cross-filter ────
@callback(
    Output("kpi-total-valor", "children"),
    Output("kpi-total-qtd", "children"),
    Output("kpi-propria-valor", "children"),
    Output("kpi-propria-qtd", "children"),
    Output("kpi-indicada-valor", "children"),
    Output("kpi-indicada-qtd", "children"),
    Output("kpi-ticket", "children"),
    Output("kpi-ticket-sub", "children"),
    Output("ct-data", "data"),
    Output("cf-store", "data"),
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

    cf_reset = {"vendedor": None, "tipo_venda": None}
    try:
        d = queries.get_aba(aba or ABA_DEFAULT, data_de=dd, data_ate=da)
    except Exception as e:
        banner = html.Div(className="rt-error", children=[
            html.I(className="fas fa-triangle-exclamation"),
            f" Erro ao carregar os dados: {e}",
        ])
        return ("—", "—", "—", "—", "—", "—", "—", "—",
                {"vendedores": [], "indicadas": {}, "detalhe": []}, cf_reset, banner)

    k = d["kpis"]
    total_qtd = _i(k.get("total_qtd"))
    total_valor = _f(k.get("total_valor"))
    ticket = (total_valor / total_qtd) if total_qtd else 0.0

    vendedores = [{
        "responsavel":    r["responsavel"],
        "propria_qtd":    _i(r["propria_qtd"]),
        "propria_valor":  _f(r["propria_valor"]),
        "indicada_qtd":   _i(r["indicada_qtd"]),
        "indicada_valor": _f(r["indicada_valor"]),
        "total_qtd":      _i(r["total_qtd"]),
        "total_valor":    _f(r["total_valor"]),
    } for r in d["vendedores"]]

    indicadas_por_resp = {}
    for it in d["indicadas"]:
        indicadas_por_resp.setdefault(it["responsavel"], []).append({
            "negocio":          it.get("negocio"),
            "indicador":        it.get("indicador"),
            "tipo_de_contrato": it.get("tipo_de_contrato"),
            "data":             it.get("data"),
            "valor":            _f(it.get("valor")),
        })

    detalhe = [{
        "bitrix_id":        r.get("bitrix_id"),
        "cliente":          r.get("cliente"),
        "vendedor":         r.get("vendedor"),
        "tipo_venda":       r.get("tipo_venda"),   # 'interno' | 'indicado'
        "etapa":            r.get("etapa"),
        "tipo_de_contrato": r.get("tipo_de_contrato"),
        "valor":            _f(r.get("valor")),
    } for r in d["detalhe"]]

    store = {"vendedores": vendedores, "indicadas": indicadas_por_resp, "detalhe": detalhe}

    return (
        fmt_brl(total_valor), f"{fmt_num(total_qtd)} negócios",
        fmt_brl(k.get("propria_valor")), f"{fmt_num(k.get('propria_qtd'))} negócios",
        fmt_brl(k.get("indicada_valor")), f"{fmt_num(k.get('indicada_qtd'))} negócios",
        fmt_brl(ticket), "valor médio por negócio",
        store, cf_reset, None,
    )


# ── Render central: 3 tabelas + donut + legenda + chip (lê ct-data e cf-store) ─
@callback(
    Output("ct-vendedores", "children"),
    Output("ct-contratos", "children"),
    Output("tbl-detalhamento", "data"),
    Output("ct-donut", "figure"),
    Output("ct-donut-legend", "children"),
    Output("cf-chip-text", "children"),
    Output("cf-chip-wrap", "style"),
    Input("ct-data", "data"),
    Input("cf-store", "data"),
)
def render_views(data, cf):
    data = data or {}
    cf = cf or {"vendedor": None, "tipo_venda": None}
    vendedores = data.get("vendedores", [])
    indicadas = data.get("indicadas", {})
    detalhe = data.get("detalhe", [])

    vend_tbl = build_vendedores_table(vendedores, indicadas, cf)
    contr_tbl = build_contratos_table(detalhe, cf)
    det_data = build_detalhamento_data(detalhe, cf)
    donut = build_donut(vendedores, cf)
    legend = build_donut_legend(vendedores, cf)

    if cf.get("vendedor"):
        tip = cf.get("tipo_venda")
        txt = f" {cf['vendedor']}" + (f" · {TIPO_VENDA_LABEL.get(tip, '')}" if tip else "")
        chip_style = {"display": "inline-flex"}
    else:
        txt = ""
        chip_style = {"display": "none"}

    return vend_tbl, contr_tbl, det_data, donut, legend, txt, chip_style


# ── Cross-filter: toggle central ─────────────────────────────────────────────
def _cf_toggle(cur, vendedor, tipo):
    cur = cur or {}
    if cur.get("vendedor") == vendedor and cur.get("tipo_venda") == tipo:
        return {"vendedor": None, "tipo_venda": None}
    return {"vendedor": vendedor, "tipo_venda": tipo}


# A. Clique no donut (anel interno → vendedor; anel externo → vendedor + tipo).
@callback(
    Output("cf-store", "data", allow_duplicate=True),
    Output("ct-donut", "clickData"),
    Input("ct-donut", "clickData"),
    State("cf-store", "data"),
    prevent_initial_call=True,
)
def cf_from_donut(click, cur):
    if not click or not click.get("points"):
        return no_update, no_update
    cd = click["points"][0].get("customdata")
    if not isinstance(cd, list) or not cd:
        return no_update, None
    vendedor = cd[0]
    tipo = cd[1] if len(cd) >= 2 else None
    return _cf_toggle(cur, vendedor, tipo), None


# B. Clique num item da legenda → vendedor (tipo=None).
@callback(
    Output("cf-store", "data", allow_duplicate=True),
    Input({"type": "ct-leg", "index": ALL}, "n_clicks"),
    State("cf-store", "data"),
    prevent_initial_call=True,
)
def cf_from_legend(_n, cur):
    if not ctx.triggered or not ctx.triggered[0]["value"]:
        return no_update
    return _cf_toggle(cur, _dec(ctx.triggered_id["index"]), None)


# C. Clique na linha de vendedor (tabela) → vendedor (tipo=None).
@callback(
    Output("cf-store", "data", allow_duplicate=True),
    Input({"type": "ct-vend-row", "index": ALL}, "n_clicks"),
    State("cf-store", "data"),
    prevent_initial_call=True,
)
def cf_from_row(_n, cur):
    if not ctx.triggered or not ctx.triggered[0]["value"]:
        return no_update
    return _cf_toggle(cur, _dec(ctx.triggered_id["index"]), None)


# D. Limpar filtro pelo "×" do chip.
@callback(
    Output("cf-store", "data", allow_duplicate=True),
    Input("cf-chip-clear", "n_clicks"),
    prevent_initial_call=True,
)
def cf_clear(_n):
    return {"vendedor": None, "tipo_venda": None}


if __name__ == "__main__":
    app.run(
        host=os.getenv("APP_HOST", "127.0.0.1"),
        port=int(os.getenv("APP_PORT", "8051")),
        debug=os.getenv("APP_DEBUG", "true").lower() == "true",
    )
