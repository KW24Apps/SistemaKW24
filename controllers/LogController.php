<?php
/**
 * Controlador para gerenciar a lógica de visualização de logs.
 */
class LogController {
    private $logDir = '/home/kw24co49/apis.kw24.com.br/Apps/logs/';

    /**
     * Busca, filtra e ordena as entradas de log.
     * 
     * @param string $startDateFilter Data inicial do período (opcional)
     * @param string $endDateFilter Data final do período (opcional)
     * @param string $traceFilter Trace ID para filtrar (opcional)
     * @return array Entradas de log filtradas e ordenadas
     */
    public function getLogs($startDateFilter = '', $endDateFilter = '', $traceFilter = '') {
        $logFiles = glob($this->logDir . '*.log');
        $allLogEntries = [];

        foreach ($logFiles as $logFile) {
            $content = file_get_contents($logFile);
            if ($content === false) continue;

            $lines = explode("\n", $content);

            foreach ($lines as $line) {
                if (empty(trim($line))) continue;

                // Usa a função global parseLogLine() do helpers.php
                $parsed = parseLogLine($line, $logFile);
                if ($parsed) {
                    // Filtro por data - pode ser data única ou período
                    if ($startDateFilter && $endDateFilter) {
                        // Filtro por período
                        if ($parsed['date'] < $startDateFilter || $parsed['date'] > $endDateFilter) {
                            continue;
                        }
                    } else if ($startDateFilter && !$endDateFilter) {
                        // Apenas data inicial
                        if ($parsed['date'] < $startDateFilter) {
                            continue;
                        }
                    } else if (!$startDateFilter && $endDateFilter) {
                        // Apenas data final
                        if ($parsed['date'] > $endDateFilter) {
                            continue;
                        }
                    }
                    
                    // Filtro por trace
                    if ($traceFilter && $parsed['traceId'] !== $traceFilter) {
                        continue;
                    }
                    
                    $allLogEntries[] = $parsed;
                }
            }
        }

        usort($allLogEntries, function($a, $b) {
            return $a['timestamp'] <=> $b['timestamp'];
        });

        return $allLogEntries;
    }

    /**
     * Obtém listas únicas de datas e Trace IDs para os filtros.
     */
    public function getFilterOptions() {
        $logFiles = glob($this->logDir . '*.log');
        $uniqueDates = [];
        $uniqueTraces = [];

        foreach ($logFiles as $logFile) {
            $content = file_get_contents($logFile);
            if ($content === false) continue;
            
            $lines = explode("\n", $content);
            foreach ($lines as $line) {
                // Usa a função global parseLogLine() do helpers.php
                $parsed = parseLogLine($line, $logFile);
                if ($parsed) {
                    $uniqueDates[$parsed['date']] = true;
                    $uniqueTraces[$parsed['traceId']] = true;
                }
            }
        }

        $dates = array_keys($uniqueDates);
        rsort($dates);
        $traces = array_keys($uniqueTraces);
        sort($traces);

        return ['dates' => $dates, 'traces' => $traces];
    }

    /**
     * Obtém a lista de arquivos para download.
     */
    public function getDownloadableFiles() {
        $logFiles = glob($this->logDir . '*.log');
        $fileList = [];

        foreach ($logFiles as $logFile) {
            $fileList[] = [
                'name' => basename($logFile),
                'size' => filesize($logFile),
                'modified' => date('Y-m-d H:i:s', filemtime($logFile)),
            ];
        }

        usort($fileList, function($a, $b) {
            return strtotime($b['modified']) - strtotime($a['modified']);
        });

        return $fileList;
    }
}