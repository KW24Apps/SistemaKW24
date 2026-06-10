"""
Dados FICTÍCIOS para visualizar a tela sem precisar do banco.

Ativado por DEMO=1 (ver app.py). Respeita o cross-filter de status para a
preview já mostrar a interação clicável funcionando.
"""

# Base de negócios fictícios: (id, etapa, sort, status, produto, valor, cliente)
_DEALS = [
    (1001, "Lead Recebido",        1, "Em Diagnóstico",   "Recuperação ICMS",      12000, "Alfa Comércio LTDA"),
    (1002, "Em Análise",           2, "Em Diagnóstico",   "Recuperação PIS/COFINS", 8500, "Beta Indústria SA"),
    (1003, "Em Análise",           2, "Em Diagnóstico",   "Recuperação ICMS",      15300, "Gamma Varejo ME"),
    (1004, "Diagnóstico Concluído", 3, "Com Oportunidade", "Exclusão ICMS na Base", 42000, "Delta Atacado LTDA"),
    (1005, "Diagnóstico Concluído", 3, "Com Oportunidade", "Recuperação PIS/COFINS",31000, "Épsilon Serviços ME"),
    (1006, "Proposta Enviada",     4, "Com Oportunidade", "Exclusão ICMS na Base", 58000, "Zeta Logística SA"),
    (1007, "Proposta Enviada",     4, "Com Oportunidade", "Recuperação ICMS",      27500, "Eta Transportes LTDA"),
    (1008, "Negociação",           5, "Com Oportunidade", "Recuperação INSS",      19000, "Theta Alimentos SA"),
    (1009, "Negociação",           5, "Com Oportunidade", "Exclusão ICMS na Base", 64000, "Iota Construções LTDA"),
    (1010, "Sem Interesse",        9, "Sem Oportunidade", "Recuperação ICMS",          0, "Kappa Móveis ME"),
    (1011, "Perdidos",             9, "Sem Oportunidade", "Recuperação PIS/COFINS",    0, "Lambda Têxtil LTDA"),
    (1012, "Documentos Incompletos", 9, "Sem Oportunidade", "(Sem Produto)",           0, "Mu Distribuidora SA"),
    (1013, "Suspenso",             8, "Suspenso",         "Recuperação INSS",      11000, "Nu Farma LTDA"),
    (1014, "Suspenso",             8, "Suspenso",         "Recuperação ICMS",       9500, "Xi Agro ME"),
    (1015, "Lead Recebido",        1, "Em Diagnóstico",   "Energia - Crédito",      7200, "Ômicron Energia SA"),
    (1016, "Em Análise",           2, "Em Diagnóstico",   "Energia - Crédito",      6800, "Pi Soluções LTDA"),
    (1017, "Diagnóstico Concluído", 3, "Com Oportunidade", "Recuperação INSS",      22000, "Rho Comércio ME"),
    (1018, "Proposta Enviada",     4, "Com Oportunidade", "Recuperação ICMS",      35000, "Sigma Indústria SA"),
    (1019, "Negociação",           5, "Com Oportunidade", "Exclusão ICMS na Base", 71000, "Tau Atacadista LTDA"),
    (1020, "Fechado com outra empresa", 9, "Sem Oportunidade", "Recuperação PIS/COFINS", 0, "Upsilon Serviços ME"),
]

_COLS = ("bitrix_id", "etapa", "sort", "status", "produto", "valor", "cliente")


def _rows():
    return [dict(zip(_COLS, d)) for d in _DEALS]


def _filtered(status_filter):
    rows = _rows()
    if status_filter:
        rows = [r for r in rows if r["status"] == status_filter]
    return rows


def get_diagnostico(status_filter=None, parceiro=None):
    base = _filtered(status_filter)

    # A) Tabela de etapas
    etapa_agg = {}
    for r in base:
        key = (r["sort"], r["etapa"])
        a = etapa_agg.setdefault(key, {"total": 0, "valor_soma": 0})
        a["total"] += 1
        a["valor_soma"] += r["valor"]
    etapa_table = [
        {"etapa_ordenada": f"{sort:02d} - {etapa}",
         "total": a["total"], "valor_soma": a["valor_soma"]}
        for (sort, etapa), a in sorted(etapa_agg.items())
    ]

    # B) Status (fonte do cross-filter → sempre todos)
    status_agg = {}
    for r in _rows():
        a = status_agg.setdefault(r["status"], {"total": 0, "valor_soma": 0})
        a["total"] += 1
        a["valor_soma"] += r["valor"]
    status_table = [
        {"status": s, "total": a["total"], "valor_soma": a["valor_soma"]}
        for s, a in sorted(status_agg.items(), reverse=True)
    ]

    # C) KPIs
    kpis = {"total": len(base), "valor_soma": sum(r["valor"] for r in base)}

    # D) Donut por produto
    prod_agg = {}
    for r in base:
        prod_agg[r["produto"]] = prod_agg.get(r["produto"], 0) + 1
    donut = [{"produto": p, "total": t}
             for p, t in sorted(prod_agg.items(), key=lambda x: -x[1])]

    # E) Detalhe
    detalhe = [
        {"bitrix_id": r["bitrix_id"],
         "cliente": r["cliente"],
         "oportunidade": r["produto"],
         "etapa": r["etapa"],
         "observacoes": "—",
         "valor": r["valor"],
         "link_deal": f'https://gnapp.bitrix24.com.br/crm/deal/details/{r["bitrix_id"]}/'}
        for r in sorted(base, key=lambda x: -x["bitrix_id"])
    ]

    return {
        "etapa_table": etapa_table,
        "status_table": status_table,
        "kpis": kpis,
        "donut": donut,
        "detalhe": detalhe,
    }
