-- Phase 1: organizacoes, org_id em clientes, chave/descricao em cliente_aplicacoes

-- 1.1 — Tabela organizacoes
CREATE TABLE IF NOT EXISTS organizacoes (
    id             SERIAL       PRIMARY KEY,
    nome           VARCHAR(255) NOT NULL,
    ativo          BOOLEAN      NOT NULL DEFAULT true,
    webhook_bitrix TEXT,
    created_at     TIMESTAMP    NOT NULL DEFAULT NOW()
);

-- 1.2 — org_id em clientes (nullable, sem NOT NULL — linhas existentes ficam intactas)
ALTER TABLE clientes ADD COLUMN IF NOT EXISTS org_id INTEGER REFERENCES organizacoes(id);

-- 1.3 — chave e descricao em cliente_aplicacoes
ALTER TABLE cliente_aplicacoes ADD COLUMN IF NOT EXISTS chave     VARCHAR(100);
ALTER TABLE cliente_aplicacoes ADD COLUMN IF NOT EXISTS descricao VARCHAR(255);

-- 1.5 — Remove UNIQUE(cliente_id, aplicacao_id) — agora permitimos múltiplas instâncias da mesma app por cliente
ALTER TABLE cliente_aplicacoes DROP CONSTRAINT IF EXISTS cliente_aplicacoes_cliente_id_aplicacao_id_key;
