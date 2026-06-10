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

### 2. Tabela-filtro: clicar aplica, clicar de novo limpa (toggle)
Toda tabela que serve de **filtro** (cross-filter), em **qualquer página** do relatório,
**deve** seguir o mesmo comportamento — isto é regra da aplicação, não config por tabela:

- **Clicar numa linha** → aplica o filtro (todos os outros visuais recarregam).
- **Clicar na MESMA linha de novo** → limpa o filtro (tudo volta ao estado cheio).
- **Destaque é na LINHA inteira** (`.rt-row-active`), nunca por célula.
- **Sem realce residual** após desmarcar; o realce padrão de célula ativa do Dash
  **fica desabilitado**.

**Como implementar (padrão canônico):** use uma **tabela HTML clicável** (não `DataTable`)
— ver `build_status_table()` em `app.py`. Cada `<tr>` tem `id={"type": "...-row", "index": <valor>}`
e `n_clicks`; um callback por padrão (`Input({"type": "...-row", "index": ALL}, "n_clicks")`)
faz o toggle contra o valor guardado num `dcc.Store`. Isso elimina o `active_cell` do
Dash e, com ele, todo o problema de célula focada.

> Reaproveite `build_status_table()` como base ao criar novas tabelas-filtro nos
> próximos funis (Operacional, Retificação, Faturamento).

## Pendências conhecidas

- **Coluna de parceiro:** ✅ resolvida — `queries.PARCEIRO_COLUMN = "parceiro_comercial_id"`.
  O filtro `?parceiro=...` já funciona.
- **Autenticação do embed:** a definir (token assinado vindo do portal).
