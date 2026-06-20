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
import re
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

    # Ordena por total decrescente; "Outros" sempre por último.
    outros = [r for r in rows if r["produto"] == "Outros"]
    rest   = [r for r in rows if r["produto"] != "Outros"]
    rest_sorted = sorted(rest, key=lambda r: int(r["total"] or 0), reverse=True)
    rows_sorted = rest_sorted + outros

    labels = [r["produto"] for r in rows_sorted]
    values = [int(r["total"] or 0) for r in rows_sorted]
    total  = sum(values) or 1
    colors = [DONUT_COLORS[i % len(DONUT_COLORS)] for i in range(len(values))]

    # Cross-filter visual: com produto selecionado, destaca a fatia e esmaece as demais.
    if selected and selected in labels:
        fill = [_hex_to_rgba(colors[i], 1.0 if labels[i] == selected else 0.3)
                for i in range(len(values))]
        pull = [0.07 if labels[i] == selected else 0 for i in range(len(values))]
    else:
        fill, pull = colors, 0

    # Rótulo da legenda no formato "18.2% - Nome". O nome é truncado (…) para a
    # legenda ter largura LIMITADA — senão um nome muito longo alarga a legenda,
    # empurra a margem e encolhe o círculo só naquele card (quebra o alinhamento).
    # O nome completo continua no hover da fatia (%{label}).
    def _trunc(s, n=42):
        s = str(s)
        return s if len(s) <= n else s[:n - 1].rstrip() + "…"
    legend_labels = [f"{values[i] / total * 100:.1f}% - {_trunc(labels[i])}"
                     for i in range(len(labels))]

    fig = go.Figure(go.Pie(
        labels=labels, values=values, hole=0.65, pull=pull,
        marker=dict(colors=fill, line=dict(color="#fff", width=1)),
        hovertemplate="<b>%{label}</b><br>%{value} (%{percent})<extra></extra>",
        sort=False,
        textinfo="none",       # sem rótulos sobre as fatias — círculo limpo
        showlegend=False,      # a legenda vem dos Scatter (formato "% - Nome")
        automargin=False,      # não encolhe o círculo → tamanho/posição uniformes
        # Pizza travada num domínio fixo → círculo na MESMA posição em todos os cards.
        domain=dict(x=[0, 0.45], y=[0, 1]),
    ))

    # Legenda lateral própria: bolinha + "18.2% - Nome" (um Scatter "fantasma" por item).
    for i, leg_label in enumerate(legend_labels):
        color = fill[i] if isinstance(fill, list) else colors[i]
        fig.add_trace(go.Scatter(
            x=[None], y=[None],
            mode="markers",
            marker=dict(symbol="circle", size=8, color=color),
            name=leg_label,
            showlegend=True,
            hoverinfo="skip",
        ))

    fig.update_layout(
        showlegend=True,
        legend=dict(
            orientation="v",
            # x DENTRO da área de plotagem (<1): legenda não "empurra a margem", então
            # a área de plotagem (e o círculo, travado em domain x=[0,0.45]) fica do
            # MESMO tamanho/posição em todos os cards, independente do tamanho dos rótulos.
            x=0.47,
            y=0.5,
            xanchor="left",
            yanchor="middle",
            font=dict(size=8, family="Inter, sans-serif", color="#374151"),
            itemsizing="constant",
            tracegroupgap=1,
            itemclick=False,        # legenda não é clicável (filtro é só na fatia)
            itemdoubleclick=False,
        ),
        # Eixos cartesianos (dos Scatter) invisíveis E confinados longe da pizza
        # (que ocupa x=[0,0.45]) → a camada de clique do XY não cobre as fatias e o
        # cross-filter por clique continua funcionando.
        xaxis=dict(visible=False, fixedrange=True, domain=[0.99, 1.0]),
        yaxis=dict(visible=False, fixedrange=True, domain=[0, 0.01]),
        margin=dict(t=10, b=10, l=10, r=10),
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
    children = []
    if icon:
        children.append(html.Div(className="rt-kpi-icon", style={"color": color},
                                 children=html.I(className=f"fas {icon}")))
    children.append(html.Div(children=[
        html.Div(label, className="rt-kpi-label"),
        html.Div("—", id=value_id, className="rt-kpi-value"),
    ]))
    return html.Div(className="rt-kpi", children=children)


def kpi_bar():
    """Grouped KPI bar shown above 'Etapas do Funil'. Hidden for tabs with no period data."""
    def group(period_label, badge_class, id_criados, id_concluidos):
        return html.Div(className=f"rt-kpibar-card", children=[
            html.Span(period_label, className=f"rt-kpibar-badge {badge_class}"),
            html.Div(className="rt-kpibar-inner", children=[
                html.Div(className="rt-kpibar-half", children=[
                    html.Div("Criados", className="rt-kpibar-label"),
                    html.Div("—", id=id_criados, className="rt-kpibar-value accent"),
                ]),
                html.Div(className="rt-kpibar-half rt-kpibar-half-right", children=[
                    html.Div("Concluídos", className="rt-kpibar-label"),
                    html.Div("—", id=id_concluidos, className="rt-kpibar-value green"),
                ]),
            ]),
        ])

    return html.Div(
        id="rt-kpibar-wrap",
        className="rt-kpibar-row",
        children=[
            group("Últimos 7 dias",  "badge-7",  "kpibar-criados-7",  "kpibar-concluidos-7"),
            group("Últimos 30 dias", "badge-30", "kpibar-criados-30", "kpibar-concluidos-30"),
        ],
    )


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

TABS = ["Funil Diagnóstico", "Funil Operacional", "Funil Retificação", "Sem Oportunidade", "Dashboard"]
# Índice da aba → chave do funil (queries.PIPELINES).
TAB_TO_FUNIL = {0: "diagnostico", 1: "operacional", 2: "retificacao"}
TAB_SEM_OP = 3                                  # aba "Sem Oportunidade" (modo, não troca o funil)
TAB_DASHBOARD = 4                               # aba "Dashboard" (resumo de todos os funis)
ABAS_ATIVAS = set(TAB_TO_FUNIL) | {TAB_SEM_OP, TAB_DASHBOARD}  # abas habilitadas


def _strip_etapa_prefix(s):
    """Remove o prefixo de ordenação 'NN - ' (só para EXIBIÇÃO na tabela de etapas).
    O valor real (com prefixo) é preservado para id/filtro/ordenação."""
    return re.sub(r"^\d+\s*-\s*", "", str(s), count=1)


def build_filter_table(rows, key, header, row_type, active_value, display_key=None):
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
        display_key:  se informado, EXIBE r[display_key] na 1ª coluna em vez do valor real.
                      O id/filtro/ordenação continuam usando `key` (valor real intacto).
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
        val = r[key]                                          # valor real (id/filtro/sort)
        label = r[display_key] if display_key else val        # texto exibido
        active = (active_value == val)
        body.append(html.Tr(
            id={"type": row_type, "index": _enc(val)},   # base64 → ASCII-safe
            n_clicks=0,
            className="rt-status-row" + (" rt-row-active" if active else ""),
            children=[
                html.Td(label,                    style={"textAlign": "left"}),
                html.Td(fmt_num(r["total"]),      style={"textAlign": "right"}),
                html.Td(fmt_brl(r["valor_soma"]), style={"textAlign": "right"}),
            ],
        ))
    return html.Table([head, html.Tbody(body)], className="rt-table rt-table-click")



def dashboard_card(title, data, icon="fa-chart-pie"):
    """Single panel card for the Dashboard tab."""
    kpi_p = data.get("kpi_periodico")
    children = []

    # KPIs: total + valor
    children.append(html.Div(className="db-kpi-row", children=[
        html.Div(className="db-kpi", children=[
            html.Div("Total de Oportunidades", className="db-kpi-label"),
            html.Div(fmt_num(data.get("total")), className="db-kpi-value"),
        ]),
        html.Div(className="db-kpi", children=[
            html.Div("Valor Total", className="db-kpi-label"),
            html.Div(fmt_brl(data.get("valor_soma")), className="db-kpi-value"),
        ]),
    ]))

    # Period badges (only for diagnostico and operacional)
    if kpi_p:
        children.append(html.Div(className="db-period-row", children=[
            html.Div(className="db-period-card", children=[
                html.Span("Últimos 7 dias", className="rt-kpibar-badge badge-7"),
                html.Div(className="rt-kpibar-inner", children=[
                    html.Div(className="rt-kpibar-half", children=[
                        html.Div("Criados", className="rt-kpibar-label"),
                        html.Div(fmt_num(kpi_p.get("criados_7")), className="rt-kpibar-value accent"),
                    ]),
                    html.Div(className="rt-kpibar-half rt-kpibar-half-right", children=[
                        html.Div("Concluídos", className="rt-kpibar-label"),
                        html.Div(fmt_num(kpi_p.get("concluidos_7")), className="rt-kpibar-value green"),
                    ]),
                ]),
            ]),
            html.Div(className="db-period-card", children=[
                html.Span("Últimos 30 dias", className="rt-kpibar-badge badge-30"),
                html.Div(className="rt-kpibar-inner", children=[
                    html.Div(className="rt-kpibar-half", children=[
                        html.Div("Criados", className="rt-kpibar-label"),
                        html.Div(fmt_num(kpi_p.get("criados_30")), className="rt-kpibar-value accent"),
                    ]),
                    html.Div(className="rt-kpibar-half rt-kpibar-half-right", children=[
                        html.Div("Concluídos", className="rt-kpibar-label"),
                        html.Div(fmt_num(kpi_p.get("concluidos_30")), className="rt-kpibar-value green"),
                    ]),
                ]),
            ]),
        ]))

    # Donut
    children.append(
        dcc.Graph(
            figure=build_donut(data.get("donut", [])),
            config={"displayModeBar": False},
            style={"height": "260px"},
        )
    )

    return card(title, children, icon=icon, extra_class="db-card")


def dashboard_layout(parceiro=None):
    try:
        d = queries.get_dashboard(parceiro=parceiro)
    except Exception as e:
        return html.Div(className="rt-error", children=[
            html.I(className="fas fa-triangle-exclamation"),
            f" Erro ao carregar o Dashboard: {e}",
        ])

    panels = [
        ("Funil Diagnóstico",        d["diagnostico"]),
        ("Funil Operacional",        d["operacional"]),
        ("Funil Retificação",        d["retificacao"]),
        ("Oportunidades Suspensas",  d["suspenso"]),
        ("Sem Oportunidade",         d["sem_op"]),
    ]

    cards = [dashboard_card(title, data) for title, data in panels]
    # 6th slot left empty (future: Faturamento)
    cards.append(html.Div())

    return html.Div(className="db-grid", children=cards)


def diagnostico_layout():
    return html.Div(children=[
        kpi_bar(),
        html.Div(className="rt-grid", children=[

        # Coluna esquerda — tabela de etapas (clicável, é fonte do filtro de etapa)
        card("Etapas do Funil", icon="fa-list-ol",
             extra_class="rt-col-left", children=[
            html.Div(id="rt-etapa-table"),
        ]),

        # Coluna direita — status + KPIs + donut
        html.Div(className="rt-col-right", children=[
            # Wrapper para esconder a tabela de status na aba "Sem Oportunidade"
            html.Div(id="rt-status-wrap", children=[
                card("Status dos Negócios", icon="fa-filter", children=[
                    # Tabela HTML clicável (NÃO é DataTable) — assim não existe realce de
                    # célula focada do Dash. O destaque é uma classe CSS na linha inteira.
                    html.Div(id="rt-status-table"),
                ]),
            ]),
            html.Div(className="rt-kpi-row", children=[
                kpi_card("Total de Oportunidades", "kpi-total", None, "#26FF93"),
                kpi_card("Valor Total", "kpi-valor", None, "#0DC2FF"),
            ]),
            card("Contagem Top 9 + Outros por Produto", icon="fa-chart-pie", children=[
                dcc.Graph(id="graph-donut", figure=empty_fig("Carregando…"),
                          config={"displayModeBar": False}, style={"height": "320px"}),
            ]),
        ]),

        # Linha inferior — detalhe
        card("Detalhe",
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
    # Modo: "normal" (exclui Sem Oportunidade) ou "sem_op" (só Sem Oportunidade)
    dcc.Store(id="rt-modo", data="normal"),
    # Índice da aba ativa (0-2 funis · 3 Sem Oportunidade · 4 Dashboard) — decide o conteúdo principal
    dcc.Store(id="rt-tab-idx", data=0),

    # Cabeçalho
    html.Div(className="rt-header", children=[
        html.Div(className="rt-brand", children="NimbusTax"),
        html.Div(className="rt-tabs", children=[
            html.Button(t, className="rt-tab" + (" rt-tab-active" if i == 0 else ""),
                        id={"type": "rt-tab", "index": i}, disabled=(i not in ABAS_ATIVAS))
            for i, t in enumerate(TABS)
        ]),
        html.Div(className="rt-header-right", children=[
            # Botão "Filtro Data" que abre um painel com os dois campos de data
            html.Div(className="rt-datawrap", children=[
                html.Button([html.I(className="fas fa-calendar-days"), " Filtro Data"],
                            id="rt-data-btn", className="rt-refresh"),
                html.Div(id="rt-data-panel", className="rt-data-panel", children=[
                    html.Div(className="rt-data-fields", children=[
                        html.Div(className="rt-data-field", children=[
                            html.Label("De (Início)", className="rt-data-flabel"),
                            # campo editável (digitar) + calendário ao clicar
                            dcc.DatePickerSingle(id="rt-data-de", display_format="DD/MM/YYYY",
                                                 placeholder="dd/mm/aaaa", clearable=True),
                        ]),
                        html.Div(className="rt-data-field", children=[
                            html.Label("Até (Fim)", className="rt-data-flabel"),
                            dcc.DatePickerSingle(id="rt-data-ate", display_format="DD/MM/YYYY",
                                                 placeholder="dd/mm/aaaa", clearable=True),
                        ]),
                    ]),
                    html.Div(id="rt-data-limpar-wrap", style={"display": "none"}, children=[
                        html.Button("Limpar", id="rt-data-limpar", className="rt-data-limpar"),
                    ]),
                ]),
            ]),
            html.Button([html.I(className="fas fa-rotate"), " Atualizar"],
                        id="btn-refresh", className="rt-refresh"),
        ]),
    ]),

    html.Div(id="error-banner"),
    # Funil sempre montado (load_data popula seus componentes sem corrida); o
    # Dashboard fica num container irmão, mostrado/escondido por estilo.
    html.Div(id="rt-main-content", children=[
        html.Div(id="rt-funil-view", children=diagnostico_layout()),
        html.Div(id="rt-dashboard-view", style={"display": "none"}),
    ]),
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


# ── Troca de aba: funil (0-2) muda o pipeline+modo=normal; "Sem Oportunidade" (3)
#    mantém o funil e só muda o modo; "Dashboard" (4) só troca o conteúdo principal.
#    Em todos os casos (menos reclicar a aba ativa) RESETA o filtro. ──────────────
@callback(
    Output("rt-pipeline", "data"),
    Output("rt-modo", "data"),
    Output("rt-filtro-ativo", "data", allow_duplicate=True),
    Output("rt-tab-idx", "data"),
    Input({"type": "rt-tab", "index": ALL}, "n_clicks"),
    State("rt-pipeline", "data"),
    State("rt-modo", "data"),
    State("rt-tab-idx", "data"),
    prevent_initial_call=True,
)
def switch_tab(_n, pipe_atual, modo_atual, tab_atual):
    if not ctx.triggered or not ctx.triggered[0]["value"]:
        return no_update, no_update, no_update, no_update
    idx = ctx.triggered_id["index"]
    if idx == tab_atual:                          # já está nessa aba → nada a fazer
        return no_update, no_update, no_update, no_update
    if idx == TAB_DASHBOARD:                       # aba "Dashboard" (só troca o conteúdo)
        return no_update, no_update, no_update, TAB_DASHBOARD
    if idx in TAB_TO_FUNIL:                        # abas de funil
        return TAB_TO_FUNIL[idx], "normal", None, idx   # troca funil, modo normal, reseta filtro
    if idx == TAB_SEM_OP:                          # aba "Sem Oportunidade"
        return no_update, "sem_op", None, TAB_SEM_OP    # mantém o funil, só muda o modo
    return no_update, no_update, no_update, no_update


# Destaque da aba ativa (funil · "Sem Oportunidade" · "Dashboard")
@callback(
    Output({"type": "rt-tab", "index": ALL}, "className"),
    Input("rt-pipeline", "data"),
    Input("rt-modo", "data"),
    Input("rt-tab-idx", "data"),
)
def highlight_tab(funil, modo, tab_idx):
    if tab_idx == TAB_DASHBOARD:
        ativo = TAB_DASHBOARD
    elif modo == "sem_op":
        ativo = TAB_SEM_OP
    else:
        ativo = next((i for i, f in TAB_TO_FUNIL.items() if f == funil), 0)
    return ["rt-tab" + (" rt-tab-active" if i == ativo else "") for i in range(len(TABS))]


# ── Conteúdo principal: mostra o funil OU o Dashboard (sem recriar o funil) ───
# O funil fica SEMPRE montado e é só escondido — assim load_data popula seus
# componentes sem corrida com um re-render. O Dashboard é (re)construído server-
# side só quando ativo (ao trocar de aba, ou via btn-refresh/url já nele).
@callback(
    Output("rt-funil-view", "style"),
    Output("rt-dashboard-view", "style"),
    Output("rt-dashboard-view", "children"),
    Input("rt-tab-idx", "data"),
    Input("url", "search"),
    Input("btn-refresh", "n_clicks"),
    prevent_initial_call=True,
)
def render_main_content(tab_idx, search, _n):
    if tab_idx == TAB_DASHBOARD:
        return ({"display": "none"}, {"display": "block"},
                dashboard_layout(parceiro=parceiro_from_search(search)))
    # funil / Sem Oportunidade: mostra o funil, esconde e libera o Dashboard
    return {"display": "block"}, {"display": "none"}, None


# Esconde a tabela de status na aba "Sem Oportunidade"
@callback(
    Output("rt-status-wrap", "style"),
    Input("rt-modo", "data"),
)
def toggle_status_card(modo):
    return {"display": "none"} if modo == "sem_op" else {"display": "block"}


# ── Filtro de data: abrir/fechar painel, mostrar "Limpar", limpar ────────────
@callback(
    Output("rt-data-panel", "className"),
    Input("rt-data-btn", "n_clicks"),
    State("rt-data-panel", "className"),
    prevent_initial_call=True,
)
def toggle_data_panel(_n, cls):
    return "rt-data-panel" if (cls and "open" in cls) else "rt-data-panel open"


@callback(
    Output("rt-data-limpar-wrap", "style"),
    Input("rt-data-de", "date"),
    Input("rt-data-ate", "date"),
)
def toggle_limpar(de, ate):
    # "Limpar" só aparece quando ao menos uma data está preenchida
    return {"display": "block"} if (de or ate) else {"display": "none"}


@callback(
    Output("rt-data-de", "date"),
    Output("rt-data-ate", "date"),
    Input("rt-data-limpar", "n_clicks"),
    prevent_initial_call=True,
)
def limpar_datas(_n):
    return None, None


# ── Barra de KPIs por período (Criados/Concluídos — 7 e 30 dias) ──────────────
# Só Diagnóstico e Operacional têm os campos de data; some nas demais abas.
# Inclui rt-modo: na aba "Sem Oportunidade" o funil (rt-pipeline) não muda, então
# sem esse Input a barra continuaria visível — escondemos quando modo == "sem_op".
@callback(
    Output("kpibar-criados-7",    "children"),
    Output("kpibar-concluidos-7", "children"),
    Output("kpibar-criados-30",   "children"),
    Output("kpibar-concluidos-30", "children"),
    Output("rt-kpibar-wrap",      "style"),
    Input("rt-pipeline", "data"),
    Input("rt-modo", "data"),
    Input("url", "search"),
    Input("btn-refresh", "n_clicks"),
)
def update_kpibar(funil, modo, search, _n):
    FUNIS_COM_KPI = {"diagnostico", "operacional"}
    if modo == "sem_op" or funil not in FUNIS_COM_KPI:
        return "—", "—", "—", "—", {"display": "none"}

    parceiro = parceiro_from_search(search)
    try:
        d = queries.get_kpi_periodico(funil, parceiro=parceiro)
    except Exception:
        # "grid" (não "block") p/ não sobrepor o display:grid da classe .rt-kpibar-row
        return "—", "—", "—", "—", {"display": "grid"}

    if not d:
        return "—", "—", "—", "—", {"display": "none"}

    return (
        fmt_num(d.get("criados_7")),
        fmt_num(d.get("concluidos_7")),
        fmt_num(d.get("criados_30")),
        fmt_num(d.get("concluidos_30")),
        {"display": "grid"},
    )


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
    Input("rt-modo", "data"),
    Input("rt-data-de", "date"),
    Input("rt-data-ate", "date"),
    Input("rt-tab-idx", "data"),
    Input("btn-refresh", "n_clicks"),
)
def load_data(search, filtro, funil, modo, data_de, data_ate, tab_idx, _n):
    # No Dashboard os componentes do funil não existem — não consulta nem atualiza.
    # (rt-tab-idx é Input p/ repovoar o funil ao VOLTAR do Dashboard.)
    if tab_idx == TAB_DASHBOARD:
        return (no_update,) * 7
    parceiro = parceiro_from_search(search)
    # Regra do período: ambos → intervalo De..Até; só um → aquele dia exato;
    # nenhum → sem filtro de data.
    if data_de and data_ate:
        dd, da = data_de, data_ate
    elif data_de:
        dd = da = data_de
    elif data_ate:
        dd = da = data_ate
    else:
        dd = da = None
    # Sempre banco real. Sem fallback para dados fictícios — erro claro se falhar.
    try:
        d = queries.get_funil(funil, parceiro=parceiro, filtro=filtro, data_de=dd, data_ate=da,
                              modo=modo or "normal")
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
    # Etapa: EXIBE o nome sem o prefixo "NN - " (display), mas mantém o valor real
    # (etapa_ordenada) para id/filtro/ordenação. Só esta tabela muda o rótulo.
    etapa_rows = [{**r, "etapa_display": _strip_etapa_prefix(r["etapa_ordenada"])}
                  for r in d["etapa_table"]]
    etapa_table = build_filter_table(
        etapa_rows, "etapa_ordenada", "Etapa", "rt-etapa-row",
        val if tipo == "etapa" else None, display_key="etapa_display")
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
