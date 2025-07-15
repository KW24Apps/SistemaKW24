<?php
/**
 * Controlador para gerenciamento de logs de múltiplos domínios
 */
class LogController
{
    private $domains;
    private $currentDomain;
    
    public function __construct()
    {
        // Carregar configuração de domínios
        $this->domains = include __DIR__ . '/../config/log_domains.php';
        $this->currentDomain = $_GET['domain'] ?? array_key_first($this->domains);
    }
    
    /**
     * Obter lista de domínios disponíveis
     */
    public function getAvailableDomains()
    {
        return $this->domains;
    }
    
    /**
     * Obter domínio atual
     */
    public function getCurrentDomain()
    {
        return $this->currentDomain;
    }
    
    /**
     * Obter informações do domínio atual
     */
    public function getCurrentDomainInfo()
    {
        return $this->domains[$this->currentDomain] ?? null;
    }
    
    /**
     * Obter arquivos de log do domínio atual
     */
    public function getLogFiles()
    {
        $domainInfo = $this->getCurrentDomainInfo();
        if (!$domainInfo) {
            return [];
        }
        
        $logDir = $domainInfo['path'];
        
        // Verificar se o diretório existe
        if (!is_dir($logDir)) {
            return [];
        }
        
        // Buscar arquivos .log no diretório
        $logFiles = glob($logDir . '*.log');
        
        return $logFiles ?: [];
    }
    
    /**
     * Processar logs com filtros
     */
    public function processLogs($date = null, $traceId = null)
    {
        $logFiles = $this->getLogFiles();
        $allLogEntries = [];
        
        foreach ($logFiles as $logFile) {
            if (!is_readable($logFile)) {
                continue;
            }
            
            $content = file_get_contents($logFile);
            $lines = explode("\n", $content);
            
            foreach ($lines as $line) {
                if (empty(trim($line))) continue;
                
                $parsed = $this->parseLogLine($line, $logFile);
                if ($parsed) {
                    // Filtrar por data
                    if ($date && $parsed['date'] !== $date) {
                        continue;
                    }
                    
                    // Filtrar por trace ID
                    if ($traceId && $parsed['traceId'] !== $traceId) {
                        continue;
                    }
                    
                    // Adicionar domínio à entrada
                    $parsed['domain'] = $this->currentDomain;
                    $parsed['domainName'] = $this->getCurrentDomainInfo()['name'];
                    
                    $allLogEntries[] = $parsed;
                }
            }
        }
        
        // Ordenar entradas por timestamp
        usort($allLogEntries, function($a, $b) {
            $timestampCompare = $a['timestamp'] <=> $b['timestamp'];
            if ($timestampCompare !== 0) {
                return $timestampCompare;
            }
            return strcmp($a['sourceFile'], $b['sourceFile']);
        });
        
        return $allLogEntries;
    }
    
    /**
     * Extrair dados da linha de log
     */
    private function parseLogLine($line, $sourceFile)
    {
        // Regex para capturar timestamp, trace ID e conteúdo
        if (preg_match('/\[([\d-]+)\s([\d:]+)\]\s+\[(.*?)\]/', $line, $matches)) {
            return [
                'date' => $matches[1],
                'time' => $matches[2],
                'timestamp' => strtotime($matches[1] . ' ' . $matches[2]),
                'traceId' => $matches[3],
                'content' => $line,
                'sourceFile' => $sourceFile
            ];
        }
        return null;
    }
    
    /**
     * Obter traces únicos
     */
    public function getUniqueTraces()
    {
        $logFiles = $this->getLogFiles();
        $uniqueTraces = [];
        
        foreach ($logFiles as $logFile) {
            if (!is_readable($logFile)) {
                continue;
            }
            
            $content = file_get_contents($logFile);
            $lines = explode("\n", $content);
            
            foreach ($lines as $line) {
                $parsed = $this->parseLogLine($line, $logFile);
                if ($parsed && !isset($uniqueTraces[$parsed['traceId']])) {
                    $uniqueTraces[$parsed['traceId']] = true;
                }
            }
        }
        
        $traces = array_keys($uniqueTraces);
        sort($traces);
        return $traces;
    }
    
    /**
     * Obter datas únicas
     */
    public function getUniqueDates()
    {
        $logFiles = $this->getLogFiles();
        $uniqueDates = [];
        
        foreach ($logFiles as $logFile) {
            if (!is_readable($logFile)) {
                continue;
            }
            
            $content = file_get_contents($logFile);
            $lines = explode("\n", $content);
            
            foreach ($lines as $line) {
                $parsed = $this->parseLogLine($line, $logFile);
                if ($parsed && !isset($uniqueDates[$parsed['date']])) {
                    $uniqueDates[$parsed['date']] = true;
                }
            }
        }
        
        $dates = array_keys($uniqueDates);
        rsort($dates); // Mais recentes primeiro
        return $dates;
    }
    
    /**
     * Validar se o domínio existe
     */
    public function isValidDomain($domain)
    {
        return isset($this->domains[$domain]);
    }
    
    /**
     * Obter lista de arquivos para download
     */
    public function getDownloadFileList()
    {
        $logFiles = $this->getLogFiles();
        $fileList = [];
        
        foreach ($logFiles as $logFile) {
            if (!is_readable($logFile)) {
                continue;
            }
            
            $fileList[] = [
                'name' => basename($logFile),
                'size' => filesize($logFile),
                'modified' => date('Y-m-d H:i:s', filemtime($logFile)),
                'path' => $logFile
            ];
        }
        
        // Ordenar por data de modificação (mais recente primeiro)
        usort($fileList, function($a, $b) {
            return strtotime($b['modified']) - strtotime($a['modified']);
        });
        
        return $fileList;
    }
}
