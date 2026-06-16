-- Migration: portais_cliente
-- Portal de acesso por empresa: login individual + embed por token

CREATE TABLE portais_cliente (
    id           SERIAL PRIMARY KEY,
    company_id   INTEGER NOT NULL,
    company_name VARCHAR(255) NOT NULL,
    slug         VARCHAR(100) NOT NULL UNIQUE,
    senha_hash   VARCHAR(255) NOT NULL,
    embed_token  VARCHAR(64)  NOT NULL UNIQUE,
    ativo        BOOLEAN NOT NULL DEFAULT true,
    created_at   TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at   TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_portais_slug        ON portais_cliente (slug);
CREATE INDEX idx_portais_embed_token ON portais_cliente (embed_token);
CREATE INDEX idx_portais_company_id  ON portais_cliente (company_id);
