"""
Camada de acesso ao PostgreSQL.

Lê as credenciais de variáveis de ambiente (.env) — nunca hardcoded.
Uma conexão por consulta é suficiente para o volume deste relatório;
se a carga crescer, trocar por um pool (psycopg2.pool) aqui dentro
sem mexer no resto do código.
"""

import os
from contextlib import contextmanager

# .env é opcional (ex.: no modo demo nem precisa do banco).
try:
    from dotenv import load_dotenv
    load_dotenv()
except ImportError:
    pass

DB_CONFIG = {
    "host":     os.getenv("DB_HOST", "127.0.0.1"),
    "port":     os.getenv("DB_PORT", "5432"),
    "dbname":   os.getenv("DB_NAME", "bx_sync_nimbus_tax"),
    "user":     os.getenv("DB_USER", "postgres"),
    "password": os.getenv("DB_PASSWORD", ""),
    "connect_timeout": 10,
}


@contextmanager
def get_cursor():
    """Abre conexão + cursor (dict), garante fechamento."""
    import psycopg2
    import psycopg2.extras
    conn = psycopg2.connect(**DB_CONFIG)
    try:
        cur = conn.cursor(cursor_factory=psycopg2.extras.RealDictCursor)
        yield cur
        conn.commit()
    finally:
        conn.close()


def fetch_all(sql, params=None):
    with get_cursor() as cur:
        cur.execute(sql, params or {})
        return [dict(r) for r in cur.fetchall()]


def fetch_one(sql, params=None):
    with get_cursor() as cur:
        cur.execute(sql, params or {})
        row = cur.fetchone()
        return dict(row) if row else None
