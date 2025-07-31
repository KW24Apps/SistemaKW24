<?php
/**
 * DATABASE CONNECTION CLASS - KW24 APPS V2
 * Implementando melhorias do módulo 4 - Sistema robusto de conexão
 */

class Database {
    private static $instance = null;
    private $connection;
    private $config;
    
    private function __construct() {
        $this->config = require_once __DIR__ . '/../config/config.php';
        $this->connect();
    }
    
    /**
     * Singleton pattern para conexão única
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Estabelece conexão com o banco
     */
    private function connect(): void {
        try {
            $dbConfig = $this->config['database'];
            
            $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
            
            $this->connection = new PDO(
                $dsn,
                $dbConfig['username'],
                $dbConfig['password'],
                $dbConfig['options']
            );
            
            $this->logInfo("Conexão com banco estabelecida com sucesso");
            
        } catch (PDOException $e) {
            $this->logError("Erro na conexão com banco: " . $e->getMessage());
            throw new Exception("Falha na conexão com o banco de dados");
        }
    }
    
    /**
     * Retorna a conexão PDO
     */
    public function getConnection(): PDO {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }
    
    /**
     * Executa query preparada
     */
    public function execute(string $sql, array $params = []): PDOStatement {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            
            $this->logDebug("Query executada: " . $sql, $params);
            
            return $stmt;
            
        } catch (PDOException $e) {
            $this->logError("Erro na execução da query: " . $e->getMessage(), [
                'sql' => $sql,
                'params' => $params
            ]);
            throw new Exception("Erro na execução da query");
        }
    }
    
    /**
     * Busca um único registro
     */
    public function fetchOne(string $sql, array $params = []): ?array {
        $stmt = $this->execute($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Busca múltiplos registros
     */
    public function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Retorna último ID inserido
     */
    public function getLastInsertId(): string {
        return $this->connection->lastInsertId();
    }
    
    /**
     * Inicia transação
     */
    public function beginTransaction(): bool {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Confirma transação
     */
    public function commit(): bool {
        return $this->connection->commit();
    }
    
    /**
     * Desfaz transação
     */
    public function rollback(): bool {
        return $this->connection->rollBack();
    }
    
    /**
     * Verifica se está em transação
     */
    public function inTransaction(): bool {
        return $this->connection->inTransaction();
    }
    
    /**
     * Log de informações
     */
    private function logInfo(string $message, array $context = []): void {
        if ($this->config['logging']['enabled']) {
            $this->writeLog('INFO', $message, $context);
        }
    }
    
    /**
     * Log de debug
     */
    private function logDebug(string $message, array $context = []): void {
        if ($this->config['logging']['enabled'] && $this->config['logging']['level'] === 'DEBUG') {
            $this->writeLog('DEBUG', $message, $context);
        }
    }
    
    /**
     * Log de erros
     */
    private function logError(string $message, array $context = []): void {
        if ($this->config['logging']['enabled']) {
            $this->writeLog('ERROR', $message, $context);
        }
    }
    
    /**
     * Escreve log no arquivo
     */
    private function writeLog(string $level, string $message, array $context = []): void {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        $logEntry = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;
        
        $logFile = __DIR__ . '/' . $this->config['logging']['file'];
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Previne clonagem
     */
    private function __clone() {}
    
    /**
     * Previne deserialização
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
