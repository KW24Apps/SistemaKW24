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

## Pendências conhecidas

- **Coluna de parceiro:** `queries.PARCEIRO_COLUMN` está `None` (mostra todos).
  Assim que confirmarmos o nome da coluna em `tbl_negocio` que identifica o
  parceiro, é só preencher essa constante e o filtro `?parceiro=...` passa a valer.
- **Autenticação do embed:** a definir (token assinado vindo do portal).
