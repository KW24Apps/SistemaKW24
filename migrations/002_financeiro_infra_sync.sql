-- Migration 002: tabela de controle de idempotência para syncInfra
-- Garante que não criamos cards duplicados em cat/284/ mesmo sem paginação Bitrix

CREATE TABLE IF NOT EXISTS financeiro_infra_sync (
    id          SERIAL      PRIMARY KEY,
    referencia  VARCHAR(7)  NOT NULL,
    company_id  INTEGER     NOT NULL,
    produto_dest INTEGER    NOT NULL,
    depto_dest  INTEGER,
    bitrix_id   INTEGER     NOT NULL,
    criado_em   TIMESTAMP   DEFAULT NOW()
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_infra_sync
    ON financeiro_infra_sync (referencia, company_id, produto_dest, COALESCE(depto_dest, 0));

CREATE INDEX IF NOT EXISTS idx_infra_sync_referencia
    ON financeiro_infra_sync (referencia);
