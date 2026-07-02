-- Relatorios BI access control via application system (cliente_aplicacoes), not RBAC.
-- Note: aplicacoes has no UNIQUE constraint on slug and no 'ativo' column
-- (confirmed via information_schema before writing this migration) — using
-- a NOT EXISTS guard instead of ON CONFLICT.
INSERT INTO aplicacoes (slug, nome, descricao)
SELECT 'relatorios-bi', 'Relatórios BI', 'Controle de acesso a relatórios BI por cliente'
WHERE NOT EXISTS (SELECT 1 FROM aplicacoes WHERE slug = 'relatorios-bi');

ALTER TABLE cliente_usuarios ADD COLUMN IF NOT EXISTS pode_ver_relatorio BOOLEAN NOT NULL DEFAULT false;
ALTER TABLE cliente_usuarios ADD COLUMN IF NOT EXISTS pode_criar_portal  BOOLEAN NOT NULL DEFAULT false;
