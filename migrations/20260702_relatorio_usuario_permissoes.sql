-- Relatorios BI: permissões granulares por relatório x usuário (por cliente).
-- Substitui o modelo anterior de flags globais em cliente_usuarios
-- (pode_ver_relatorio / pode_criar_portal) por um modelo per-report per-user.
--
-- A lista de "quais relatórios estão habilitados para o cliente" continua em
-- cliente_aplicacoes.config_extra->relatorios (array de slugs, formato já existente,
-- sem mudança) — esta tabela só guarda QUEM (usuario_id) pode VER/CRIAR PORTAL em
-- QUAL relatório (relatorio_id) DENTRO de qual instância de app do cliente
-- (cliente_aplicacao_id), permitindo que o mesmo usuário tenha acesso diferente
-- por relatório em vez de uma flag única para todos os relatórios habilitados.
CREATE TABLE IF NOT EXISTS relatorio_usuario_permissoes (
    id                    SERIAL PRIMARY KEY,
    cliente_aplicacao_id  INTEGER NOT NULL REFERENCES cliente_aplicacoes(id) ON DELETE CASCADE,
    relatorio_id          INTEGER NOT NULL REFERENCES relatorios_bi(id) ON DELETE CASCADE,
    usuario_id            INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    pode_ver              BOOLEAN NOT NULL DEFAULT false,
    pode_criar_portal     BOOLEAN NOT NULL DEFAULT false,
    created_at            TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE (cliente_aplicacao_id, relatorio_id, usuario_id)
);

CREATE INDEX IF NOT EXISTS idx_relatorio_usuario_permissoes_usuario
    ON relatorio_usuario_permissoes (usuario_id);
