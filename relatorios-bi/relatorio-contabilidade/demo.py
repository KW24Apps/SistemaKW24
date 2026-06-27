"""
ContaFarma — Relatório Contabilidade (MODO DEMO)

Roda offline: sem banco, sem túnel SSH, sem .env.
Dados fictícios hardcoded — cenário realista da ContaFarma.
Porta: 8052

Como rodar:
    cd relatorios-bi/relatorio-contabilidade
    python demo.py
    → http://localhost:8052
"""

import base64
import calendar
from datetime import date

import plotly.graph_objects as go
from dash import Dash, dcc, html, dash_table, Input, Output, State, callback, no_update, ALL, ctx

# ── Constantes (idênticas ao app.py) ─────────────────────────────────────────
ABAS = [("fechadas", "Vendas Fechadas"), ("negociacao", "Em Negociação")]
ABA_DEFAULT = "fechadas"
TIPO_VENDA_LABEL = {"interno": "Interno", "indicado": "Indicado"}
CF_EMPTY = {"vendedor": None, "tipo_venda": None, "tipo_contrato": None}

COR_INTERNO  = "#00BBBC"
COR_INDICADO = "#f6ad55"
VEND_COLORS  = [
    "#6366F1", "#EC4899", "#A855F7", "#EF4444", "#3B82F6",
    "#D946EF", "#F43F5E", "#8B5CF6", "#0EA5E9", "#FB7185",
]

_R_INNER_BASE, _R_INNER_TOP = 0.40, 0.60
_R_OUTER_BASE, _R_OUTER_TOP = 0.62, 0.82
_R_NAME    = (_R_INNER_BASE + _R_INNER_TOP) / 2
_R_PCT     = (_R_OUTER_BASE + _R_OUTER_TOP) / 2
_GAP_DEG   = 0.0
_PCT_MIN_DEG = 13.0

DETALHE_COLS = [
    {"name": "ID",               "id": "id",               "presentation": "markdown"},
    {"name": "Cliente",          "id": "cliente"},
    {"name": "Vendedor",         "id": "vendedor"},
    {"name": "Tipo de Venda",    "id": "tipo_venda"},
    {"name": "Etapa",            "id": "etapa"},
    {"name": "Tipo de Contrato", "id": "tipo_de_contrato"},
    {"name": "Valor",            "id": "valor"},
]
DETALHE_STYLE_CELL_COND = [
    {"if": {"column_id": "valor"},    "textAlign": "right"},
    {"if": {"column_id": "id"},       "textAlign": "left", "minWidth": "70px"},
    {"if": {"column_id": "cliente"},  "minWidth": "180px"},
    {"if": {"column_id": "vendedor"}, "minWidth": "150px"},
]


def mes_atual_range():
    hoje  = date.today()
    ultimo = calendar.monthrange(hoje.year, hoje.month)[1]
    return (date(hoje.year, hoje.month, 1).isoformat(),
            date(hoje.year, hoje.month, ultimo).isoformat())


MES_INI, MES_FIM = mes_atual_range()


# ── Helpers de formatação ────────────────────────────────────────────────────
def _enc(v):
    return base64.urlsafe_b64encode(str(v).encode("utf-8")).decode("ascii")

def _dec(v):
    return base64.urlsafe_b64decode(str(v).encode("ascii")).decode("utf-8")

def fmt_brl(v):
    try:   v = float(v or 0)
    except (TypeError, ValueError): v = 0.0
    return "R$ " + f"{v:,.2f}".replace(",", "X").replace(".", ",").replace("X", ".")

def fmt_num(v):
    try:   v = int(float(v or 0))
    except (TypeError, ValueError): v = 0
    return f"{v:,}".replace(",", ".")

def _f(v):
    try:   return float(v or 0)
    except (TypeError, ValueError): return 0.0

def _i(v):
    try:   return int(float(v or 0))
    except (TypeError, ValueError): return 0

def vend_color(i):
    return VEND_COLORS[i % len(VEND_COLORS)]

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
            html.Div("—", id=sub_id,   className="rt-kpi-sub"),
        ]),
    ])

def kpi_row():
    return html.Div(className="rt-kpi-row", children=[
        kpi_card("Vendas Total",     "fa-layer-group", "kpi-total-valor",    "kpi-total-qtd",    "kpi-accent-total"),
        kpi_card("Vendas Internas",  "fa-house",       "kpi-propria-valor",  "kpi-propria-qtd",  "kpi-accent-propria"),
        kpi_card("Vendas Indicadas", "fa-handshake",   "kpi-indicada-valor", "kpi-indicada-qtd", "kpi-accent-indicada"),
        kpi_card("Ticket Médio",     "fa-receipt",     "kpi-ticket",         "kpi-ticket-sub",   "kpi-accent-ticket"),
    ])

def data_filter_bar():
    return html.Div(className="rt-header-right", children=[
        html.Div(className="rt-datawrap", children=[
            html.Button([html.I(className="fas fa-calendar-days"), " Filtro Data"],
                        id="ct-data-btn", className="rt-refresh"),
            html.Div(id="ct-data-panel", className="rt-data-panel", children=[
                html.Div(className="rt-data-fields", children=[
                    html.Div(className="rt-data-field", children=[
                        html.Label("De (Início)", className="rt-data-flabel"),
                        dcc.DatePickerSingle(id="ct-data-de", display_format="DD/MM/YYYY",
                                             placeholder="dd/mm/aaaa", clearable=True,
                                             date=MES_INI),
                    ]),
                    html.Div(className="rt-data-field", children=[
                        html.Label("Até (Fim)", className="rt-data-flabel"),
                        dcc.DatePickerSingle(id="ct-data-ate", display_format="DD/MM/YYYY",
                                             placeholder="dd/mm/aaaa", clearable=True,
                                             date=MES_FIM),
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


# ── Donut por vendedor ────────────────────────────────────────────────────────
def _vend_donut_rows(vendedores):
    vs = [v for v in vendedores if _i(v["total_qtd"]) > 0]
    return sorted(vs, key=lambda r: _i(r["total_qtd"]), reverse=True)

def build_donut(vendedores, cf):
    vs = _vend_donut_rows(vendedores)
    if not vs:
        return empty_fig("Sem dados para o período")
    n   = len(vs)
    arc = 360.0 / n
    names  = [v["responsavel"]  for v in vs]
    totals = [_i(v["total_qtd"]) for v in vs]
    intern = [_i(v["propria_qtd"]) for v in vs]
    indic  = [_i(v["indicada_qtd"]) for v in vs]
    grand  = sum(totals) or 1
    sel      = (cf or {}).get("vendedor")
    sel_tipo = (cf or {}).get("tipo_venda")

    def keep(name, tipo=None):
        if not sel: return True
        if name != sel: return False
        return (not sel_tipo) or (tipo is None) or (sel_tipo == tipo)

    def col(c, on):
        return c if on else _hex_to_rgba(c, 0.28)

    in_theta, in_w, in_base, in_r, in_col, in_custom = [], [], [], [], [], []
    for i in range(n):
        center = i * arc + arc / 2
        in_theta.append(center); in_w.append(arc - _GAP_DEG)
        in_base.append(_R_INNER_BASE); in_r.append(_R_INNER_TOP - _R_INNER_BASE)
        in_col.append(col(vend_color(i), keep(names[i])))
        in_custom.append([names[i]])

    ou_theta, ou_w, ou_base, ou_r, ou_col, ou_custom = [], [], [], [], [], []
    pct_theta, pct_r, pct_txt = [], [], []
    for i in range(n):
        tot    = totals[i] or 1
        usable = arc - _GAP_DEG
        start  = i * arc + _GAP_DEG / 2
        iw = usable * (intern[i] / tot)
        dw = usable * (indic[i]  / tot)
        ic = start + iw / 2
        ou_theta.append(ic); ou_w.append(iw)
        ou_base.append(_R_OUTER_BASE); ou_r.append(_R_OUTER_TOP - _R_OUTER_BASE)
        ou_col.append(col(COR_INTERNO, keep(names[i], "interno")))
        ou_custom.append([names[i], "interno"])
        dc = start + iw + dw / 2
        ou_theta.append(dc); ou_w.append(dw)
        ou_base.append(_R_OUTER_BASE); ou_r.append(_R_OUTER_TOP - _R_OUTER_BASE)
        ou_col.append(col(COR_INDICADO, keep(names[i], "indicado")))
        ou_custom.append([names[i], "indicado"])
        pct_theta.append(ic); pct_r.append(_R_PCT)
        pct_txt.append(f"{intern[i]/tot*100:.0f}%" if iw >= _PCT_MIN_DEG else "")
        pct_theta.append(dc); pct_r.append(_R_PCT)
        pct_txt.append(f"{indic[i]/tot*100:.0f}%"  if dw >= _PCT_MIN_DEG else "")

    fig = go.Figure()
    fig.add_trace(go.Barpolar(theta=in_theta, width=in_w, base=in_base, r=in_r,
        marker=dict(color=in_col, line=dict(color="#ffffff", width=1)),
        customdata=in_custom, name="inner",
        hovertemplate="%{customdata[0]}<extra></extra>"))
    fig.add_trace(go.Barpolar(theta=ou_theta, width=ou_w, base=ou_base, r=ou_r,
        marker=dict(color=ou_col, line=dict(color="#ffffff", width=1)),
        customdata=ou_custom, name="outer",
        hovertemplate="%{customdata[0]} · %{customdata[1]}<extra></extra>"))
    fig.add_trace(go.Scatterpolar(theta=pct_theta, r=pct_r, mode="text", text=pct_txt,
        textfont=dict(color="#1f2937", size=10, family="Inter"),
        hoverinfo="skip", showlegend=False, name="pcts", cliponaxis=False))
    fig.update_layout(
        margin=dict(t=8, b=8, l=8, r=8),
        paper_bgcolor="rgba(0,0,0,0)", showlegend=False,
        polar=dict(domain=dict(x=[0, 1], y=[0, 1]), bgcolor="rgba(0,0,0,0)",
                   radialaxis=dict(range=[0, 1], visible=False),
                   angularaxis=dict(visible=False, rotation=90, direction="clockwise"),
                   hole=0),
        meta=dict(vendedores=[dict(name=names[i], theta=i*arc+arc/2, color=vend_color(i))
                               for i in range(n)], n=n, r_outer_top=_R_OUTER_TOP),
        annotations=[
            dict(text=f"<b>{fmt_num(grand)}</b>", x=0.5, y=0.53, xref="paper", yref="paper",
                 showarrow=False, font=dict(size=30, color="#263846", family="Rubik")),
            dict(text="NEGÓCIOS", x=0.5, y=0.435, xref="paper", yref="paper",
                 showarrow=False, font=dict(size=11, color="#64748b", family="Inter")),
        ],
    )
    return fig

def build_donut_legend(vendedores, cf):
    vs = _vend_donut_rows(vendedores)
    if not vs:
        return [html.Div("Sem dados para o período.", className="rt-empty")]
    grand = sum(_i(v["total_qtd"]) for v in vs) or 1
    sel   = (cf or {}).get("vendedor")
    items = []
    for i, v in enumerate(vs):
        name     = v["responsavel"]
        tot      = _i(v["total_qtd"])
        interno  = _i(v["propria_qtd"])
        indicado = _i(v["indicada_qtd"])
        ipct = (interno  / tot * 100) if tot else 0
        dpct = (indicado / tot * 100) if tot else 0
        dim    = bool(sel) and name != sel
        active = name == sel
        items.append(html.Div(
            id={"type": "ct-leg", "index": _enc(name)}, n_clicks=0,
            className="ct-leg-item" + (" ct-dim" if dim else "") + (" ct-leg-active" if active else ""),
            children=[
                html.Span(className="ct-leg-dot", style={"backgroundColor": vend_color(i)}),
                html.Div(className="ct-leg-main", children=[
                    html.Div(className="ct-leg-top", children=[
                        html.Span(name, className="ct-leg-name", title=name),
                        html.Span(f"{tot/grand*100:.0f}%", className="ct-leg-pct"),
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
    items.append(html.Div(className="ct-leg-foot", children=[
        html.Div(className="ct-leg-foot-row", children=[
            html.Span(className="ct-leg-dot", style={"backgroundColor": COR_INTERNO}),
            html.Span("Interno", className="ct-leg-foot-name"),
            html.Span(className="ct-leg-dot", style={"backgroundColor": COR_INDICADO, "marginLeft": "14px"}),
            html.Span("Indicado", className="ct-leg-foot-name"),
        ]),
        html.Div("Tamanho do arco externo = split interno/indicado", className="ct-leg-foot-cap"),
    ]))
    return items

def build_team_donut(detalhe, cf):
    filt     = _filter_detalhe(detalhe, cf)
    interno  = sum(1 for d in filt if d.get("tipo_venda") == "interno")
    indicado = sum(1 for d in filt if d.get("tipo_venda") == "indicado")
    total    = interno + indicado
    if total == 0:
        return empty_fig("Sem dados")
    fig = go.Figure(go.Pie(
        labels=["Interno", "Indicado"], values=[interno, indicado],
        marker=dict(colors=[COR_INTERNO, COR_INDICADO], line=dict(color="#ffffff", width=2)),
        hole=0.76, sort=False, direction="clockwise", rotation=0,
        texttemplate="%{percent:.0%}", textposition="inside",
        insidetextfont=dict(color="#06343a", size=13),
        hovertemplate="%{label}<br>%{value} negócios (%{percent})<extra></extra>",
    ))
    fig.update_layout(
        margin=dict(t=6, b=6, l=6, r=6), paper_bgcolor="rgba(0,0,0,0)", showlegend=False,
        annotations=[
            dict(text=f"<b>{fmt_num(total)}</b>", x=0.5, y=0.54, xref="paper", yref="paper",
                 showarrow=False, font=dict(size=24, color="#263846", family="Rubik")),
            dict(text="NEGÓCIOS", x=0.5, y=0.44, xref="paper", yref="paper",
                 showarrow=False, font=dict(size=10, color="#64748b", family="Inter")),
        ],
    )
    return fig

def build_team_legend(detalhe, cf):
    filt     = _filter_detalhe(detalhe, cf)
    interno  = sum(1 for d in filt if d.get("tipo_venda") == "interno")
    indicado = sum(1 for d in filt if d.get("tipo_venda") == "indicado")
    total    = (interno + indicado) or 1
    def item(label, color, n):
        return html.Div(className="ct-teamleg-item", children=[
            html.Span(className="ct-leg-dot", style={"backgroundColor": color}),
            html.Span(label, className="ct-teamleg-name"),
            html.Span(f"{n/total*100:.0f}%", className="ct-teamleg-pct"),
        ])
    return [item("Interno", COR_INTERNO, interno), item("Indicado", COR_INDICADO, indicado)]


# ── Tabelas ───────────────────────────────────────────────────────────────────
def _filter_detalhe(detalhe, cf, dims=("vendedor", "tipo_venda", "tipo_contrato")):
    cf  = cf or {}
    v   = cf.get("vendedor")      if "vendedor"      in dims else None
    t   = cf.get("tipo_venda")    if "tipo_venda"    in dims else None
    tc  = cf.get("tipo_contrato") if "tipo_contrato" in dims else None
    out = []
    for d in detalhe or []:
        if v  and d.get("vendedor")         != v:  continue
        if t  and d.get("tipo_venda")       != t:  continue
        if tc and d.get("tipo_de_contrato") != tc: continue
        out.append(d)
    return out

def aggregate_vendedores(detalhe):
    agg = {}
    for d in detalhe or []:
        resp = d.get("vendedor") or "(Sem responsável)"
        a    = agg.get(resp)
        if a is None:
            a = agg[resp] = {"responsavel": resp, "propria_qtd": 0, "propria_valor": 0.0,
                             "indicada_qtd": 0, "indicada_valor": 0.0,
                             "total_qtd": 0,   "total_valor": 0.0}
        val = _f(d.get("valor"))
        a["total_qtd"]   += 1
        a["total_valor"] += val
        if d.get("tipo_venda") == "interno":
            a["propria_qtd"]   += 1
            a["propria_valor"] += val
        else:
            a["indicada_qtd"]   += 1
            a["indicada_valor"] += val
    rows = list(agg.values())
    rows.sort(key=lambda r: (-r["total_valor"], r["responsavel"]))
    return rows

def build_vendedores_table(vendedores, cf):
    if not vendedores:
        return html.P("Sem dados para o período.", className="rt-empty")
    sel  = (cf or {}).get("vendedor")
    head = html.Thead(html.Tr([
        html.Th("Vendedor",         style={"textAlign": "left"}),
        html.Th("Qtd Internas",     style={"textAlign": "right"}),
        html.Th("Valor Internas",   style={"textAlign": "right"}),
        html.Th("Qtd Indicadas",    style={"textAlign": "right"}),
        html.Th("Valor Indicadas",  style={"textAlign": "right"}),
        html.Th("Total",            style={"textAlign": "right"}),
    ]))
    body = []
    for r in vendedores:
        resp   = r["responsavel"]
        active = (resp == sel)
        body.append(html.Tr(
            id={"type": "ct-vend-row", "index": _enc(resp)}, n_clicks=0,
            className="rt-vend-row" + (" rt-vend-active" if active else ""),
            children=[
                html.Td(resp,                       style={"textAlign": "left"}),
                html.Td(fmt_num(r["propria_qtd"]),  style={"textAlign": "right"}),
                html.Td(fmt_brl(r["propria_valor"]),style={"textAlign": "right"}),
                html.Td(fmt_num(r["indicada_qtd"]), style={"textAlign": "right"}),
                html.Td(fmt_brl(r["indicada_valor"]),style={"textAlign": "right"}),
                html.Td(fmt_brl(r["total_valor"]),  style={"textAlign": "right", "fontWeight": 700}),
            ],
        ))
    return html.Table([head, html.Tbody(body)], className="rt-table rt-table-click")

def build_contratos_table(detalhe, cf):
    filt = _filter_detalhe(detalhe, cf, dims=("vendedor", "tipo_venda"))
    if not filt:
        return html.P("Sem dados para o filtro atual.", className="rt-empty")
    agg = {}
    for d in filt:
        tipo = d.get("tipo_de_contrato") or "(Sem tipo)"
        a    = agg.setdefault(tipo, {"qtd": 0, "valor": 0.0})
        a["qtd"]   += 1
        a["valor"] += _f(d.get("valor"))
    linhas   = sorted(agg.items(), key=lambda kv: (-kv[1]["valor"], kv[0]))
    sel_tc   = (cf or {}).get("tipo_contrato")
    head     = html.Thead(html.Tr([
        html.Th("Tipo de Contrato", style={"textAlign": "left"}),
        html.Th("Qtd",              style={"textAlign": "right"}),
        html.Th("Valor Total",      style={"textAlign": "right"}),
    ]))
    body = [html.Tr(
        id={"type": "ct-tipo-row", "index": _enc(tipo)}, n_clicks=0,
        className="rt-tipo-row" + (" rt-tipo-active" if tipo == sel_tc else ""),
        children=[
            html.Td(tipo,              style={"textAlign": "left"}),
            html.Td(fmt_num(v["qtd"]), style={"textAlign": "right"}),
            html.Td(fmt_brl(v["valor"]),style={"textAlign": "right"}),
        ],
    ) for tipo, v in linhas]
    return html.Table([head, html.Tbody(body)], className="rt-table rt-table-click")

def build_detalhamento_data(detalhe, cf):
    rows = []
    for d in _filter_detalhe(detalhe, cf):
        bid  = d.get("bitrix_id")
        link = d.get("link_deal")
        rows.append({
            "id":               f"[{bid}]({link})" if (bid is not None and link) else (str(bid) if bid is not None else "—"),
            "cliente":          d.get("cliente")          or "—",
            "vendedor":         d.get("vendedor")         or "—",
            "tipo_venda":       TIPO_VENDA_LABEL.get(d.get("tipo_venda"), "—"),
            "etapa":            d.get("etapa")            or "—",
            "tipo_de_contrato": d.get("tipo_de_contrato") or "—",
            "valor":            fmt_brl(d.get("valor")),
        })
    return rows


# ── Dados fictícios ───────────────────────────────────────────────────────────
# Cenário: 5 vendedores da ContaFarma, mix de contratos e tipos de venda.
# Nenhum dado real — somente para demonstração visual e validação de layout.

def _det(bid, cliente, vendedor, tipo_venda, etapa, tipo_contrato, valor):
    return {"bitrix_id": bid, "link_deal": "#", "cliente": cliente,
            "vendedor": vendedor, "tipo_venda": tipo_venda, "etapa": etapa,
            "tipo_de_contrato": tipo_contrato, "valor": float(valor)}

_D = _det   # alias curto

_DETALHE_FECHADAS = [
    # Ana Paula Costa — 8 interno + 3 indicado
    _D(1001, "Drogaria Bem Estar",          "Ana Paula Costa", "interno",  "Concluídos",           "MEI",              980),
    _D(1002, "Farmácia São João",           "Ana Paula Costa", "interno",  "Concluídos",           "Simples Nacional", 1490),
    _D(1003, "Clínica Médica Norte",        "Ana Paula Costa", "interno",  "Conferência",          "LTDA",             2200),
    _D(1004, "Dr. Anderson Silva",          "Ana Paula Costa", "interno",  "Concluídos",           "Autônomo",         750),
    _D(1005, "Drogaria Central",            "Ana Paula Costa", "interno",  "Concluídos",           "MEI",              980),
    _D(1006, "Farmácia Popular Bairro",     "Ana Paula Costa", "interno",  "Delegação de Tarefas", "Simples Nacional", 1490),
    _D(1007, "Instituto Odontológico",      "Ana Paula Costa", "interno",  "Boas Vindas",          "LTDA",             2200),
    _D(1008, "Laboratório Diagnósticos",    "Ana Paula Costa", "interno",  "Conferência",          "MEI",              980),
    _D(1009, "Clínica Pediátrica",          "Ana Paula Costa", "indicado", "Concluídos",           "LTDA",             2200),
    _D(1010, "Drogaria Saúde Total",        "Ana Paula Costa", "indicado", "Concluídos",           "Simples Nacional", 1490),
    _D(1011, "Centro Médico Vida",          "Ana Paula Costa", "indicado", "Conferência",          "MEI",              980),
    # Carlos Henrique Lima — 6 interno + 5 indicado
    _D(1012, "Ortopedia Dr. Melo",          "Carlos Henrique Lima", "interno",  "Concluídos",           "LTDA",             2200),
    _D(1013, "Clínica Dermatológica",       "Carlos Henrique Lima", "interno",  "Concluídos",           "EIRELI",           1890),
    _D(1014, "Drogaria Horizonte",          "Carlos Henrique Lima", "interno",  "Conferência",          "MEI",              980),
    _D(1015, "Fisioterapia Bem-Estar",      "Carlos Henrique Lima", "interno",  "Concluídos",           "Autônomo",         750),
    _D(1016, "Nutrição Dra. Clara",         "Carlos Henrique Lima", "interno",  "Delegação de Tarefas", "Autônomo",         750),
    _D(1017, "Farmácia Magistral",          "Carlos Henrique Lima", "interno",  "Constituição Empresa", "Simples Nacional", 1490),
    _D(1018, "Psicologia Dra. Beatriz",     "Carlos Henrique Lima", "indicado", "Concluídos",           "Autônomo",         750),
    _D(1019, "Ótica Visão Clara",           "Carlos Henrique Lima", "indicado", "Concluídos",           "MEI",              980),
    _D(1020, "Drogaria Familiar Souza",     "Carlos Henrique Lima", "indicado", "Conferência",          "Simples Nacional", 1490),
    _D(1021, "Clínica Médica Sul",          "Carlos Henrique Lima", "indicado", "Concluídos",           "LTDA",             2200),
    _D(1022, "Farmácia Estrela",            "Carlos Henrique Lima", "indicado", "Boas Vindas",          "MEI",              980),
    # Fernanda Souza — 5 interno + 2 indicado
    _D(1023, "Consultório Dr. Lima",        "Fernanda Souza", "interno",  "Concluídos",           "Autônomo",         750),
    _D(1024, "Drogaria Vitória",            "Fernanda Souza", "interno",  "Concluídos",           "MEI",              980),
    _D(1025, "Clínica Odontológica",        "Fernanda Souza", "interno",  "Conferência",          "EIRELI",           1890),
    _D(1026, "Farmácia São Lucas",          "Fernanda Souza", "interno",  "Concluídos",           "Simples Nacional", 1490),
    _D(1027, "Reabilitação Dr. Pires",      "Fernanda Souza", "interno",  "Delegação de Tarefas", "LTDA",             2200),
    _D(1028, "Clínica Cardio Norte",        "Fernanda Souza", "indicado", "Concluídos",           "LTDA",             2200),
    _D(1029, "Farmácia Novo Horizonte",     "Fernanda Souza", "indicado", "Concluídos",           "MEI",              980),
    # Rafael Mendes — 3 interno + 4 indicado
    _D(1030, "Instituto de Nutrição",       "Rafael Mendes", "interno",  "Concluídos",           "Autônomo",         750),
    _D(1031, "Drogaria Saúde em Dia",       "Rafael Mendes", "interno",  "Conferência",          "MEI",              980),
    _D(1032, "Clínica Geriátrica",          "Rafael Mendes", "interno",  "Constituição Empresa", "LTDA",             2200),
    _D(1033, "Óptica Mais Visão",           "Rafael Mendes", "indicado", "Concluídos",           "MEI",              980),
    _D(1034, "Farmácia Cuidar Bem",         "Rafael Mendes", "indicado", "Concluídos",           "Simples Nacional", 1490),
    _D(1035, "Centro de Estética Dra. Ana", "Rafael Mendes", "indicado", "Conferência",          "Autônomo",         750),
    _D(1036, "Clínica Reumatológica",       "Rafael Mendes", "indicado", "Boas Vindas",          "EIRELI",           1890),
    # Juliana Rocha — 2 interno + 1 indicado
    _D(1037, "Drogaria Esperança",          "Juliana Rocha", "interno",  "Concluídos",           "MEI",              980),
    _D(1038, "Farmácia Santa Cruz",         "Juliana Rocha", "interno",  "Conferência",          "Simples Nacional", 1490),
    _D(1039, "Clínica Ortopédica Sul",      "Juliana Rocha", "indicado", "Concluídos",           "LTDA",             2200),
]

_DETALHE_NEGOCIACAO = [
    # Ana Paula Costa — 3 interno + 2 indicado
    _D(2001, "Drogaria Alfa",               "Ana Paula Costa", "interno",  "Gerar Contrato", "MEI",              980),
    _D(2002, "Clínica Médica Oeste",        "Ana Paula Costa", "interno",  "Click Sign",     "LTDA",             2200),
    _D(2003, "Farmácia do Povo",            "Ana Paula Costa", "interno",  "Orçamento",      "Simples Nacional", 1490),
    _D(2004, "Drogaria Prime",              "Ana Paula Costa", "indicado", "Gerar Proposta", "MEI",              980),
    _D(2005, "Clínica Especializada",       "Ana Paula Costa", "indicado", "Gerar Contrato", "EIRELI",           1890),
    # Carlos Henrique Lima — 2 interno + 3 indicado
    _D(2006, "Instituto Saúde e Vida",      "Carlos Henrique Lima", "interno",  "Click Sign",     "LTDA",             2200),
    _D(2007, "Drogaria Modelo",             "Carlos Henrique Lima", "interno",  "Gerar Proposta", "Simples Nacional", 1490),
    _D(2008, "Farmácia Confiança",          "Carlos Henrique Lima", "indicado", "Orçamento",      "MEI",              980),
    _D(2009, "Clínica Integrada",           "Carlos Henrique Lima", "indicado", "Gerar Contrato", "LTDA",             2200),
    _D(2010, "Dr. Felipe Rocha",            "Carlos Henrique Lima", "indicado", "Click Sign",     "Autônomo",         750),
    # Fernanda Souza — 2 interno + 1 indicado
    _D(2011, "Farmácia Bela Saúde",         "Fernanda Souza", "interno",  "Gerar Contrato", "Simples Nacional", 1490),
    _D(2012, "Drogaria Sucesso",            "Fernanda Souza", "interno",  "Orçamento",      "MEI",              980),
    _D(2013, "Clínica Endocrinológica",     "Fernanda Souza", "indicado", "Gerar Proposta", "LTDA",             2200),
    # Rafael Mendes — 1 interno + 2 indicado
    _D(2014, "Drogaria Premium",            "Rafael Mendes", "interno",  "Click Sign",     "EIRELI",           1890),
    _D(2015, "Clínica Derma Plus",          "Rafael Mendes", "indicado", "Gerar Contrato", "LTDA",             2200),
    _D(2016, "Farmácia Alternativa",        "Rafael Mendes", "indicado", "Orçamento",      "MEI",              980),
    # Juliana Rocha — 1 interno + 1 indicado
    _D(2017, "Drogaria Excelência",         "Juliana Rocha", "interno",  "Gerar Proposta", "Simples Nacional", 1490),
    _D(2018, "Clínica Viver Bem",           "Juliana Rocha", "indicado", "Gerar Contrato", "MEI",              980),
]

_DADOS = {"fechadas": _DETALHE_FECHADAS, "negociacao": _DETALHE_NEGOCIACAO}


def _build_kpis(detalhe):
    interno  = [d for d in detalhe if d["tipo_venda"] == "interno"]
    indicado = [d for d in detalhe if d["tipo_venda"] == "indicado"]
    return {
        "total_qtd":     len(detalhe),
        "total_valor":   sum(d["valor"] for d in detalhe),
        "propria_qtd":   len(interno),
        "propria_valor": sum(d["valor"] for d in interno),
        "indicada_qtd":  len(indicado),
        "indicada_valor":sum(d["valor"] for d in indicado),
    }

def _build_vendedores(detalhe):
    agg = {}
    for d in detalhe:
        v = d["vendedor"]
        a = agg.setdefault(v, {"responsavel": v, "propria_qtd": 0, "propria_valor": 0.0,
                                "indicada_qtd": 0, "indicada_valor": 0.0,
                                "total_qtd": 0,    "total_valor": 0.0})
        a["total_qtd"]   += 1
        a["total_valor"] += d["valor"]
        if d["tipo_venda"] == "interno":
            a["propria_qtd"]   += 1
            a["propria_valor"] += d["valor"]
        else:
            a["indicada_qtd"]   += 1
            a["indicada_valor"] += d["valor"]
    return list(agg.values())

def get_aba_demo(aba="fechadas", data_de=None, data_ate=None):
    detalhe = _DADOS.get(aba, _DETALHE_FECHADAS)
    return {
        "kpis":       _build_kpis(detalhe),
        "vendedores": _build_vendedores(detalhe),
        "indicadas":  [],
        "detalhe":    detalhe,
    }


# ── App (porta 8052 — sem prefixo de produção) ────────────────────────────────
app = Dash(
    __name__,
    title="ContaFarma — DEMO",
    external_stylesheets=[
        "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css",
    ],
    suppress_callback_exceptions=True,
)
server = app.server

app.layout = html.Div(className="rt-app", children=[
    dcc.Location(id="url"),
    dcc.Store(id="ct-aba",  data=ABA_DEFAULT),
    dcc.Store(id="ct-data", data={"vendedores": [], "indicadas": {}, "detalhe": []}),
    dcc.Store(id="cf-store", data=dict(CF_EMPTY)),

    html.Div(className="rt-header", children=[
        html.Div(className="rt-brand", children=[
            "ContaFarma",
            html.Span(" · DEMO", style={"fontSize": "13px", "color": "#f6ad55",
                                        "fontWeight": 400, "marginLeft": "8px"}),
        ]),
        html.Div(className="rt-tabs", children=[
            html.Button(rotulo, className="rt-tab" + (" rt-tab-active" if chave == ABA_DEFAULT else ""),
                        id={"type": "ct-tab", "index": chave})
            for chave, rotulo in ABAS
        ]),
        data_filter_bar(),
    ]),

    html.Div(id="error-banner"),
    kpi_row(),

    html.Div(className="ct-donut-row", children=[
        html.Div(className="rt-card ct-donut-card", children=[
            html.Div(className="rt-card-head", children=[
                html.I(className="fas fa-chart-pie"),
                html.Span("Distribuição por Vendedor (Interno × Indicado)"),
                html.Div(id="cf-chip-wrap", className="ct-chip-wrap", style={"display": "none"}, children=[
                    html.I(className="fas fa-filter"),
                    html.Span(id="cf-chip-text", className="ct-chip-text"),
                    html.Button("×", id="cf-chip-clear", className="ct-chip-x", title="Limpar filtro"),
                ]),
            ]),
            html.Div(className="rt-card-body", children=[
                html.Div(className="ct-donut", children=[
                    html.Div(className="ct-donut-left", children=[
                        html.Div(className="ct-donut-circle", children=dcc.Graph(
                            id="ct-donut", figure=empty_fig("Carregando…"),
                            config={"displayModeBar": False},
                            style={"height": "360px", "width": "360px"})),
                    ]),
                    html.Div(className="ct-donut-right", children=[
                        html.Div(id="ct-donut-legend", className="ct-donut-legend"),
                    ]),
                ]),
            ]),
        ]),
        html.Div(className="rt-card ct-team-card", children=[
            html.Div(className="rt-card-head", children=[
                html.I(className="fas fa-users"),
                html.Span("Equipe — Interno × Indicado"),
            ]),
            html.Div(className="rt-card-body ct-team-body", children=[
                html.Div(className="ct-team-circle", children=dcc.Graph(
                    id="ct-donut2", figure=empty_fig("Carregando…"),
                    config={"displayModeBar": False, "staticPlot": True},
                    style={"height": "300px", "width": "300px"})),
                html.Div(id="ct-donut2-legend", className="ct-teamleg"),
            ]),
        ]),
    ]),

    html.Div(className="ct-two-col", children=[
        card("Negócios por Vendedor", icon="fa-user-tie", extra_class="ct-col-vend", children=[
            html.Div(id="ct-vendedores"),
        ]),
        card("Negócios por Tipo de Contrato", icon="fa-file-signature", extra_class="ct-col-contrato", children=[
            html.Div(id="ct-contratos"),
        ]),
    ]),

    card("Detalhamento", icon="fa-table-list", extra_class="rt-col-full", children=[
        dash_table.DataTable(
            id="tbl-detalhamento", columns=DETALHE_COLS, data=[],
            markdown_options={"link_target": "_blank"},
            cell_selectable=False, page_size=25, sort_action="native",
            style_as_list_view=True, style_table={"overflowX": "auto"},
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


# ── Callbacks (idênticos ao app.py, exceto load_data que usa get_aba_demo) ───
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

@callback(
    Output("kpi-total-valor",   "children"),
    Output("kpi-total-qtd",     "children"),
    Output("kpi-propria-valor", "children"),
    Output("kpi-propria-qtd",   "children"),
    Output("kpi-indicada-valor","children"),
    Output("kpi-indicada-qtd",  "children"),
    Output("kpi-ticket",        "children"),
    Output("kpi-ticket-sub",    "children"),
    Output("ct-data",           "data"),
    Output("cf-store",          "data"),
    Output("error-banner",      "children"),
    Input("ct-aba",        "data"),
    Input("ct-data-de",    "date"),
    Input("ct-data-ate",   "date"),
    Input("btn-refresh",   "n_clicks"),
)
def load_data(aba, data_de, data_ate, _n):
    cf_reset = dict(CF_EMPTY)
    d   = get_aba_demo(aba or ABA_DEFAULT, data_de, data_ate)
    k   = d["kpis"]
    total_qtd   = _i(k.get("total_qtd"))
    total_valor = _f(k.get("total_valor"))
    ticket      = (total_valor / total_qtd) if total_qtd else 0.0

    vendedores = [{
        "responsavel":    r["responsavel"],
        "propria_qtd":    _i(r["propria_qtd"]),
        "propria_valor":  _f(r["propria_valor"]),
        "indicada_qtd":   _i(r["indicada_qtd"]),
        "indicada_valor": _f(r["indicada_valor"]),
        "total_qtd":      _i(r["total_qtd"]),
        "total_valor":    _f(r["total_valor"]),
    } for r in d["vendedores"]]

    detalhe = [{
        "bitrix_id":        r.get("bitrix_id"),
        "link_deal":        r.get("link_deal"),
        "cliente":          r.get("cliente"),
        "vendedor":         r.get("vendedor"),
        "tipo_venda":       r.get("tipo_venda"),
        "etapa":            r.get("etapa"),
        "tipo_de_contrato": r.get("tipo_de_contrato"),
        "valor":            _f(r.get("valor")),
    } for r in d["detalhe"]]

    store = {"vendedores": vendedores, "indicadas": {}, "detalhe": detalhe}
    return (
        fmt_brl(total_valor),        f"{fmt_num(total_qtd)} negócios",
        fmt_brl(k.get("propria_valor")),  f"{fmt_num(k.get('propria_qtd'))} negócios",
        fmt_brl(k.get("indicada_valor")), f"{fmt_num(k.get('indicada_qtd'))} negócios",
        fmt_brl(ticket),             "valor médio por negócio",
        store, cf_reset, None,
    )

@callback(
    Output("ct-vendedores",      "children"),
    Output("ct-contratos",       "children"),
    Output("tbl-detalhamento",   "data"),
    Output("ct-donut",           "figure"),
    Output("ct-donut-legend",    "children"),
    Output("ct-donut2",          "figure"),
    Output("ct-donut2-legend",   "children"),
    Output("cf-chip-text",       "children"),
    Output("cf-chip-wrap",       "style"),
    Input("ct-data",   "data"),
    Input("cf-store",  "data"),
)
def render_views(data, cf):
    data    = data or {}
    cf      = cf   or dict(CF_EMPTY)
    detalhe = data.get("detalhe", [])
    det_vend  = _filter_detalhe(detalhe, cf, dims=("tipo_contrato",))
    vend_agg  = aggregate_vendedores(det_vend)
    vend_tbl  = build_vendedores_table(vend_agg, cf)
    contr_tbl = build_contratos_table(detalhe, cf)
    det_data  = build_detalhamento_data(detalhe, cf)
    donut     = build_donut(vend_agg, cf)
    legend    = build_donut_legend(vend_agg, cf)
    team_donut  = build_team_donut(detalhe, cf)
    team_legend = build_team_legend(detalhe, cf)
    if cf.get("vendedor"):
        tip = cf.get("tipo_venda")
        txt = f" {cf['vendedor']}" + (f" · {TIPO_VENDA_LABEL.get(tip, '')}" if tip else "")
        chip_style = {"display": "inline-flex"}
    elif cf.get("tipo_contrato"):
        txt = f" {cf['tipo_contrato']}"
        chip_style = {"display": "inline-flex"}
    else:
        txt = ""; chip_style = {"display": "none"}
    return (vend_tbl, contr_tbl, det_data, donut, legend,
            team_donut, team_legend, txt, chip_style)

def _cf_toggle(cur, vendedor, tipo):
    cur = cur or {}
    if cur.get("vendedor") == vendedor and cur.get("tipo_venda") == tipo:
        return dict(CF_EMPTY)
    return {"vendedor": vendedor, "tipo_venda": tipo, "tipo_contrato": None}

def _cf_toggle_tipo(cur, tipo_contrato):
    cur = cur or {}
    if cur.get("tipo_contrato") == tipo_contrato:
        return dict(CF_EMPTY)
    return {"vendedor": None, "tipo_venda": None, "tipo_contrato": tipo_contrato}

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
    tipo     = cd[1] if len(cd) >= 2 else None
    return _cf_toggle(cur, vendedor, tipo), None

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

@callback(
    Output("cf-store", "data", allow_duplicate=True),
    Input({"type": "ct-tipo-row", "index": ALL}, "n_clicks"),
    State("cf-store", "data"),
    prevent_initial_call=True,
)
def cf_from_tipo(_n, cur):
    if not ctx.triggered or not ctx.triggered[0]["value"]:
        return no_update
    return _cf_toggle_tipo(cur, _dec(ctx.triggered_id["index"]))

@callback(
    Output("cf-store", "data", allow_duplicate=True),
    Input("cf-chip-clear", "n_clicks"),
    prevent_initial_call=True,
)
def cf_clear(_n):
    return dict(CF_EMPTY)


if __name__ == "__main__":
    app.run(host="127.0.0.1", port=8052, debug=True)
