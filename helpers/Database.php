<?php
/**
 * DATABASE CONNECTION - KW24 APPS V2
 * Singleton pattern para conexão única com MySQL
 */

class Database {
    private static $instance = null;
    private $connection;
    private $config;
    
    private function __construct() {
        $configFile = __DIR__ . '/../config/config.php';
        if (!file_exists($configFile)) {
            throw new Exception("Arquivo de configuração não encontrado");
        }
        
        $this->config = require $configFile;
        
        if (!isset($this->config['database'])) {
            throw new Exception("Configuração de banco de dados não encontrada");
        }
        
        $this->connect();
    }
    
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect(): void {
        try {
            $dbConfig = $this->config['database'];
            $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
            
            // Log detalhado da tentativa de conexão (sem senha por segurança)
            error_log("[DATABASE] Tentando conectar - Host: {$dbConfig['host']}, DB: {$dbConfig['dbname']}, User: {$dbConfig['username']}");
            
            $this->connection = new PDO(
                $dsn,
                $dbConfig['username'],
                $dbConfig['password'],
                $dbConfig['options']
            );
            
            error_log("[DATABASE] Conexão estabelecida com sucesso!");
            
        } catch (PDOException $e) {
            $errorMsg = "Falha na conexão com o banco de dados: " . $e->getMessage();
            error_log("[DATABASE] ERRO DE CONEXÃO: " . $errorMsg);
            
            // Em ambiente de desenvolvimento, mostra mais detalhes
            if (strpos(__DIR__, '/home/') === false) {
                $errorMsg .= " | DSN: " . $dsn . " | Config loaded from: " . __DIR__ . '/../config/config.php';
            }
            
            throw new Exception($errorMsg);
        }
    }
    
    public function getConnection(): PDO {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }
    
    public function execute(string $sql, array $params = []): PDOStatement {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("Erro na execução da query: " . $e->getMessage());
        }
    }
    
    public function fetchOne(string $sql, array $params = []): ?array {
        $stmt = $this->execute($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    public function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function query(string $sql): PDOStatement {
        try {
            return $this->connection->query($sql);
        } catch (PDOException $e) {
            throw new Exception("Erro na execução da query: " . $e->getMessage());
        }
    }
    
    public function exec(string $sql): int {
        try {
            return $this->connection->exec($sql);
        } catch (PDOException $e) {
            throw new Exception("Erro na execução do comando: " . $e->getMessage());
        }
    }
    
    public function getLastInsertId(): string {
        return $this->connection->lastInsertId();
    }
    
    private function __clone() {}
    
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
