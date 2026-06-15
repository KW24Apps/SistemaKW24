<?php
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../dao/ConfiguracaoDAO.php';
require_once __DIR__ . '/../services/BitrixService.php';

class FinanceiroSync {

    private const BX_ENTITY_TYPE  = 1054;
    private const BX_CAT_DEMANDAS = 208;
    private const BX_CAT_FINANC   = 210;

    private const TIPOS_SUPORTE = [21204, 21206];
    private const TIPOS_DEV     = [21208, 21210];
    private const TIPOS_FATURA  = [21204, 21206, 21208, 21210];

    // Campos do card de demanda (category 208)
    private const F_TIPO_CHAMADO = 'ufCrm41_1737476320';
    private const F_TEMPO_ATUAL  = 'ufCrm41_1751475675';
    private const F_DATA_FIN     = 'ufCrm41_1778777816';
    private const F_FATURA_LINK  = 'ufCrm41_1767897101';

    // Campos do card financeiro (category 210)
    private const F_CONTROLE     = 'ufCrm41_1742082168'; // Controle de Fatura # (lookup key)
    private const F_MIN_SUPORTE  = 'ufCrm41_1767900752';
    private const F_MIN_DEV      = 'ufCrm41_1767900780';
    private const F_DEM_SUPORTE  = 'ufCrm41_1778777514'; // Demandas suporte (CRM relation)
    private const F_DEM_DEV      = 'ufCrm41_1778777535'; // Demandas desenvolvimento (CRM relation)
    private const F_COMPETENCIA  = 'ufCrm41_1742081702';

    private BitrixService $bitrix;
    private int $diaInicio;
    private array $log = [];

    public function __construct() {
        $dao = new ConfiguracaoDAO();
        $this->diaInicio = max(1, min(28, (int)($dao->get('financeiro_dia_inicio') ?? 27)));
        $this->bitrix    = new BitrixService();
    }

    public function run(?string $period = null): array {
        $this->log = [];

        if (!$this->bitrix->isConfigured()) {
            return $this->erroRetorno('Webhook Bitrix24 não configurado em configuracoes_sistema');
        }

        $periodo = $this->calcularPeriodo($period);
        $this->addLog("Período: {$periodo['referencia']} ({$periodo['inicio']->format('Y-m-d')} → {$periodo['fim']->format('Y-m-d')})");

        $demandas = $this->buscarDemandas($periodo);
        $this->addLog("Demandas faturáveis encontradas: " . count($demandas));

        if (empty($demandas)) {
            return [
                'periodo'        => $periodo['referencia'],
                'inicio'         => $periodo['inicio']->format('Y-m-d'),
                'fim'            => $periodo['fim']->format('Y-m-d'),
                'demandas_total' => 0,
                'empresas'       => 0,
                'atualizados'    => 0,
                'erros'          => 0,
                'log'            => $this->log,
            ];
        }

        // Agrupar por empresa
        $porEmpresa = [];
        foreach ($demandas as $d) {
            $cid = (int)($d['companyId'] ?? 0);
            if ($cid) $porEmpresa[$cid][] = $d;
        }
        $this->addLog("Empresas distintas: " . count($porEmpresa));

        $atualizados = 0;
        $erros       = 0;
        foreach ($porEmpresa as $companyId => $dems) {
            try {
                $this->processarEmpresa($companyId, $dems, $periodo);
                $atualizados++;
            } catch (Exception $e) {
                $erros++;
                $this->addLog("ERRO empresa {$companyId}: " . $e->getMessage());
            }
        }

        return [
            'periodo'        => $periodo['referencia'],
            'inicio'         => $periodo['inicio']->format('Y-m-d'),
            'fim'            => $periodo['fim']->format('Y-m-d'),
            'demandas_total' => count($demandas),
            'empresas'       => count($porEmpresa),
            'atualizados'    => $atualizados,
            'erros'          => $erros,
            'log'            => $this->log,
        ];
    }

    private function calcularPeriodo(?string $period): array {
        $diaInicio = $this->diaInicio;

        if ($period !== null && preg_match('/^(\d{4})-(\d{2})$/', $period, $m)) {
            $refYear  = (int)$m[1];
            $refMonth = (int)$m[2];
            $diaFim   = $diaInicio - 1;

            $fim = new DateTime(sprintf('%04d-%02d-%02d', $refYear, $refMonth, $diaFim));
            $fim->setTime(23, 59, 59);

            $inicioMes = $refMonth - 1;
            $inicioAno = $refYear;
            if ($inicioMes < 1) { $inicioMes = 12; $inicioAno--; }
            $inicio = new DateTime(sprintf('%04d-%02d-%02d', $inicioAno, $inicioMes, $diaInicio));

            return [
                'inicio'     => $inicio,
                'fim'        => $fim,
                'referencia' => sprintf('%02d/%04d', $refMonth, $refYear),
                'refDate'    => sprintf('%04d-%02d-01', $refYear, $refMonth),
            ];
        }

        // Período atual (mesma lógica do webhook)
        $hoje = new DateTime();
        $dia  = (int)$hoje->format('d');
        $mes  = (int)$hoje->format('m');
        $ano  = (int)$hoje->format('Y');

        if ($dia >= $diaInicio) {
            $inicioMes = $mes;
            $inicioAno = $ano;
        } else {
            $inicioMes = $mes - 1;
            $inicioAno = $ano;
            if ($inicioMes < 1) { $inicioMes = 12; $inicioAno--; }
        }

        $inicio = new DateTime(sprintf('%04d-%02d-%02d', $inicioAno, $inicioMes, $diaInicio));
        $fim    = clone $inicio;
        $fim->add(new DateInterval('P1M'));
        $fim->sub(new DateInterval('P1D'));
        $fim->setTime(23, 59, 59);

        $refMes = (int)$fim->format('m');
        $refAno = (int)$fim->format('Y');

        return [
            'inicio'     => $inicio,
            'fim'        => $fim,
            'referencia' => sprintf('%02d/%04d', $refMes, $refAno),
            'refDate'    => sprintf('%04d-%02d-01', $refAno, $refMes),
        ];
    }

    private function buscarDemandas(array $periodo): array {
        $inicioStr = $periodo['inicio']->format('Y-m-d\T00:00:00');
        $fimStr    = $periodo['fim']->format('Y-m-d\T23:59:59');

        $todas = $this->bitrix->listItems(
            self::BX_ENTITY_TYPE,
            [
                'categoryId'                       => self::BX_CAT_DEMANDAS,
                '>=' . self::F_DATA_FIN            => $inicioStr,
                '<=' . self::F_DATA_FIN            => $fimStr,
            ],
            [
                'id', 'title', 'companyId', 'stageId',
                self::F_TIPO_CHAMADO,
                self::F_TEMPO_ATUAL,
                self::F_DATA_FIN,
                self::F_FATURA_LINK,
            ],
            0 // sem limite — sync precisa de todos
        );

        return array_values(array_filter($todas, function ($d) {
            $tipo = (int)($d[self::F_TIPO_CHAMADO] ?? 0);
            return in_array($tipo, self::TIPOS_FATURA, true);
        }));
    }

    private function processarEmpresa(int $companyId, array $demandas, array $periodo): void {
        $minSuporte = 0;
        $minDev     = 0;
        $idsSuporte = [];
        $idsDev     = [];

        foreach ($demandas as $d) {
            $tipo = (int)($d[self::F_TIPO_CHAMADO] ?? 0);
            $mins = (int)($d[self::F_TEMPO_ATUAL]  ?? 0);
            $id   = (int)$d['id'];

            if (in_array($tipo, self::TIPOS_SUPORTE, true)) {
                $minSuporte  += $mins;
                $idsSuporte[] = $id;
            } else {
                $minDev  += $mins;
                $idsDev[] = $id;
            }
        }

        $financialId = $this->encontrarOuCriarCard($companyId, $periodo);

        // REPLACE completo — sobrescreve totais e listas de demanda
        $ok = $this->bitrix->updateItem(self::BX_ENTITY_TYPE, $financialId, [
            self::F_MIN_SUPORTE => (string)$minSuporte,
            self::F_MIN_DEV     => (string)$minDev,
            self::F_DEM_SUPORTE => $idsSuporte,
            self::F_DEM_DEV     => $idsDev,
        ]);

        if (!$ok) {
            throw new Exception("Falha ao atualizar card financeiro {$financialId}");
        }

        $this->addLog("Empresa {$companyId}: suporte={$minSuporte}min dev={$minDev}min card={$financialId}");

        // Vincular demandas ao card financeiro (corrige links errados ou ausentes)
        foreach ($demandas as $d) {
            $existing   = (array)($d[self::F_FATURA_LINK] ?? []);
            $existingId = (int)($existing[0] ?? 0);
            if ($existingId !== $financialId) {
                $this->bitrix->updateItem(self::BX_ENTITY_TYPE, (int)$d['id'], [
                    self::F_FATURA_LINK => [$financialId],
                ]);
            }
        }
    }

    private function encontrarOuCriarCard(int $companyId, array $periodo): int {
        // Lookup via F_CONTROLE — campo dedicado, imune a renomeação de título
        $cards = $this->bitrix->listItems(self::BX_ENTITY_TYPE, [
            'categoryId'     => self::BX_CAT_FINANC,
            'companyId'      => $companyId,
            self::F_CONTROLE => $periodo['referencia'],
        ], ['id', self::F_CONTROLE]);

        if (!empty($cards)) {
            return (int)$cards[0]['id'];
        }

        $company     = $this->bitrix->getCompany($companyId);
        $companyName = $company['TITLE'] ?? "Empresa #{$companyId}";
        $title       = "Fatura Referente a {$periodo['referencia']} - {$companyName}";

        $id = $this->bitrix->createItem(self::BX_ENTITY_TYPE, [
            'categoryId'       => self::BX_CAT_FINANC,
            'title'            => $title,
            'companyId'        => $companyId,
            self::F_COMPETENCIA => $periodo['refDate'],
            self::F_CONTROLE   => $periodo['referencia'],
        ]);

        if (!$id) {
            throw new Exception("Falha ao criar card financeiro empresa {$companyId}");
        }

        $this->addLog("Card financeiro criado id={$id} empresa={$companyId}");
        return $id;
    }

    private function erroRetorno(string $msg): array {
        $this->addLog($msg);
        return [
            'periodo'        => '',
            'inicio'         => '',
            'fim'            => '',
            'demandas_total' => 0,
            'empresas'       => 0,
            'atualizados'    => 0,
            'erros'          => 1,
            'log'            => $this->log,
        ];
    }

    private function addLog(string $msg): void {
        $ts = date('H:i:s');
        $this->log[] = "[{$ts}] {$msg}";
        error_log("[FinanceiroSync] {$msg}");
    }
}
