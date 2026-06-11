"""
DEMO SERVER — uso interno de desenvolvimento APENAS. NUNCA vai para produção.

Sobe o MESMO app (app.py, intocado) na porta 8051 com DADOS FICTÍCIOS, para
testar a interatividade (cross-filter de etapa/status/donut) sem precisar do
túnel SSH / banco real.

Como funciona: faz monkeypatch de `queries.get_diagnostico` ANTES de importar o
app. O app.py não tem nenhuma referência a demo — toda a lógica fictícia vive aqui.

Rodar:   python demo_server.py    →    http://localhost:8051
O app.py principal continua na 8050 conectado ao banco real, sem conflito.
"""

import queries

# ── Base de negócios fictícios ───────────────────────────────────────────────
# status é derivado da etapa para espelhar a lógica real (STATUS_CASE).
ETAPAS_STATUS = {
    "01 - Coleta de documentos": "Em Diagnóstico",
    "02 - Triagem":              "Em Diagnóstico",
    "03 - Relatório preliminar": "Em Diagnóstico",
    "07 - Proposta":             "Em Diagnóstico",
    "06 - Suspenso":             "Suspenso",
    "13 - Sem valor de crédito": "Sem Oportunidade",
    "15 - Sem interesse":        "Sem Oportunidade",
    "17 - Perdidos":             "Sem Oportunidade",
}
# 12 produtos → donut com Top 9 + "Outros" (3 agrupados)
PRODUTOS = [
    "ICMS ST", "PIS E COFINS - NÃO CUMULATIVIDADE", "PIS E COFINS - EXCLUSÃO ICMS",
    "Energia - Crédito", "Recuperação INSS", "ICMS Energia", "PIS/COFINS Insumos",
    "IRPJ/CSLL", "Simples Nacional", "ICMS Importação", "ISS Retido", "(Sem Produto)",
]
_COUNT_POR_ETAPA = [8, 6, 5, 4, 7, 5, 4, 3]  # total ~42 negócios

DEALS = []
_bid = 100000
for _i, (_etapa, _status) in enumerate(ETAPAS_STATUS.items()):
    for _j in range(_COUNT_POR_ETAPA[_i]):
        _bid += 1
        DEALS.append({
            "bitrix_id":      _bid,
            "etapa_ordenada": _etapa,
            "status":         _status,
            "produto":        PRODUTOS[(_i * 3 + _j) % len(PRODUTOS)],
            "valor":          (_i + 1) * 1000 + _j * 250,
            "cliente":        f"Cliente Demo {_bid}",
        })

_STATUS_ORDER = ["Suspenso", "Sem Oportunidade", "Em Diagnóstico", "Com Oportunidade"]

# Top 9 produtos por contagem (mesma regra do donut) → "Outros" é o complemento.
_PROD_COUNTS = {}
for _d in DEALS:
    _PROD_COUNTS[_d["produto"]] = _PROD_COUNTS.get(_d["produto"], 0) + 1
_TOP9 = {p for p, _ in sorted(_PROD_COUNTS.items(), key=lambda kv: -kv[1])[:9]}


# ── Helpers de agregação (espelham a semântica das queries reais) ────────────
def _keep(deal, filtro, skip_tipo):
    """Aplica o filtro ativo, MENOS quando este visual é a fonte (skip_tipo)."""
    if not filtro or not filtro.get("valor"):
        return True
    tipo, valor = filtro.get("tipo"), filtro.get("valor")
    if tipo == skip_tipo:
        return True
    if tipo == "etapa":
        return deal["etapa_ordenada"] == valor
    if tipo == "status":
        return deal["status"] == valor
    if tipo == "produto":
        if valor == "Outros":        # complemento do Top 9 (igual à query real)
            return deal["produto"] not in _TOP9
        return deal["produto"] == valor
    return True


def _agg(deals, key):
    acc = {}
    for d in deals:
        a = acc.setdefault(d[key], {"total": 0, "valor_soma": 0})
        a["total"] += 1
        a["valor_soma"] += d["valor"]
    return acc


def _fake_get_diagnostico(parceiro=None, filtro=None):
    # A) etapas (fonte = etapa)
    etapa_acc = _agg([d for d in DEALS if _keep(d, filtro, "etapa")], "etapa_ordenada")
    etapa_table = [{"etapa_ordenada": k, **v} for k, v in sorted(etapa_acc.items())]

    # B) status (fonte = status)
    status_acc = _agg([d for d in DEALS if _keep(d, filtro, "status")], "status")
    status_table = [{"status": s, **status_acc[s]} for s in _STATUS_ORDER if s in status_acc]

    # C) KPIs (aplica tudo)
    kpi_deals = [d for d in DEALS if _keep(d, filtro, None)]
    kpis = {"total": len(kpi_deals), "valor_soma": sum(d["valor"] for d in kpi_deals)}

    # D) donut (fonte = produto): Top 9 + Outros
    prod_acc = _agg([d for d in DEALS if _keep(d, filtro, "produto")], "produto")
    ranked = sorted(prod_acc.items(), key=lambda kv: -kv[1]["total"])
    donut = [{"produto": p, "total": v["total"]} for p, v in ranked[:9]]
    outros = sum(v["total"] for _, v in ranked[9:])
    if outros:
        donut.append({"produto": "Outros", "total": outros})

    # E) detalhe (aplica tudo)
    detalhe = [{
        "bitrix_id":   d["bitrix_id"],
        "cliente":     d["cliente"],
        "oportunidade": d["produto"],
        "etapa":       d["etapa_ordenada"],
        "observacoes": "—",
        "valor":       d["valor"],
        "link_deal":   f'https://gnapp.bitrix24.com.br/crm/deal/details/{d["bitrix_id"]}/',
    } for d in [x for x in DEALS if _keep(x, filtro, None)]]

    return {
        "etapa_table":  etapa_table,
        "status_table": status_table,
        "kpis":         kpis,
        "donut":        donut,
        "detalhe":      detalhe,
    }


# Monkeypatch ANTES de importar o app → load_data usará os dados fictícios.
queries.get_diagnostico = _fake_get_diagnostico

import app  # noqa: E402  (precisa vir depois do patch)

if __name__ == "__main__":
    print("DEMO (dados fictícios) em http://localhost:8051  —  app.py real segue na 8050")
    app.app.run(host="127.0.0.1", port=8051, debug=False)
