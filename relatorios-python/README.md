# NimbusTax — Relatórios (Dash)

Relatório de BI em **Python/Dash**, pensado para ser **embarcado (iframe)** no
portal do parceiro e mostrar os dados **filtrados por parceiro** via URL.

> Fase 1: **Funil Diagnóstico** (KPIs, tabela de etapas, status com cross-filter,
> donut Top 9 produtos e tabela detalhe). Os demais funis virão como próximas abas.

## Estrutura

```
relatorios-python/
├── app.py            # App Dash (layout + callbacks)
├── queries.py        # Consultas SQL (Funil Diagnóstico)
├── db.py             # Conexão PostgreSQL (lê .env)
├── assets/style.css  # Estilo (carregado automático pelo Dash)
├── requirements.txt
└── .env.example      # Modelo de configuração (copie para .env)
```

## Rodar localmente

```bash
cd relatorios-python
python -m venv .venv
# Windows:
.venv\Scripts\activate
# Linux/Mac:
source .venv/bin/activate

pip install -r requirements.txt
cp .env.example .env        # depois edite o .env com os dados da VPS
python app.py
```

Acesse: <http://localhost:8050>

Filtrar por parceiro (multi-tenant): <http://localhost:8050/?parceiro=123>

## Produção na VPS (gunicorn + nginx)

```bash
gunicorn app:server -b 127.0.0.1:8050 --workers 2
```

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

### 2. Cross-filter: clicar aplica, clicar de novo limpa (toggle) — em TODO componente
**Qualquer** elemento visual clicável que represente um agrupamento de dados (tabela,
fatia de donut, barra de gráfico, etc.), em **qualquer página**, **deve** funcionar como
filtro. É regra da aplicação, não config por componente. Objetivo: interatividade igual
ou melhor que o Power BI.

- **Clicar** num elemento → aplica o filtro (todos os outros visuais da página recarregam).
- **Clicar no MESMO elemento de novo** → limpa o filtro (tudo volta ao estado cheio).
- **Clicar em outro elemento** → troca o filtro direto, sem precisar desmarcar antes.
- Filtros de fontes diferentes **compõem em AND** (ex.: status + produto), e cada visual
  aplica todos os filtros **menos o que ele próprio é a fonte** (o donut não filtra a si
  mesmo por produto; a tabela de status não filtra a si mesma por status).
- **Destaque na unidade inteira** (linha/fatia), nunca por sub-elemento; sem realce residual.

**Padrões canônicos (reaproveitar nos próximos funis):**
- **Tabela-filtro:** `build_status_table()` — tabela HTML clicável (não `DataTable`, p/
  não ter `active_cell`). `<tr>` com `id={"type": "...-row", "index": <valor>}` + `n_clicks`;
  callback `Input({"type": "...-row", "index": ALL}, "n_clicks")` faz o toggle contra um `dcc.Store`.
- **Gráfico-filtro (donut/barras):** callback com `Input(graph, "clickData")` **e**
  `Output(graph, "clickData")` (self-loop) → lê `clickData["points"][0]["label"]`, faz o
  toggle contra um `dcc.Store` e zera o `clickData` (assim reclicar a mesma fatia dispara
  de novo). Ver `click_product()` em `app.py`.

**Implementado:** filtro de status (tabela) e filtro de produto (donut). Cada novo visual
de agrupamento deve seguir o mesmo padrão.

## Pendências conhecidas

- **Coluna de parceiro:** ✅ resolvida — `queries.PARCEIRO_COLUMN = "parceiro_comercial_id"`.
  O filtro `?parceiro=...` já funciona.
- **Autenticação do embed:** a definir (token assinado vindo do portal).
