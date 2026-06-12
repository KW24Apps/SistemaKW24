"""
DEMO SERVER — uso interno de desenvolvimento APENAS. NUNCA vai para produção.

Sobe o MESMO app (app.py, intocado) na porta 8051 com DADOS FICTÍCIOS, para
testar a interatividade (cross-filter + troca de funis) sem o túnel SSH / banco.

Monkeypatch de queries.get_funil ANTES de importar o app. app.py sem referência
a demo — toda a lógica fictícia vive aqui, e cobre os TRÊS funis.

Rodar:   python demo_server.py    →    http://localhost:8051
"""

import queries

# ── Etapas (label ordenado → status) por funil ───────────────────────────────
FUNIS_ETAPAS = {
    "diagnostico": {
        "01 - Coleta de documentos": "Em Diagnóstico",
        "02 - Triagem":              "Em Diagnóstico",
        "03 - Relatório preliminar": "Em Diagnóstico",
        "07 - Proposta":             "Em Diagnóstico",
        "06 - Suspenso":             "Suspenso",
        "13 - Sem valor de crédito": "Sem Oportunidade",
        "15 - Sem interesse":        "Sem Oportunidade",
        "17 - Perdidos":             "Sem Oportunidade",
    },
    "operacional": {
        "01 - Coleta de Documentos (Parceiro)": "Com Oportunidade",
        "02 - Triagem (CheckList Operação)":    "Com Oportunidade",
        "03 - Execução Operação":               "Com Oportunidade",
        "07 - Aguardando Contencioso":          "Com Oportunidade",
        "05 - Suspenso":                        "Suspenso",
        "09 - Sem valor de crédito":            "Sem Oportunidade",
        "13 - Perdidos":                        "Sem Oportunidade",
    },
    "retificacao": {
        "01 - Distribuição de trabalhos":       "Com Oportunidade",
        "03 - Retificação de Declarações":      "Com Oportunidade",
        "07 - Liberação de Crédito/Liquidação": "Com Oportunidade",
        "14 - Concluido":                       "Com Oportunidade",
        "13 - Suspenso":                        "Suspenso",
        "15 - Sem valor de crédito":            "Sem Oportunidade",
        "17 - Perdidos":                        "Sem Oportunidade",
    },
}
# 12 produtos → donut com Top 9 + "Outros"
PRODUTOS = [
    "ICMS ST", "PIS E COFINS - NÃO CUMULATIVIDADE", "PIS E COFINS - EXCLUSÃO ICMS",
    "Energia - Crédito", "Recuperação INSS", "ICMS Energia", "PIS/COFINS Insumos",
    "IRPJ/CSLL", "Simples Nacional", "ICMS Importação", "ISS Retido", "(Sem Produto)",
]

# Gera negócios fictícios para cada funil
DEALS = []
_bid = 100000
for _funil, _etapas in FUNIS_ETAPAS.items():
    for _i, (_etapa, _status) in enumerate(_etapas.items()):
        _n = 3 + (_i % 5)  # 3..7 negócios por etapa
        for _j in range(_n):
            _bid += 1
            DEALS.append({
                "funil":          _funil,
                "bitrix_id":      _bid,
                "etapa_ordenada": _etapa,
                "status":         _status,
                "produto":        PRODUTOS[(_i * 3 + _j) % len(PRODUTOS)],
                "valor":          (_i + 1) * 1000 + _j * 250,
                "cliente":        f"Cliente Demo {_bid}",
                # criado_em fictício espalhado em 2026-01..2026-06 (p/ testar o filtro de data)
                "criado":         f"2026-{(_bid % 6) + 1:02d}-{(_bid % 27) + 1:02d}",
            })

_STATUS_ORDER = ["Suspenso", "Sem Oportunidade", "Em Diagnóstico", "Com Oportunidade"]


def _top9_of(deals):
    counts = {}
    for d in deals:
        counts[d["produto"]] = counts.get(d["produto"], 0) + 1
    return {p for p, _ in sorted(counts.items(), key=lambda kv: -kv[1])[:9]}


# Normaliza a etapa Sem-Op (pipeline-agnostic) — espelha o ETAPA_SEM_OP_CASE do
# queries.py: na aba 'Sem Oportunidade' os três funis se combinam, então a MESMA
# etapa precisa do MESMO rótulo (o prefixo "NN - " de cada funil é descartado).
_SEM_OP_LABEL = {
    "sem interesse":            "1 - Sem interesse",
    "sem valor de crédito":     "2 - Sem valor de crédito",
    "documentos incompletos":   "3 - Documentos incompletos",
    "fechado com outra empresa": "4 - Fechado c/ outra empresa",
    "lixeira":                  "5 - Lixeira",
    "perdidos":                 "6 - Perdidos",
}


def _norm_semop(etapa_ordenada):
    nome = etapa_ordenada.split(" - ", 1)[-1].strip()
    return _SEM_OP_LABEL.get(nome.lower(), "9 - " + nome)


def _keep(deal, filtro, skip_tipo, top9):
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
        if valor == "Outros":
            return deal["produto"] not in top9
        return deal["produto"] == valor
    return True


def _agg(deals, key):
    acc = {}
    for d in deals:
        a = acc.setdefault(d[key], {"total": 0, "valor_soma": 0})
        a["total"] += 1
        a["valor_soma"] += d["valor"]
    return acc


def _fake_get_funil(funil="diagnostico", parceiro=None, filtro=None, data_de=None, data_ate=None, modo="normal"):
    def _in_range(d):
        # filtro de data GLOBAL — só quando ambas preenchidas; combina com o cross-filter
        return not (data_de and data_ate) or (data_de <= d["criado"] <= data_ate)

    if modo == "sem_op":
        # Aba 'Sem Oportunidade': SEM filtro de funil — combina os TRÊS pipelines;
        # só registros 'Sem Oportunidade'. Etapa normalizada p/ combinar entre funis.
        base = [{**d, "etapa_ordenada": _norm_semop(d["etapa_ordenada"])}
                for d in DEALS if d["status"] == "Sem Oportunidade" and _in_range(d)]
    else:
        # Funis: filtra o pipeline ativo e EXCLUI 'Sem Oportunidade'.
        base = [d for d in DEALS
                if d["funil"] == funil and d["status"] != "Sem Oportunidade" and _in_range(d)]
    top9 = _top9_of(base)

    def kept(skip):
        return [d for d in base if _keep(d, filtro, skip, top9)]

    etapa_acc = _agg(kept("etapa"), "etapa_ordenada")
    etapa_table = [{"etapa_ordenada": k, **v} for k, v in sorted(etapa_acc.items())]

    status_acc = _agg(kept("status"), "status")
    status_table = [{"status": s, **status_acc[s]} for s in _STATUS_ORDER if s in status_acc]

    kpi_deals = kept(None)
    kpis = {"total": len(kpi_deals), "valor_soma": sum(d["valor"] for d in kpi_deals)}

    prod_acc = _agg(kept("produto"), "produto")
    ranked = sorted(prod_acc.items(), key=lambda kv: -kv[1]["total"])
    donut = [{"produto": p, "total": v["total"]} for p, v in ranked[:9]]
    outros = sum(v["total"] for _, v in ranked[9:])
    if outros:
        donut.append({"produto": "Outros", "total": outros})

    detalhe = [{
        "bitrix_id":    d["bitrix_id"],
        "cliente":      d["cliente"],
        "oportunidade": d["produto"],
        "etapa":        d["etapa_ordenada"],
        "observacoes":  "—",
        "valor":        d["valor"],
        "link_deal":    f'https://gnapp.bitrix24.com.br/crm/deal/details/{d["bitrix_id"]}/',
    } for d in kept(None)]

    return {"etapa_table": etapa_table, "status_table": status_table,
            "kpis": kpis, "donut": donut, "detalhe": detalhe}


# Monkeypatch ANTES de importar o app.
queries.get_funil = _fake_get_funil

import app  # noqa: E402

if __name__ == "__main__":
    print("DEMO (dados fictícios, 3 funis) em http://localhost:8051  —  app.py real na 8050")
    app.app.run(host="127.0.0.1", port=8051, debug=False)
