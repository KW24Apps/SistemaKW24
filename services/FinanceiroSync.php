<?php
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../dao/ConfiguracaoDAO.php';
require_once __DIR__ . '/../services/BitrixService.php';

class FinanceiroSync {

    // ── Part 1: Demandas ─────────────────────────────────────────────────────
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
    private const F_CONTROLE     = 'ufCrm41_1742082168'; // Controle de Fatura # (lookup key — shared com cat/284/)
    private const F_MIN_SUPORTE  = 'ufCrm41_1767900752';
    private const F_MIN_DEV      = 'ufCrm41_1767900780';
    private const F_DEM_SUPORTE  = 'ufCrm41_1778777514';
    private const F_DEM_DEV      = 'ufCrm41_1778777535';
    private const F_COMPETENCIA  = 'ufCrm41_1742081702';

    // ── Part 2A: Infra Execução ───────────────────────────────────────────────
    // Fonte: SPA 1130 / cat 282 — Produtos de Infra Contratados
    private const BX_INFRA_SRC_ENTITY = 1130;
    private const BX_CAT_INFRA_SRC   = 282;

    // Destino: SPA 1054 / cat 284 — Infra Mensal (Execução)
    private const BX_CAT_INFRA       = 284;
    private const BX_INFRA_STAGE_NEW = 'DT1054_284:NEW'; // confirmado via crm.status.list

    // Campos fonte (SPA 1130 / ufCrm66_*)
    private const S_PRODUTO   = 'ufCrm66_1773322225'; // Produto Contratado (enum)
    private const S_DEPTO     = 'ufCrm66_1773325912'; // Departamento (enum)
    private const S_HORAS_DEV = 'ufCrm66_1773337978'; // Horas Dev
    private const S_HORAS_SUP = 'ufCrm66_1773338012'; // Horas Suporte
    private const S_VH_DEV    = 'ufCrm66_1773337676'; // Valor Hora Dev (money)
    private const S_VH_SUP    = 'ufCrm66_1773337956'; // Valor Hora Suporte (money)
    private const S_DOMINIOS  = 'ufCrm66_1773340437'; // Domínios (string[])
    private const S_QTD_RDP   = 'ufCrm66_1773350132'; // Qtd Usuários RDP

    // Campos destino (SPA 1054 / cat 284 / ufCrm41_*)
    private const I_PRODUTO   = 'ufCrm41_1773942147'; // Produto Contratado (enum)
    private const I_DEPTO     = 'ufCrm41_1737476922'; // Departamento (enum)
    private const I_HORAS_DEV = 'ufCrm41_1742071291'; // Horas Dev
    private const I_HORAS_SUP = 'ufCrm41_1742071347'; // Horas Suporte
    private const I_VH_DEV    = 'ufCrm41_1767928073'; // Valor Hora Dev (money)
    private const I_VH_SUP    = 'ufCrm41_1767928096'; // Valor Hora Suporte (money)
    private const I_DOMINIOS  = 'ufCrm41_1773467121'; // Domínios (string[])
    private const I_QTD_RDP   = 'ufCrm41_1773467142'; // Qtd Usuários RDP

    // Tradução enum: Produto Contratado (SPA 1130 → SPA 1054)
    private const PRODUTO_MAP = [
        28358 => 28422, // Contrato Mensal
        28360 => 28424, // Demandas Avulsas Mensal
        28362 => 28426, // Servidor RDP
        28364 => 28428, // Servidor VM
        28366 => 28430, // Servidor de Dados
        28368 => 28432, // Servidor Sistema Domínio
        28370 => 28434, // Hospedagem de Domínio
        28372 => 28436, // Gestão de E-mail e Sites
        28374 => 28438, // API Validador de CNPJ
        28376 => 28440, // API ClickSign
        28378 => 28442, // API Receita Federal
        28380 => 28444, // API WhatsApp
    ];

    // Tradução enum: Departamento (SPA 1130 → SPA 1054) — null = sem equivalente (campo em branco)
    private const DEPTO_MAP = [
        28382 => 21226, // Grupo Nimbus
        28384 => 21234, // Nimbus Tax
        28386 => 21518, // GN - Financeiro
        28388 => 21228, // GN - Controladoria
        28390 => 21230, // GN - Marketing
        28392 => 21520, // GN - RH
        28394 => 21232, // GN - Núcleo de Produtos
        28396 => 21240, // Capiton
        28398 => 21242, // BGA - Advocacia
        28400 => 21244, // Altura Assessoria
        28402 => 21246, // Nimbus Privacy
        28404 => 21538, // ContaFarma
        28410 => 21250, // Externo
        28804 => null,  // Consisto — sem equivalente no SPA 1054
    ];

    // Mapa de labels para geração de título (SPA 1130 enum ID → nome)
    private const PRODUTO_LABELS = [
        28358 => 'Contrato Mensal',        28360 => 'Demandas Avulsas Mensal',
        28362 => 'Servidor RDP',           28364 => 'Servidor VM',
        28366 => 'Servidor de Dados',      28368 => 'Servidor Sistema Domínio',
        28370 => 'Hospedagem de Domínio',  28372 => 'Gestão de E-mail e Sites',
        28374 => 'API Validador de CNPJ',  28376 => 'API ClickSign',
        28378 => 'API Receita Federal',    28380 => 'API WhatsApp',
    ];

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
            'stageId'          => 'DT1054_210:NEW',
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

    // ── Part 2A: syncInfra ───────────────────────────────────────────────────

    public function syncInfra(?string $period = null): array {
        $this->log = [];

        if (!$this->bitrix->isConfigured()) {
            $this->addLog('Webhook Bitrix24 não configurado');
            return $this->erroInfraRetorno();
        }

        $periodo = $this->calcularPeriodo($period);
        $this->addLog("Infra sync — Período: {$periodo['referencia']} ({$periodo['inicio']->format('Y-m-d')} → {$periodo['fim']->format('Y-m-d')})");

        // 1. Buscar todos os produtos contratados (SPA 1130 / cat 282) — sem filtro de período
        $sourcecards = $this->bitrix->listItems(
            self::BX_INFRA_SRC_ENTITY,
            ['categoryId' => self::BX_CAT_INFRA_SRC],
            [
                'id', 'companyId', 'opportunity',
                self::S_PRODUTO, self::S_DEPTO,
                self::S_HORAS_DEV, self::S_HORAS_SUP,
                self::S_VH_DEV, self::S_VH_SUP,
                self::S_DOMINIOS, self::S_QTD_RDP,
            ],
            0
        );
        $this->addLog("Produtos contratados em cat/282/: " . count($sourcecards));

        if (empty($sourcecards)) {
            return [
                'periodo'      => $periodo['referencia'],
                'inicio'       => $periodo['inicio']->format('Y-m-d'),
                'fim'          => $periodo['fim']->format('Y-m-d'),
                'total_source' => 0,
                'created'      => 0,
                'skipped'      => 0,
                'errors'       => 0,
                'log'          => $this->log,
            ];
        }

        // 2. Carregar índice de idempotência do PostgreSQL (não depende da paginação Bitrix)
        $index = $this->carregarInfraIndex($periodo['referencia']);
        $this->addLog("Registros no índice local para {$periodo['referencia']}: " . count($index));

        // 3. Processar cada produto contratado
        $created      = 0;
        $skipped      = 0;
        $errors       = 0;
        $companyCache = [];

        foreach ($sourcecards as $src) {
            $companyId   = (int)($src['companyId'] ?? 0);
            $produto1130 = (int)($src[self::S_PRODUTO] ?? 0);
            $depto1130   = (int)($src[self::S_DEPTO]   ?? 0);

            if (!$companyId) {
                $this->addLog("SKIP: source id={$src['id']} sem empresa");
                $errors++;
                continue;
            }

            // Traduzir Produto (obrigatório — sem match = ignorar)
            if (!isset(self::PRODUTO_MAP[$produto1130])) {
                $this->addLog("WARN: Produto sem tradução id={$produto1130} empresa={$companyId} — ignorado");
                $errors++;
                continue;
            }
            $produto284 = self::PRODUTO_MAP[$produto1130];

            // Traduzir Departamento (null = Consisto ou desconhecido → campo em branco)
            if ($depto1130 === 0) {
                $depto284 = null;
            } elseif (array_key_exists($depto1130, self::DEPTO_MAP)) {
                $depto284 = self::DEPTO_MAP[$depto1130];
            } else {
                $this->addLog("WARN: Departamento desconhecido id={$depto1130} empresa={$companyId} — campo em branco");
                $depto284 = null;
            }

            // Verificar índice de idempotência
            $depKey = (string)($depto284 ?? '');
            $key    = "{$companyId}|{$produto284}|{$depKey}";

            if (isset($index[$key])) {
                $skipped++;
                $this->addLog("SKIP: cid={$companyId} produto={$produto284} depto={$depKey} card_id={$index[$key]}");
                continue;
            }

            // Nome da empresa (cache para evitar N chamadas)
            if (!isset($companyCache[$companyId])) {
                $co = $this->bitrix->getCompany($companyId);
                $companyCache[$companyId] = $co['TITLE'] ?? "Empresa #{$companyId}";
            }

            $prodLabel = self::PRODUTO_LABELS[$produto1130] ?? "Produto #{$produto1130}";
            $title     = "Infra {$periodo['referencia']} - {$prodLabel} - {$companyCache[$companyId]}";

            // Campos do card de destino
            $fields = [
                'categoryId'       => self::BX_CAT_INFRA,
                'stageId'          => self::BX_INFRA_STAGE_NEW,
                'title'            => $title,
                'companyId'        => $companyId,
                'opportunity'      => $src['opportunity'] ?? 0,
                self::F_CONTROLE   => $periodo['referencia'],
                self::I_PRODUTO    => $produto284,
                self::I_HORAS_DEV  => $src[self::S_HORAS_DEV] ?? '',
                self::I_HORAS_SUP  => $src[self::S_HORAS_SUP] ?? '',
                self::I_VH_DEV     => $src[self::S_VH_DEV]    ?? '',
                self::I_VH_SUP     => $src[self::S_VH_SUP]    ?? '',
                self::I_DOMINIOS   => $src[self::S_DOMINIOS]   ?? [],
                self::I_QTD_RDP    => $src[self::S_QTD_RDP]   ?? 0,
            ];

            // Departamento: omite campo se null (Consisto sem equivalente)
            if ($depto284 !== null) {
                $fields[self::I_DEPTO] = $depto284;
            }

            $newId = $this->bitrix->createItem(self::BX_ENTITY_TYPE, $fields);
            if ($newId) {
                $created++;
                $index[$key] = $newId; // previne duplicata dentro da mesma rodada
                $this->salvarInfraSync($periodo['referencia'], $companyId, $produto284, $depto284, $newId);
                $this->addLog("CRIADO: id={$newId} cid={$companyId} produto={$produto284} depto={$depKey}");
            } else {
                $errors++;
                $this->addLog("ERRO: falha ao criar cid={$companyId} produto={$produto284}");
            }
        }

        return [
            'periodo'      => $periodo['referencia'],
            'inicio'       => $periodo['inicio']->format('Y-m-d'),
            'fim'          => $periodo['fim']->format('Y-m-d'),
            'total_source' => count($sourcecards),
            'created'      => $created,
            'skipped'      => $skipped,
            'errors'       => $errors,
            'log'          => $this->log,
        ];
    }

    private function carregarInfraIndex(string $referencia): array {
        try {
            $pdo  = Database::getInstance()->getConnection();
            $stmt = $pdo->prepare(
                'SELECT company_id, produto_dest, depto_dest FROM financeiro_infra_sync WHERE referencia = ?'
            );
            $stmt->execute([$referencia]);
            $index = [];
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                $depKey = (string)($row['depto_dest'] ?? '');
                $index["{$row['company_id']}|{$row['produto_dest']}|{$depKey}"] = true;
            }
            return $index;
        } catch (\Exception $e) {
            error_log("[FinanceiroSync] carregarInfraIndex: " . $e->getMessage());
            return [];
        }
    }

    private function salvarInfraSync(string $referencia, int $cid, int $prod, ?int $dep, int $bxId): void {
        try {
            $pdo = Database::getInstance()->getConnection();
            $pdo->prepare(
                'INSERT INTO financeiro_infra_sync (referencia, company_id, produto_dest, depto_dest, bitrix_id)
                 VALUES (?, ?, ?, ?, ?)'
            )->execute([$referencia, $cid, $prod, $dep, $bxId]);
        } catch (\Exception $e) {
            // Conflito de unicidade: registro já existe, ignorar
            error_log("[FinanceiroSync] salvarInfraSync: " . $e->getMessage());
        }
    }

    private function erroInfraRetorno(): array {
        return [
            'periodo'      => '',
            'inicio'       => '',
            'fim'          => '',
            'total_source' => 0,
            'created'      => 0,
            'skipped'      => 0,
            'errors'       => 1,
            'log'          => $this->log,
        ];
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
