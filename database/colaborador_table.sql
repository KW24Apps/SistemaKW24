-- SCRIPTS PARA ADAPTAÇÃO À TABELA COLABORADORES EXISTENTE - KW24 APPS V2
-- Este arquivo contém scripts para trabalhar com a estrutura real do cliente

-- TABELA DE LOG DE TENTATIVAS DE LOGIN (OPCIONAL)
CREATE TABLE IF NOT EXISTS `login_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario` varchar(50) NOT NULL,
  `sucesso` tinyint(1) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `tentativa_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario`),
  KEY `idx_tentativa_em` (`tentativa_em`),
  KEY `idx_sucesso` (`sucesso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- SCRIPT PARA MIGRAR SENHA DO GABRIEL PARA HASH SEGURO
-- Execute este comando para atualizar a senha do Gabriel
UPDATE Colaboradores 
SET senha = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
WHERE UserName = 'gabriel.acker';
-- Esta é a senha '123456' hasheada com bcrypt para teste

-- COMENTÁRIOS NA TABELA
ALTER TABLE `Colaboradores` 
  COMMENT = 'Tabela de colaboradores/usuários do sistema KW24 Apps v2 - Estrutura original do cliente adaptada';

ALTER TABLE `login_log` 
  COMMENT = 'Log de tentativas de login para auditoria e segurança';

-- ÍNDICES PARA PERFORMANCE (OPCIONAL)
-- CREATE INDEX idx_colaboradores_username_ativo ON Colaboradores (UserName, ativo);
-- CREATE INDEX idx_colaboradores_ultimo_acesso ON Colaboradores (ultimo_acesso);

/*
ESTRUTURA ATUAL DA TABELA COLABORADORES:
- id (PK)
- Nome (original do cliente)
- CPF (original do cliente - útil)
- Cargo (original do cliente - útil)  
- Telefone (original do cliente - útil)
- Email (original do cliente)
- UserName (original do cliente - usado como 'usuario')
- senha (original do cliente)
- perfil (adicionado - Administrador/Usuário/Supervisor)
- ativo (adicionado - controle de usuários)
- ultimo_acesso (adicionado - monitoramento)
- tentativas_login (adicionado - segurança)
- criado_em (adicionado - auditoria)
- atualizado_em (adicionado - controle)
*/
