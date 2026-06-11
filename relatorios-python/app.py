"""
NimbusTax — Relatórios (Dash)
Fase 1: Funil Diagnóstico.

Multi-tenant: o parceiro vem pela query string da URL (?parceiro=ID), o que
permite embarcar o relatório num iframe do portal do parceiro já filtrado.
Enquanto a coluna de parceiro não está ligada (ver queries.PARCEIRO_COLUMN),
o filtro é aceito mas não restringe os dados.

Rodar local:   python app.py
Produção:      gunicorn app:server -b 0.0.0.0:8050
"""

import os
import base64
from urllib.parse import parse_qs

import plotly.graph_objects as go
from dash import Dash, dcc, html, dash_table, Input, Output, State, callback, no_update, ALL, ctx

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


def parceiro_from_search(search):
    """Extrai ?parceiro=... da query string da URL."""
    if not search:
        return None
    qs = parse_qs(search.lstrip("?"))
    val = qs.get("parceiro", [None])[0]
    return val or None


# ── Cores do donut (mesmas da versão JS) ─────────────────────────────────────
DONUT_COLORS = [
    "#0DC2FF", "#26FF93", "#7C3AED", "#F59E0B", "#EF4444",
    "#10B981", "#3B82F6", "#EC4899", "#F97316", "#a0aec0",
]


def empty_fig(msg="Sem dados"):
    fig = go.Figure()
    fig.add_annotation(text=msg, showarrow=False,
                       font=dict(size=14, color="#a0aec0"))
    fig.update_layout(margin=dict(l=0, r=0, t=0, b=0),
                      xaxis=dict(visible=False), yaxis=dict(visible=False),
                      paper_bgcolor="rgba(0,0,0,0)", plot_bgcolor="rgba(0,0,0,0)")
    return fig


def _hex_to_rgba(hex_color, alpha):
    h = hex_color.lstrip("#")
    r, g, b = int(h[0:2], 16), int(h[2:4], 16), int(h[4:6], 16)
    return f"rgba({r},{g},{b},{alpha})"


def build_donut(rows, selected=None):
    if not rows:
        return empty_fig()
    labels = [r["produto"] for r in rows]
    values = [int(r["total"] or 0) for r in rows]
    colors = [DONUT_COLORS[i % len(DONUT_COLORS)] for i in range(len(values))]
    # Cross-filter visual: com produto selecionado, destaca a fatia (puxa pra fora)
    # e esmaece as demais. Sem seleção, tudo cheio.
    if selected and selected in labels:
        fill = [_hex_to_rgba(colors[i], 1.0 if labels[i] == selected else 0.3)
                for i in range(len(values))]
        pull = [0.07 if labels[i] == selected else 0 for i in range(len(values))]
    else:
        fill, pull = colors, 0
    fig = go.Figure(go.Pie(
        labels=labels, values=values, hole=0.45, pull=pull,
        marker=dict(colors=fill, line=dict(color="#fff", width=1)),
        textfont=dict(size=11),
        hovertemplate="<b>%{label}</b><br>%{value} (%{percent})<extra></extra>",
        sort=False,
    ))
    # Rótulos direto nas fatias (nome + %), sem painel de legenda lateral.
    fig.update_traces(
        textposition="outside",
        textinfo="label+percent",
        showlegend=False,
    )
    fig.update_layout(
        showlegend=False,
        margin=dict(t=20, b=20, l=20, r=20),
        paper_bgcolor="rgba(0,0,0,0)",
    )
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


def kpi_card(label, value_id, icon, color):
    return html.Div(className="rt-kpi", children=[
        html.Div(className="rt-kpi-icon", style={"color": color}, children=html.I(className=f"fas {icon}")),
        html.Div(children=[
            html.Div(label, className="rt-kpi-label"),
            html.Div("—", id=value_id, className="rt-kpi-value"),
        ]),
    ])


TABLE_BASE = dict(
    style_as_list_view=True,
    page_size=50,
    style_table={"overflowY": "auto", "maxHeight": "420px"},
    style_cell={"fontFamily": "Inter, sans-serif", "fontSize": "12.5px",
                "padding": "8px 10px", "border": "none"},
    style_header={"backgroundColor": "#f8fafc", "fontWeight": "600",
                  "color": "#475569", "textTransform": "uppercase",
                  "fontSize": "10.5px", "letterSpacing": "0.04em"},
    style_data={"borderBottom": "1px solid #f1f5f9"},
)

# Alinhamento padrão das colunas (igual ao Power BI): texto/ID à esquerda,
# números (Total / Valor) à direita. Vale para as três tabelas — ids ausentes
# numa tabela são simplesmente ignorados.
TABLE_ALIGN = [
    {"if": {"column_id": ["etapa", "etapa_ordenada", "status",
                          "cliente", "oportunidade", "observacoes", "id"]},
     "textAlign": "left"},
    {"if": {"column_id": ["total", "valor", "valor_soma"]},
     "textAlign": "right"},
]

TABS = ["Funil Diagnóstico", "Funil Operacional", "Funil Retificação", "Faturamento", "Dashboard"]
# Índice da aba → chave do funil (queries.PIPELINES). Só estas abas estão ativas.
TAB_TO_FUNIL = {0: "diagnostico", 1: "operacional", 2: "retificacao"}


def build_filter_table(rows, key, header, row_type, active_value):
    """Tabela HTML clicável usada como FILTRO — PADRÃO CANÔNICO do relatório.

    Sem DataTable → sem realce de célula do Dash. A linha do filtro ativo recebe
    .rt-row-active; o clique vira n_clicks por <tr> (id {type: row_type, index: valor}).
    Reutilizável para qualquer tabela-filtro (status, etapa, e futuros funis).
    Comportamento: clicar aplica, reclicar a mesma linha limpa, destaque na linha inteira.

    Args:
        key:          chave da 1ª coluna nos rows (ex.: "status", "etapa_ordenada").
        header:       rótulo da 1ª coluna.
        row_type:     "type" do id do <tr> (casa com o Input ALL do callback de clique).
        active_value: valor atualmente filtrado por este componente (ou None).
    """
    if not rows:
        return html.P("Sem dados", className="rt-empty")
    head = html.Thead(html.Tr([
        html.Th(header, style={"textAlign": "left"}),
        html.Th("Total", style={"textAlign": "right"}),
        html.Th("Valor", style={"textAlign": "right"}),
    ]))
    body = []
    for r in rows:
        val = r[key]
        active = (active_value == val)
        body.append(html.Tr(
            id={"type": row_type, "index": _enc(val)},   # base64 → ASCII-safe
            n_clicks=0,
            className="rt-status-row" + (" rt-row-active" if active else ""),
            children=[
                html.Td(val,                      style={"textAlign": "left"}),
                html.Td(fmt_num(r["total"]),      style={"textAlign": "right"}),
                html.Td(fmt_brl(r["valor_soma"]), style={"textAlign": "right"}),
            ],
        ))
    return html.Table([head, html.Tbody(body)], className="rt-table rt-table-click")



def diagnostico_layout():
    return html.Div(className="rt-grid", children=[

        # Coluna esquerda — tabela de etapas (clicável, é fonte do filtro de etapa)
        card("Nome da Etapa Numerado · clique para filtrar", icon="fa-list-ol",
             extra_class="rt-col-left", children=[
            html.Div(id="rt-etapa-table"),
        ]),

        # Coluna direita — status + KPIs + donut
        html.Div(className="rt-col-right", children=[
            card("Etapas Oportunidades · clique para filtrar", icon="fa-filter", children=[
                # Tabela HTML clicável (NÃO é DataTable) — assim não existe realce de
                # célula focada do Dash. O destaque é uma classe CSS na linha inteira.
                html.Div(id="rt-status-table"),
            ]),
            html.Div(className="rt-kpi-row", children=[
                kpi_card("Total de Oportunidades", "kpi-total", "fa-hashtag", "#26FF93"),
                kpi_card("Valor Total", "kpi-valor", "fa-dollar-sign", "#0DC2FF"),
            ]),
            card("Contagem Top 9 + Outros por Produto", icon="fa-chart-pie", children=[
                dcc.Graph(id="graph-donut", figure=empty_fig("Carregando…"),
                          config={"displayModeBar": False}, style={"height": "320px"}),
            ]),
        ]),

        # Linha inferior — detalhe
        card("Detalhe · máx. 500 registros · ID abre o negócio no Bitrix",
             icon="fa-table-list", extra_class="rt-col-full", children=[
            dash_table.DataTable(
                id="tbl-detalhe",
                columns=[
                    {"name": "ID", "id": "id", "presentation": "markdown"},
                    {"name": "Cliente", "id": "cliente"},
                    {"name": "Oportunidade", "id": "oportunidade"},
                    {"name": "Etapa", "id": "etapa"},
                    {"name": "Observações", "id": "observacoes"},
                    {"name": "Valor", "id": "valor"},
                ],
                style_cell_conditional=TABLE_ALIGN,
                markdown_options={"link_target": "_blank"},
                # Tabela de EXIBIÇÃO (não é filtro): desliga seleção/realce de célula
                # do Dash. Sem célula ativa → sem realce rosa e sem captura de clique.
                # Os links de ID (markdown <a>) continuam clicáveis normalmente.
                cell_selectable=False,
                **TABLE_BASE,
            ),
        ]),
    ])


# ── App ──────────────────────────────────────────────────────────────────────
app = Dash(
    __name__,
    title="NimbusTax — Relatórios",
    external_stylesheets=[
        "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css",
    ],
    suppress_callback_exceptions=True,
)
server = app.server  # alvo do gunicorn

app.layout = html.Div(className="rt-app", children=[
    dcc.Location(id="url"),
    # Filtro central — UM filtro ativo por vez: {"tipo": "etapa"|"status"|"produto", "valor": ...} ou None
    dcc.Store(id="rt-filtro-ativo", data=None),
    # Funil (pipeline) ativo — trocado pelas abas. Reaproveita TODOS os componentes.
    dcc.Store(id="rt-pipeline", data="diagnostico"),

    # Cabeçalho
    html.Div(className="rt-header", children=[
        html.Div(className="rt-brand", children="NimbusTax"),
        html.Div(className="rt-tabs", children=[
            html.Button(t, className="rt-tab" + (" rt-tab-active" if i == 0 else ""),
                        id={"type": "rt-tab", "index": i}, disabled=(i not in TAB_TO_FUNIL))
            for i, t in enumerate(TABS)
        ]),
        html.Button([html.I(className="fas fa-rotate"), " Atualizar"],
                    id="btn-refresh", className="rt-refresh"),
    ]),

    html.Div(id="error-banner"),
    diagnostico_layout(),
])


# ── Cross-filter central — UM filtro ativo por vez (clicar troca, reclicar limpa) ─
def _toggle(current, tipo, valor):
    """Reclicar o mesmo elemento limpa (None); clicar em outro substitui."""
    if current and current.get("tipo") == tipo and current.get("valor") == valor:
        return None
    return {"tipo": tipo, "valor": valor}


# Clique na tabela de ETAPAS
@callback(
    Output("rt-filtro-ativo", "data", allow_duplicate=True),
    Input({"type": "rt-etapa-row", "index": ALL}, "n_clicks"),
    State("rt-filtro-ativo", "data"),
    prevent_initial_call=True,
)
def click_etapa(_n, current):
    if not ctx.triggered or not ctx.triggered[0]["value"]:
        return no_update
    return _toggle(current, "etapa", _dec(ctx.triggered_id["index"]))


# Clique na tabela de STATUS
@callback(
    Output("rt-filtro-ativo", "data", allow_duplicate=True),
    Input({"type": "rt-status-row", "index": ALL}, "n_clicks"),
    State("rt-filtro-ativo", "data"),
    prevent_initial_call=True,
)
def click_status(_n, current):
    if not ctx.triggered or not ctx.triggered[0]["value"]:
        return no_update
    return _toggle(current, "status", _dec(ctx.triggered_id["index"]))


# Clique numa fatia do DONUT — clickData em self-loop (zera p/ permitir reclique).
# Todas as fatias filtram, inclusive "Outros" (a query trata como complemento do Top 9).
@callback(
    Output("rt-filtro-ativo", "data", allow_duplicate=True),
    Output("graph-donut", "clickData"),
    Input("graph-donut", "clickData"),
    State("rt-filtro-ativo", "data"),
    prevent_initial_call=True,
)
def click_product(click_data, current):
    if not click_data:
        return no_update, no_update
    produto = click_data["points"][0]["label"]
    return _toggle(current, "produto", produto), None


# ── Troca de aba (funil): muda o pipeline e RESETA o filtro ───────────────────
@callback(
    Output("rt-pipeline", "data"),
    Output("rt-filtro-ativo", "data", allow_duplicate=True),
    Input({"type": "rt-tab", "index": ALL}, "n_clicks"),
    State("rt-pipeline", "data"),
    prevent_initial_call=True,
)
def switch_tab(_n, atual):
    if not ctx.triggered or not ctx.triggered[0]["value"]:
        return no_update, no_update
    funil = TAB_TO_FUNIL.get(ctx.triggered_id["index"])
    if not funil or funil == atual:
        return no_update, no_update
    return funil, None   # troca o funil e limpa o filtro (cross-filter é por funil)


# Destaque da aba ativa conforme o funil selecionado
@callback(
    Output({"type": "rt-tab", "index": ALL}, "className"),
    Input("rt-pipeline", "data"),
)
def highlight_tab(funil):
    ativo = next((i for i, f in TAB_TO_FUNIL.items() if f == funil), 0)
    return ["rt-tab" + (" rt-tab-active" if i == ativo else "") for i in range(len(TABS))]


# ── Callback principal: carrega todos os dados a partir do filtro central ─────
@callback(
    Output("rt-etapa-table", "children"),
    Output("rt-status-table", "children"),
    Output("kpi-total", "children"),
    Output("kpi-valor", "children"),
    Output("graph-donut", "figure"),
    Output("tbl-detalhe", "data"),
    Output("error-banner", "children"),
    Input("url", "search"),
    Input("rt-filtro-ativo", "data"),
    Input("rt-pipeline", "data"),
    Input("btn-refresh", "n_clicks"),
)
def load_data(search, filtro, funil, _n):
    parceiro = parceiro_from_search(search)
    # Sempre banco real. Sem fallback para dados fictícios — erro claro se falhar.
    try:
        d = queries.get_funil(funil, parceiro=parceiro, filtro=filtro)
    except Exception as e:
        banner = html.Div(className="rt-error", children=[
            html.I(className="fas fa-triangle-exclamation"),
            f" Erro ao carregar os dados: {e}",
        ])
        return (build_filter_table([], "etapa_ordenada", "Etapa", "rt-etapa-row", None),
                build_filter_table([], "status", "Status", "rt-status-row", None),
                "—", "—", empty_fig("Erro"), [], banner)

    tipo = filtro["tipo"] if filtro else None
    val = filtro["valor"] if filtro else None

    # Tabelas-filtro (HTML clicáveis); a linha ativa recebe .rt-row-active só quando
    # ESTE componente é a fonte do filtro atual.
    etapa_table = build_filter_table(
        d["etapa_table"], "etapa_ordenada", "Etapa", "rt-etapa-row",
        val if tipo == "etapa" else None)
    status_table = build_filter_table(
        d["status_table"], "status", "Status", "rt-status-row",
        val if tipo == "status" else None)

    kpis = d["kpis"] or {}
    total_kpi = fmt_num(kpis.get("total"))
    valor_kpi = fmt_brl(kpis.get("valor_soma"))

    donut = build_donut(d["donut"], selected=(val if tipo == "produto" else None))

    detalhe = [
        {"id": f'[{r["bitrix_id"]}]({r["link_deal"]})',
         "cliente": r["cliente"],
         "oportunidade": r["oportunidade"],
         "etapa": r["etapa"],
         "observacoes": r["observacoes"],
         "valor": fmt_brl(r["valor"])}
        for r in d["detalhe"]
    ]

    return (etapa_table, status_table, total_kpi, valor_kpi, donut, detalhe, None)


if __name__ == "__main__":
    app.run(
        host=os.getenv("APP_HOST", "0.0.0.0"),
        port=int(os.getenv("APP_PORT", "8050")),
        debug=os.getenv("APP_DEBUG", "true").lower() == "true",
    )
