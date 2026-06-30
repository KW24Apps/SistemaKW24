-- Seed: app Nimbus Partners Report em aplicacoes
-- Usa INSERT condicional pois aplicacoes.slug pode nao ter constraint UNIQUE no banco

INSERT INTO aplicacoes (slug, nome, descricao)
SELECT 'nimbus_parceiros',
       'Nimbus Partners Report',
       'Relatório automático de parceiros Nimbus – categoria 59'
WHERE NOT EXISTS (SELECT 1 FROM aplicacoes WHERE slug = 'nimbus_parceiros');
