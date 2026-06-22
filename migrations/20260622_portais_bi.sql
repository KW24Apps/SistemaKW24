-- Portal BI: portais de acesso externo filtrado por parceiro ou oportunidade
CREATE TABLE portais_bi (
    id             SERIAL        PRIMARY KEY,
    relatorio_slug VARCHAR(100)  NOT NULL,
    filter_type    VARCHAR(20)   NOT NULL CHECK (filter_type IN ('parceiro', 'oportunidade')),
    filter_values  JSONB         NOT NULL DEFAULT '[]',
    filter_labels  JSONB         NOT NULL DEFAULT '[]',
    slug           VARCHAR(100)  UNIQUE NOT NULL,
    nome           VARCHAR(200),
    senha_hash     VARCHAR(255)  NOT NULL,
    embed_token    VARCHAR(100)  UNIQUE NOT NULL,
    ativo          BOOLEAN       NOT NULL DEFAULT true,
    created_at     TIMESTAMP     NOT NULL DEFAULT NOW()
);
