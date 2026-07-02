-- Migration: portais_bi — campos do relatorio-contabilidade (ContaFarma)
-- ct_indicador_values/labels → array de valores de parceiro_indicacao selecionados
-- ct_contab_values/labels    → array de contabilidade_responsavel_operacional selecionados
-- ct_completo                → TRUE = Relatório Completo (sem filtro de indicador)
-- Todos nullable: usados só quando relatorio_slug = 'relatorio-contabilidade'

ALTER TABLE portais_bi
  ADD COLUMN IF NOT EXISTS ct_indicador_values  JSONB,
  ADD COLUMN IF NOT EXISTS ct_indicador_labels  JSONB,
  ADD COLUMN IF NOT EXISTS ct_contab_values     JSONB,
  ADD COLUMN IF NOT EXISTS ct_contab_labels     JSONB,
  ADD COLUMN IF NOT EXISTS ct_completo          BOOLEAN DEFAULT FALSE;
