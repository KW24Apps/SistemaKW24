-- Migration: relatorios_bi
-- Hub de relatórios BI: registro de relatórios disponíveis no painel

CREATE TABLE relatorios_bi (
    id            SERIAL PRIMARY KEY,
    slug          VARCHAR(100) NOT NULL UNIQUE,
    nome_amigavel VARCHAR(200) NOT NULL,
    url_base      VARCHAR(500) NOT NULL,
    visivel       BOOLEAN      NOT NULL DEFAULT true,
    ordem         INTEGER      NOT NULL DEFAULT 0,
    created_at    TIMESTAMP DEFAULT NOW()
);

INSERT INTO relatorios_bi (slug, nome_amigavel, url_base, visivel, ordem)
VALUES ('relatorio-parceiros-tax', 'Parceiros Nimbus TAX', 'http://relatorio.kw24.com.br', true, 0);
