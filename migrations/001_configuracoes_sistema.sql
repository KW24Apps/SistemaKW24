CREATE TABLE IF NOT EXISTS configuracoes_sistema (
    chave        VARCHAR(100) PRIMARY KEY,
    valor        TEXT         NOT NULL DEFAULT '',
    atualizado_em TIMESTAMP   NOT NULL DEFAULT NOW()
);

INSERT INTO configuracoes_sistema (chave, valor) VALUES
    ('financeiro_dia_inicio',      '27'),
    ('financeiro_webhook_bitrix',  '')
ON CONFLICT (chave) DO NOTHING;
