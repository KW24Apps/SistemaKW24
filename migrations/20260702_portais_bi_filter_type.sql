-- Hotfix: permitir filter_type 'indicador' e 'contabilidade' (relatorio-contabilidade),
-- além de 'parceiro' e 'oportunidade' (nimbus-tax), na tabela portais_bi.
ALTER TABLE portais_bi DROP CONSTRAINT IF EXISTS portais_bi_filter_type_check;
ALTER TABLE portais_bi ADD CONSTRAINT portais_bi_filter_type_check
    CHECK (filter_type IN ('parceiro', 'oportunidade', 'indicador', 'contabilidade'));
