-- RBAC: tabela de perfis de permissão + FK em usuarios
CREATE TABLE permission_profiles (
    id        SERIAL PRIMARY KEY,
    nome      VARCHAR(100) NOT NULL UNIQUE,
    menus     JSONB NOT NULL DEFAULT '[]',
    criado_em TIMESTAMP NOT NULL DEFAULT NOW()
);

ALTER TABLE usuarios
    ADD COLUMN profile_id INTEGER REFERENCES permission_profiles(id) ON DELETE SET NULL;

-- Perfis padrão
INSERT INTO permission_profiles (nome, menus) VALUES
(
    'Admin Interno',
    '["dashboard","cadastro","usuarios","permissoes","aplicacoes","relatorio","relatorio-teste","portais-bi","logs","bancodados","financeiro","financeiro-relatorios","portais","configuracoes"]'
),
(
    'Usuário Cliente',
    '["relatorio-teste","portais-bi"]'
);

-- Vincular usuários existentes
UPDATE usuarios
    SET profile_id = (SELECT id FROM permission_profiles WHERE nome = 'Admin Interno')
    WHERE perfil = 'admin_interno';

UPDATE usuarios
    SET profile_id = (SELECT id FROM permission_profiles WHERE nome = 'Usuário Cliente')
    WHERE perfil = 'usuario_cliente';
