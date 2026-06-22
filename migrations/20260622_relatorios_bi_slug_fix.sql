-- Migration: relatorios_bi_slug_fix
-- slug must match the folder name inside relatorios-bi/ and is immutable after creation.
-- nome_amigavel is display-only; changing it must never alter the slug.

UPDATE relatorios_bi SET slug = 'relatorio-parceiros-tax' WHERE id = 1;

COMMENT ON COLUMN relatorios_bi.slug IS 'Immutable folder-name identifier. Set at creation only. Must match the directory name inside relatorios-bi/.';
