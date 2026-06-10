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
from urllib.parse import parse_qs

import plotly.graph_objects as go
from dash import Dash, dcc, html, dash_table, Input, Output, State, callback, no_update, ALL, ctx

import queries
import demo

# DEMO=1 → usa dados fictícios (não precisa de banco). Bom pra preview visual.
USE_DEMO = os.getenv("DEMO", "").lower() in ("1", "true", "yes", "on")

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


def build_donut(rows):
    if not rows:
        return empty_fig()
    labels = [r["produto"] for r in rows]
    values = [int(r["total"] or 0) for r in rows]
    colors = [DONUT_COLORS[i % len(DONUT_COLORS)] for i in range(len(values))]
    fig = go.Figure(go.Pie(
        labels=labels, values=values, hole=0.45,
        marker=dict(colors=colors),
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


def build_status_table(rows, current_filter):
    """Tabela HTML clicável do status (cross-filter). Sem DataTable → sem realce
    de célula do Dash. O destaque é a classe .rt-row-active na linha inteira;
    o clique vira n_clicks em cada <tr> (id por padrão {type, index})."""
    if not rows:
        return html.P("Sem dados", className="rt-empty")
    head = html.Thead(html.Tr([
        html.Th("Status", style={"textAlign": "left"}),
        html.Th("Total",  style={"textAlign": "right"}),
        html.Th("Valor",  style={"textAlign": "right"}),
    ]))
    body = []
    for r in rows:
        active = (r["status"] == current_filter)
        body.append(html.Tr(
            id={"type": "rt-status-row", "index": r["status"]},
            n_clicks=0,
            className="rt-status-row" + (" rt-row-active" if active else ""),
            children=[
                html.Td(r["status"],              style={"textAlign": "left"}),
                html.Td(fmt_num(r["total"]),      style={"textAlign": "right"}),
                html.Td(fmt_brl(r["valor_soma"]), style={"textAlign": "right"}),
            ],
        ))
    return html.Table([head, html.Tbody(body)], className="rt-table rt-table-click")



def diagnostico_layout():
    return html.Div(className="rt-grid", children=[

        # Coluna esquerda — tabela de etapas
        card("Nome da Etapa Numerado", icon="fa-list-ol", extra_class="rt-col-left", children=[
            dash_table.DataTable(
                id="tbl-etapa",
                columns=[
                    {"name": "Etapa", "id": "etapa_ordenada"},
                    {"name": "Total", "id": "total"},
                    {"name": "Valor", "id": "valor_soma"},
                ],
                style_cell_conditional=TABLE_ALIGN,
                **TABLE_BASE,
            ),
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
    dcc.Store(id="status-filter-store", data=None),

    # Cabeçalho
    html.Div(className="rt-header", children=[
        html.Div(className="rt-brand", children="NimbusTax"),
        html.Div(className="rt-tabs", children=[
            html.Button(t, className="rt-tab" + (" rt-tab-active" if i == 0 else ""),
                        id={"type": "rt-tab", "index": i}, disabled=(i != 0))
            for i, t in enumerate(TABS)
        ]),
        html.Button([html.I(className="fas fa-rotate"), " Atualizar"],
                    id="btn-refresh", className="rt-refresh"),
    ]),

    html.Div(id="error-banner"),
    diagnostico_layout(),
])


# ── Callback: cross-filter de status (clique na linha da tabela HTML) ─────────
@callback(
    Output("status-filter-store", "data"),
    Input({"type": "rt-status-row", "index": ALL}, "n_clicks"),
    State("status-filter-store", "data"),
    prevent_initial_call=True,
)
def click_status(_n_clicks_list, current):
    # Ignora disparos que não são clique real (ex.: as linhas são recriadas pelo
    # load_data com n_clicks=0 → o valor que disparou seria 0/None).
    if not ctx.triggered or not ctx.triggered[0]["value"]:
        return no_update
    status = ctx.triggered_id["index"]
    return None if status == current else status   # toggle (reclicar a mesma linha limpa)


# ── Callback principal: carrega todos os dados ───────────────────────────────
@callback(
    Output("tbl-etapa", "data"),
    Output("rt-status-table", "children"),
    Output("kpi-total", "children"),
    Output("kpi-valor", "children"),
    Output("graph-donut", "figure"),
    Output("tbl-detalhe", "data"),
    Output("error-banner", "children"),
    Input("url", "search"),
    Input("status-filter-store", "data"),
    Input("btn-refresh", "n_clicks"),
)
def load_data(search, status_filter, _n):
    parceiro = parceiro_from_search(search)
    try:
        if USE_DEMO:
            d = demo.get_diagnostico(status_filter=status_filter, parceiro=parceiro)
        else:
            d = queries.get_diagnostico(parceiro=parceiro, status_filter=status_filter)
    except Exception as e:
        banner = html.Div(className="rt-error", children=[
            html.I(className="fas fa-triangle-exclamation"),
            f" Erro ao carregar os dados: {e}",
        ])
        return ([], build_status_table([], None), "—", "—", empty_fig("Erro"), [], banner)

    etapa = [
        {"etapa_ordenada": r["etapa_ordenada"],
         "total": fmt_num(r["total"]),
         "valor_soma": fmt_brl(r["valor_soma"])}
        for r in d["etapa_table"]
    ]

    # Tabela de status (HTML clicável); a linha do filtro ativo recebe .rt-row-active.
    status_table = build_status_table(d["status_table"], status_filter)

    kpis = d["kpis"] or {}
    total_kpi = fmt_num(kpis.get("total"))
    valor_kpi = fmt_brl(kpis.get("valor_soma"))

    donut = build_donut(d["donut"])

    detalhe = [
        {"id": f'[{r["bitrix_id"]}]({r["link_deal"]})',
         "cliente": r["cliente"],
         "oportunidade": r["oportunidade"],
         "etapa": r["etapa"],
         "observacoes": r["observacoes"],
         "valor": fmt_brl(r["valor"])}
        for r in d["detalhe"]
    ]

    notice = None
    if USE_DEMO:
        notice = html.Div(className="rt-demo", children=[
            html.I(className="fas fa-flask"),
            " Modo demonstração — dados fictícios. Configure o .env com o banco para ver os dados reais.",
        ])

    return (etapa, status_table, total_kpi, valor_kpi, donut, detalhe, notice)


if __name__ == "__main__":
    app.run(
        host=os.getenv("APP_HOST", "0.0.0.0"),
        port=int(os.getenv("APP_PORT", "8050")),
        debug=os.getenv("APP_DEBUG", "true").lower() == "true",
    )
