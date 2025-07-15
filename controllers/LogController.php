<?php
/**
 * Controlador para gerenciar a lógica de visualização de logs.
 */
class LogController {
    private $logDir = '/home/kw24co49/apis.kw24.com.br/Apps/logs/';

    /**
     * Busca, filtra e ordena as entradas de log.
     */
    public function getLogs($dateFilter, $traceFilter) {
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
                    if ($dateFilter && $parsed['date'] !== $dateFilter) {
                        continue;
                    }
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