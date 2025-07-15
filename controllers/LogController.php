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
     * @param string $appFilter App para filtrar (opcional)
     * @return array Entradas de log filtradas e ordenadas
     */
    public function getLogs($startDateFilter = '', $endDateFilter = '', $traceFilter = '', $appFilter = '') {
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
                    
                    // Processar o conteúdo para identificar o app antes de filtrar
                    $content = $parsed['content'];
                    $content = preg_replace('/\[\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}\]\s+\[[^\]]+\]/', '', $content);
                    $functionName = extractFunctionName($content);
                    $app = identifyAppFromLog($content, $functionName);
                    
                    // Filtro por app
                    if ($appFilter && $app !== $appFilter) {
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
     * Obtém listas únicas de datas, Trace IDs e Apps para os filtros.
     */
    public function getFilterOptions() {
        $logFiles = glob($this->logDir . '*.log');
        $uniqueDates = [];
        $uniqueTraces = [];
        $uniqueApps = [];
        $fileCount = 0;

        foreach ($logFiles as $logFile) {
            $content = file_get_contents($logFile);
            if ($content === false) continue;
            
            $fileCount++;
            $lines = explode("\n", $content);
            foreach ($lines as $line) {
                // Usa a função global parseLogLine() do helpers.php
                $parsed = parseLogLine($line, $logFile);
                if ($parsed) {
                    $uniqueDates[$parsed['date']] = true;
                    $uniqueTraces[$parsed['traceId']] = true;
                    
                    // Identificar o app
                    $content = $parsed['content'];
                    $content = preg_replace('/\[\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}\]\s+\[[^\]]+\]/', '', $content);
                    $functionName = extractFunctionName($content);
                    $app = identifyAppFromLog($content, $functionName);
                    $uniqueApps[$app] = true;
                }
            }
        }

        $dates = array_keys($uniqueDates);
        rsort($dates);
        $traces = array_keys($uniqueTraces);
        sort($traces);
        $apps = array_keys($uniqueApps);
        sort($apps);

        return [
            'dates' => $dates, 
            'traces' => $traces, 
            'apps' => $apps,
            'files' => $logFiles,
            'fileCount' => $fileCount
        ];
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