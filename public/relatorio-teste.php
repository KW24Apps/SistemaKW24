<?php
if (!defined('SYSTEM_ACCESS') && !isset($user_data)) {
    header('Location: /public/login.php');
    exit;
}
?>

<!-- Page header -->
<div style="margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between">
    <div>
        <h1 class="page-title" style="margin-bottom:.15rem"><i class="fas fa-flask"></i> Relatório Teste</h1>
        <p style="font-size:.82rem;color:#a0aec0;margin:0">Análise de sincronização — últimos 30 dias</p>
    </div>
    <button id="rt-btn-refresh"
        style="border:1px solid #e2e8f0;background:#fff;border-radius:8px;padding:.5rem 1rem;font-size:.82rem;color:#718096;cursor:pointer;display:inline-flex;align-items:center;gap:.4rem;transition:border-color .15s"
        onmouseover="this.style.borderColor='#0DC2FF'" onmouseout="this.style.borderColor='#e2e8f0'">
        <i class="fas fa-sync-alt" id="rt-icon-refresh" style="font-size:.78rem"></i> Atualizar
    </button>
</div>

<!-- KPI cards -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.75rem">
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1.25rem 1.5rem;display:flex;align-items:center;gap:1rem">
        <div style="width:48px;height:48px;border-radius:10px;background:#e0f7ff;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="fas fa-users" style="font-size:1.2rem;color:#0DC2FF"></i>
        </div>
        <div>
            <div id="rt-kpi-total" style="font-size:1.5rem;font-weight:800;color:#1a202c;line-height:1">
                <i class="fas fa-spinner fa-spin" style="font-size:.9rem;color:#a0aec0"></i>
            </div>
            <div style="font-size:.72rem;color:#a0aec0;margin-top:.25rem;text-transform:uppercase;letter-spacing:.05em">Total Clientes</div>
        </div>
    </div>
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1.25rem 1.5rem;display:flex;align-items:center;gap:1rem">
        <div style="width:48px;height:48px;border-radius:10px;background:#e0fff3;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="fas fa-check-circle" style="font-size:1.2rem;color:#26FF93"></i>
        </div>
        <div>
            <div id="rt-kpi-ativos" style="font-size:1.5rem;font-weight:800;color:#1a202c;line-height:1">
                <i class="fas fa-spinner fa-spin" style="font-size:.9rem;color:#a0aec0"></i>
            </div>
            <div style="font-size:.72rem;color:#a0aec0;margin-top:.25rem;text-transform:uppercase;letter-spacing:.05em">Clientes Ativos</div>
        </div>
    </div>
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1.25rem 1.5rem;display:flex;align-items:center;gap:1rem">
        <div style="width:48px;height:48px;border-radius:10px;background:#e6f2f7;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="fas fa-database" style="font-size:1.2rem;color:#086B8D"></i>
        </div>
        <div>
            <div id="rt-kpi-registros" style="font-size:1.5rem;font-weight:800;color:#1a202c;line-height:1">
                <i class="fas fa-spinner fa-spin" style="font-size:.9rem;color:#a0aec0"></i>
            </div>
            <div style="font-size:.72rem;color:#a0aec0;margin-top:.25rem;text-transform:uppercase;letter-spacing:.05em">Registros (30 dias)</div>
        </div>
    </div>
</div>

<!-- Charts row -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:1rem;margin-bottom:1.25rem">
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1.25rem">
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#a0aec0;margin-bottom:1rem">
            <i class="fas fa-chart-bar" style="color:#0DC2FF;margin-right:.3rem"></i> Sincronizações por dia
        </div>
        <div id="rt-chart-bar" style="height:240px;display:flex;align-items:center;justify-content:center;color:#e2e8f0">
            <i class="fas fa-spinner fa-spin" style="font-size:1.5rem"></i>
        </div>
    </div>
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1.25rem">
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#a0aec0;margin-bottom:1rem">
            <i class="fas fa-chart-pie" style="color:#26FF93;margin-right:.3rem"></i> Clientes por status
        </div>
        <div id="rt-chart-pie" style="height:240px;display:flex;align-items:center;justify-content:center;color:#e2e8f0">
            <i class="fas fa-spinner fa-spin" style="font-size:1.5rem"></i>
        </div>
    </div>
</div>

<!-- Top entities table -->
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px">
    <div style="padding:.75rem 1.25rem;border-bottom:1px solid #f0f4f8">
        <span style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#a0aec0">
            <i class="fas fa-table" style="color:#a0aec0;margin-right:.3rem"></i> Top entidades sincronizadas (30 dias)
        </span>
    </div>
    <div id="rt-tabela" style="padding:.5rem 1rem 1rem">
        <div style="text-align:center;color:#a0aec0;padding:2rem"><i class="fas fa-spinner fa-spin"></i></div>
    </div>
</div>

<script>
(function () {
    var rtBar = null;
    var rtPie = null;

    function fmt(n) {
        n = parseInt(n) || 0;
        if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
        if (n >= 1000)    return (n / 1000).toFixed(1) + 'K';
        return n.toLocaleString('pt-BR');
    }

    function esc(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function renderKpis(kpis) {
        document.getElementById('rt-kpi-total').textContent    = kpis.total_clientes;
        document.getElementById('rt-kpi-ativos').textContent   = kpis.clientes_ativos;
    }

    function renderBar(dias) {
        var el = document.getElementById('rt-chart-bar');
        el.innerHTML = '';
        el.style.display = 'block';
        if (rtBar) { rtBar.dispose(); rtBar = null; }
        rtBar = echarts.init(el);

        var labels = dias.map(function (d) { return (d.dia || '').substring(5); });
        var ops    = dias.map(function (d) { return parseInt(d.operacoes) || 0; });
        var errs   = dias.map(function (d) { return parseInt(d.erros) || 0; });
        var totalReg = dias.reduce(function (acc, d) { return acc + (parseInt(d.registros) || 0); }, 0);
        document.getElementById('rt-kpi-registros').textContent = fmt(totalReg);

        rtBar.setOption({
            tooltip:  { trigger: 'axis', axisPointer: { type: 'shadow' } },
            legend:   { data: ['Operações', 'Erros'], bottom: 0, textStyle: { fontSize: 11 } },
            grid:     { top: 8, right: 8, bottom: 36, left: 8, containLabel: true },
            xAxis:    { type: 'category', data: labels, axisLabel: { fontSize: 10, rotate: labels.length > 15 ? 45 : 0 } },
            yAxis:    { type: 'value', axisLabel: { fontSize: 10 } },
            series: [
                { name: 'Operações', type: 'bar', stack: 'total', data: ops,  itemStyle: { color: '#0DC2FF', borderRadius: [0,0,0,0] } },
                { name: 'Erros',     type: 'bar', stack: 'total', data: errs, itemStyle: { color: '#FC8181', borderRadius: [3,3,0,0] } }
            ]
        });
    }

    function renderPie(kpis) {
        var el = document.getElementById('rt-chart-pie');
        el.innerHTML = '';
        el.style.display = 'block';
        if (rtPie) { rtPie.dispose(); rtPie = null; }
        rtPie = echarts.init(el);

        rtPie.setOption({
            tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
            legend:  { bottom: 0, textStyle: { fontSize: 11 } },
            series: [{
                type:   'pie',
                radius: ['40%', '68%'],
                center: ['50%', '42%'],
                data: [
                    { value: kpis.clientes_ativos,   name: 'Ativos',   itemStyle: { color: '#26FF93' } },
                    { value: kpis.clientes_inativos, name: 'Inativos', itemStyle: { color: '#e2e8f0' } }
                ],
                label:     { show: false },
                emphasis:  { label: { show: true, fontSize: 13, fontWeight: 'bold' } }
            }]
        });
    }

    function renderTabela(entidades) {
        var el = document.getElementById('rt-tabela');
        if (!entidades || !entidades.length) {
            el.innerHTML = '<p style="text-align:center;color:#a0aec0;font-size:.82rem;padding:1.5rem 0">Sem dados de sincronização nos últimos 30 dias.</p>';
            return;
        }
        var rows = entidades.map(function (e, i) {
            return '<tr style="border-bottom:1px solid #f7fafc">'
                + '<td style="padding:.55rem .75rem;font-size:.82rem;color:#1a202c;font-weight:500">'
                +   (i + 1) + '. ' + esc(e.entidade)
                + '</td>'
                + '<td style="padding:.55rem .75rem;font-size:.82rem;color:#718096;text-align:right">'
                +   Number(e.execucoes).toLocaleString('pt-BR') + ' exec.'
                + '</td>'
                + '<td style="padding:.55rem .75rem;font-size:.82rem;font-weight:700;color:#0DC2FF;text-align:right">'
                +   Number(e.total_registros).toLocaleString('pt-BR') + ' regs.'
                + '</td>'
                + '</tr>';
        }).join('');
        el.innerHTML = '<table style="width:100%;border-collapse:collapse">'
            + '<thead><tr style="border-bottom:2px solid #e2e8f0">'
            +   '<th style="padding:.45rem .75rem;font-size:.68rem;text-transform:uppercase;letter-spacing:.05em;color:#a0aec0;text-align:left;font-weight:700">Entidade</th>'
            +   '<th style="padding:.45rem .75rem;font-size:.68rem;text-transform:uppercase;letter-spacing:.05em;color:#a0aec0;text-align:right;font-weight:700">Execuções</th>'
            +   '<th style="padding:.45rem .75rem;font-size:.68rem;text-transform:uppercase;letter-spacing:.05em;color:#a0aec0;text-align:right;font-weight:700">Registros</th>'
            + '</tr></thead>'
            + '<tbody>' + rows + '</tbody></table>';
    }

    function carregar() {
        var icon = document.getElementById('rt-icon-refresh');
        if (icon) icon.classList.add('fa-spin');

        fetch('/api/relatorio-teste-dados.php', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.sucesso) throw new Error(d.erro || 'Erro desconhecido');
                renderKpis(d.kpis);
                renderBar(d.sync_por_dia || []);
                renderPie(d.kpis);
                renderTabela(d.top_entidades || []);
            })
            .catch(function (err) {
                console.error('[relatorio-teste]', err);
                ['rt-kpi-total','rt-kpi-ativos','rt-kpi-registros'].forEach(function(id){
                    var el = document.getElementById(id);
                    if (el) el.innerHTML = '<span style="color:#FC8181;font-size:.8rem">Erro</span>';
                });
            })
            .finally(function () {
                if (icon) icon.classList.remove('fa-spin');
            });
    }

    // Bind refresh button
    var btn = document.getElementById('rt-btn-refresh');
    if (btn) btn.addEventListener('click', carregar);

    // Resize charts when sidebar/window changes
    window.addEventListener('resize', function () {
        if (rtBar) rtBar.resize();
        if (rtPie) rtPie.resize();
    });
    document.addEventListener('sidebarStateChange', function () {
        setTimeout(function () {
            if (rtBar) rtBar.resize();
            if (rtPie) rtPie.resize();
        }, 320);
    });

    // Load ECharts if not already present, then fetch data
    if (window.echarts) {
        carregar();
    } else {
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/echarts@5/dist/echarts.min.js';
        s.onload = carregar;
        s.onerror = function () {
            console.error('[relatorio-teste] Falha ao carregar ECharts');
        };
        document.head.appendChild(s);
    }
}());
</script>
