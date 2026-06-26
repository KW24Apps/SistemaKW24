# Relatório Contabilidade — ContaFarma (Dash)

App Dash dentro de `relatorios-bi/`, com a mesma estrutura técnica do
`relatorios-bi/relatorio-parceiros-tax/` (mesmo padrão `db.py`, mesmo `.env`,
mesmo Gunicorn + nginx + `auth_request`, mesmo `run_local.py`).

- **Rota pública:** `https://app.kw24.com.br/relatorios-bi/relatorio-contabilidade/`
- **Banco:** `bx_sync_contabilidade` · tabela `tbl_onboard`
- **Porta Gunicorn:** `8051` (a `8050` é do `relatorio-parceiros-tax`)
- **Service systemd:** `kw24-contabilidade.service`

## Estrutura

```
relatorios-bi/relatorio-contabilidade/
  db.py            # acesso ao PostgreSQL (DB_NAME=bx_sync_contabilidade)
  queries.py       # KPIs, tabela por vendedor, indicadas, tabela por contrato
  app.py           # Dash: 2 abas, filtro de data, 3 blocos por aba
  run_local.py     # teste local sem o prefixo do nginx (NÃO versionado)
  requirements.txt
  .env.example     # template de credenciais
  .env             # credenciais reais (NÃO versionado — .gitignore cobre `.env`)
  assets/
    style.css          # tema ContaFarma (#00BBBC / #263846)
    datepicker_ptbr.js # tradução PT-BR do calendário
```

## Abas e blocos

Duas abas de estrutura idêntica — só muda o filtro de `etapa`:

| Aba | etapa IN |
|-----|----------|
| **Vendas Fechadas** | Boas Vindas, Constituição Empresa, Delegação de Tarefas, Conferência, Concluídos |
| **Em Negociação** | Solicitação, Orçamento, Gerar Proposta, Gerar Contrato, Click Sign |

Cada aba: **Bloco 1** 4 KPIs (Total · Próprias · Indicadas · Ticket médio) ·
**Bloco 2** tabela por vendedor (linha expansível → negócios indicados, com o
nome do cliente) · **Bloco 3** tabela por tipo de contrato.

> O nome do cliente na expansão usa colunas diferentes por aba: `empresa` em
> Vendas Fechadas e `nome_da_empresa` em Em Negociação (ver `NEGOCIO_COL` em
> `queries.py`), expostas como `negocio`.

### Regra de origem (própria x indicada)

`parceiro_indicacao`:
- `FF CONTABILIDADE LTDA` → **própria**
- `CAPITON CONTABILIDADE S/S` → **própria**
- `NULL` / vazio → **própria** (registro não é perdido)
- qualquer outro valor → **indicada** (o valor é o nome do indicador)

## Teste local (`run_local.py`)

Em produção o app é servido pelo nginx sob o prefixo
`/relatorios-bi/relatorio-contabilidade/`. Rodando direto no localhost esse
prefixo quebra os bundles JS do Dash → use `run_local.py`, que força o prefixo
para `/` e serve em `http://localhost:8051`.

**Pré-requisito:** túnel SSH encaminhando a porta **5432** para o Postgres do
servidor (o `.env` aponta para `127.0.0.1:5432`).

```bash
cd relatorios-bi/relatorio-contabilidade
# usa o venv compartilhado em relatorios-bi/.venv (deps idênticas ao relatório irmão)
python run_local.py
# → http://localhost:8051
```

---

## Deploy — produção (tarefa SEPARADA, após validação local)

> ✅ **Schema verificado em 2026-06-26** (via túnel): todas as 8 colunas existem,
> `valor` é `numeric`, `criado_em` é `timestamp`, e os dois nomes de venda
> própria batem exatamente. **Nenhum ajuste de `queries.py` foi necessário.**
> Comando de re-verificação (no servidor, sem expor a senha):
> ```bash
> set -a; . /var/www/app.kw24.com.br/relatorios-bi/relatorio-contabilidade/.env; set +a
> PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" \
>   -c '\d tbl_onboard' \
>   -c "SELECT etapa, COUNT(*) FROM tbl_onboard GROUP BY etapa ORDER BY 2 DESC;"
> ```

### 1. systemd — `/etc/systemd/system/kw24-contabilidade.service`

```ini
[Unit]
Description=ContaFarma Relatorio Contabilidade Dash App
After=network.target

[Service]
User=kw24
WorkingDirectory=/var/www/app.kw24.com.br/relatorios-bi/relatorio-contabilidade
Environment="PATH=/var/www/app.kw24.com.br/relatorios-python/.venv/bin"
ExecStart=/var/www/app.kw24.com.br/relatorios-python/.venv/bin/gunicorn app:server -b 127.0.0.1:8051 --workers 2
Restart=always

[Install]
WantedBy=multi-user.target
```

> Reaproveita o venv de produção em `relatorios-python/.venv/` (dependências
> idênticas às do `relatorio-parceiros-tax`).

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now kw24-contabilidade.service
sudo systemctl status kw24-contabilidade.service
journalctl -u kw24-contabilidade.service -n 50
```

### 2. nginx — bloco em `/etc/nginx/sites-enabled/app.kw24.com.br`

Adicionar (mesmo padrão `auth_request` do `relatorio-parceiros-tax`; `^~` para
ter prioridade sobre o regex `~ \.php$`; trailing slash no `proxy_pass` faz o
nginx remover o prefixo antes de passar ao Gunicorn):

```nginx
location ^~ /relatorios-bi/relatorio-contabilidade/ {
    auth_request /_auth-check;
    error_page 401 = /public/login.php;
    proxy_pass http://127.0.0.1:8051/;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_read_timeout 60s;
}
```

```bash
sudo nginx -t && sudo systemctl reload nginx
```

### 3. Registro no portal (tabela `relatorios_bi`)

O hub (`public/relatorio-teste.php` + `api/relatorios-bi.php`) lista relatórios
da tabela `relatorios_bi` e abre `https://app.kw24.com.br/relatorios-bi/<slug>`.
Inserir uma linha com `slug = 'relatorio-contabilidade'` (via migration em
`migrations/`, conforme `stack_deploy.md`).

### 4. Deploy do código

```bash
ssh -o StrictHostKeyChecking=no -i ~/.ssh/kw24_deploy -p 4030 kw24@192.168.3.90 \
  "cd /var/www/app.kw24.com.br && git pull && \
   /var/www/app.kw24.com.br/relatorios-python/.venv/bin/pip install -r relatorios-bi/relatorio-contabilidade/requirements.txt && \
   sudo systemctl restart kw24-contabilidade.service"
```

> O `.env` **não** é versionado — criar manualmente em
> `/var/www/app.kw24.com.br/relatorios-bi/relatorio-contabilidade/.env` no
> servidor (copiar de `.env.example` e preencher), uma única vez.
