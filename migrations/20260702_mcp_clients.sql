CREATE TABLE IF NOT EXISTS mcp_clients (
    id         SERIAL       PRIMARY KEY,
    nome       VARCHAR(255) NOT NULL,
    chave      VARCHAR(64)  NOT NULL UNIQUE,
    ativo      BOOLEAN      NOT NULL DEFAULT true,
    created_at TIMESTAMP    NOT NULL DEFAULT NOW()
);
