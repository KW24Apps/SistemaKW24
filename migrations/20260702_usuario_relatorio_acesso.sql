-- Migration: perfis e permissões por relatório
-- 1) quem criou cada usuário  2) grupo dos relatórios  3) acesso granular usuário × relatório

-- 1. Campo criado_por_id em usuarios (quem cadastrou)
ALTER TABLE usuarios
  ADD COLUMN IF NOT EXISTS criado_por_id INTEGER REFERENCES usuarios(id);

-- 2. Campo grupo em relatorios_bi (agrupa relatórios no menu e nas permissões)
ALTER TABLE relatorios_bi
  ADD COLUMN IF NOT EXISTS grupo VARCHAR(50);

UPDATE relatorios_bi SET grupo = 'nimbus'
  WHERE slug = 'relatorio-parceiros-tax';
UPDATE relatorios_bi SET grupo = 'contabilidade'
  WHERE slug = 'relatorio-contabilidade';

-- 3. Acesso granular por usuário × relatório
CREATE TABLE IF NOT EXISTS usuario_relatorio_acesso (
  id           SERIAL PRIMARY KEY,
  usuario_id   INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
  relatorio_id INTEGER NOT NULL REFERENCES relatorios_bi(id) ON DELETE CASCADE,
  pode_portal  BOOLEAN NOT NULL DEFAULT FALSE,
  criado_em    TIMESTAMP DEFAULT NOW(),
  UNIQUE(usuario_id, relatorio_id)
);
