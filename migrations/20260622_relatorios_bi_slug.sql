-- Migration: relatorios_bi_slug
-- Repurpose slug column (folder name → URL slug from nome_amigavel), remove url_base

ALTER TABLE relatorios_bi ALTER COLUMN slug TYPE varchar(200);
ALTER TABLE relatorios_bi DROP COLUMN IF EXISTS url_base;

UPDATE relatorios_bi SET slug = 'parceiros-nimbus-tax' WHERE id = 1;
