# NimbusTax — Relatórios (Dash)

Relatório de BI em **Python/Dash**, pensado para ser **embarcado (iframe)** no
portal do parceiro e mostrar os dados **filtrados por parceiro** via URL.

> **Três funis** (abas), todos com a MESMA estrutura — KPIs, tabela de etapas, status
> com cross-filter, donut Top 9 e tabela detalhe:
> - **Funil Diagnóstico** → pipeline `RELATÓRIO PRELIMINAR (DIAGNOST)`
> - **Funil Operacional** → pipeline `OPERACIONAL`
> - **Funil Retificação** → pipeline `RETIFICAÇÃO & FATURAMENTO`
>
> Uma única página parametrizada pelo pipeline: a aba ativa fica num `dcc.Store(rt-pipeline)`,
> e **todos os componentes/callbacks são reaproveitados** (cross-filter, toggle, base64, etc.).
> A regra de status (`STATUS_CASE`) é idêntica; o `ETAPA_ORDENADA_CASE` tem o ramo de cada
> pipeline (ordem das etapas vinda de `tbl_etapas.sort`). Trocar de aba reseta o filtro.

## Estrutura

```
relatorios-bi/
├── .venv/                          # ambiente virtual (fica no nível de relatorios-bi/)
└── relatorio-parceiros-tax/
    ├── app.py                       # App Dash (layout + callbacks)
    ├── queries.py                   # Consultas SQL (3 funis + Dashboard)
    ├── db.py                        # Conexão PostgreSQL (lê .env)
    ├── demo_server.py               # Servidor de demonstração (dados fictícios)
    ├── assets/style.css             # Estilo (carregado automático pelo Dash)
    ├── assets/datepicker_ptbr.js    # Calendário em pt-BR
    ├── assets/donut_legend_hover.js # Destaque/pull ao passar o mouse na legenda
    ├── requirements.txt
    └── .env.example                 # Modelo de configuração (copie para .env)
```

## Rodar localmente

```bash
cd relatorios-bi
python -m venv .venv
# Windows:
.venv\Scripts\activate
# Linux/Mac:
source .venv/bin/activate

cd relatorio-parceiros-tax
pip install -r requirements.txt
cp .env.example .env        # depois edite o .env com os dados da VPS
python app.py
```

Acesse: <http://localhost:8050>

Filtrar por parceiro (multi-tenant): <http://localhost:8050/?parceiro=123>

## Produção na VPS (gunicorn + nginx)

```bash
cd relatorios-bi/relatorio-parceiros-tax
gunicorn app:server -b 127.0.0.1:8050 --workers 2
```

> **systemd:** `WorkingDirectory=/var/www/app.kw24.com.br/relatorios-bi/relatorio-parceiros-tax`

Exemplo de `nginx` (subdomínio + permitir embed em iframe):

```nginx
location / {
    proxy_pass http://127.0.0.1:8050;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
}
```

> **Embedding:** para o portal do parceiro conseguir embutir num `<iframe>`,
> o cabeçalho `X-Frame-Options` não pode bloquear, e o ideal é definir
> `Content-Security-Policy: frame-ancestors <domínio-do-portal>`. Isso é o
> "assunto de depois" — quando definirmos como o portal passa o ID do parceiro.

## Regras de design (valem para TODO o relatório)

### 1. Sempre banco real — nunca dados fictícios
O app **sempre** conecta no PostgreSQL real. **Não existe** modo demo / fallback para
dados falsos. Se a conexão falhar, a tela mostra um **erro claro** (banner vermelho) —
nunca mascara a falha com dado fabricado. Não reintroduzir `DEMO`/mock.

### 2. Cross-filter: clicar aplica, clicar de novo limpa — em TODO componente
**Qualquer** elemento visual clicável que represente um agrupamento de dados (tabela,
fatia de donut, barra, etc.), em **qualquer página**, **deve** funcionar como filtro da
página inteira. É regra da aplicação, não config por componente. Objetivo: interatividade
igual ou melhor que o Power BI.

- **Clicar** num elemento → aplica o filtro (todos os outros visuais recarregam).
- **Clicar no MESMO elemento de novo** → limpa o filtro (tudo volta ao estado cheio).
- **Clicar em outro elemento** → **troca** o filtro direto (substitui), sem desmarcar antes.
- **Um filtro ativo por vez** (não compõe). O componente que é a **fonte** do filtro atual
  não filtra a si mesmo (mostra todos); os demais refletem o filtro.
- **Destaque na unidade inteira** (linha/fatia), nunca por sub-elemento; sem realce residual.

**Arquitetura (central):** um único `dcc.Store(id="rt-filtro-ativo")` guarda
`{"tipo": "etapa"|"status"|"produto", "valor": <v>}` ou `None`. Cada componente clicável
tem um callback que faz o toggle desse store (`_toggle()` em `app.py`, com
`allow_duplicate=True`). O callback central `load_data` escuta o store e redesenha todos
os visuais; `queries.get_diagnostico(filtro=...)` recebe o filtro e cada query aplica via
`_filtro_clause(filtro, skip_tipo=<fonte>)` — pulando o próprio tipo-fonte.

**Padrões canônicos (reaproveitar nos próximos funis):**
- **Tabela-filtro:** `build_filter_table(rows, key, header, row_type, active_value)` — tabela
  HTML clicável (não `DataTable`, p/ não ter `active_cell`). `<tr>` com
  `id={"type": row_type, "index": <valor>}` + `n_clicks`; callback
  `Input({"type": row_type, "index": ALL}, "n_clicks")` → `_toggle`.
- **Gráfico-filtro (donut/barras):** callback com `Input(graph, "clickData")` **e**
  `Output(graph, "clickData")` (self-loop, zera p/ permitir reclique) → `_toggle`.
  Ver `click_product()`.

**Implementado no Funil Diagnóstico:** etapa (tabela), status (tabela), produto (donut).
Cada novo visual de agrupamento deve seguir o mesmo padrão e tipo no store central.

## Pendências conhecidas

- **Coluna de parceiro:** ✅ resolvida — `queries.PARCEIRO_COLUMN = "parceiro_comercial_id"`.
  O filtro `?parceiro=...` já funciona.
- **Autenticação do embed:** a definir (token assinado vindo do portal).
