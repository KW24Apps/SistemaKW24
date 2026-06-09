/**
 * RELATÓRIO TESTE — Embedded BI Report
 * Phase 1: Diagnóstico tab.
 *
 * Depends on: ECharts 5 (loaded lazily from CDN if absent).
 * Entry point: rtInit() — called from relatorio-teste.php after the DOM is ready.
 */

(function (global) {
    'use strict';

    // ── State ─────────────────────────────────────────────────────────────────
    var state = {
        activeTab:    'diagnostico',
        statusFilter: null,   // null = no cross-filter; string = active filter
        donutChart:   null,
        loading:      false,
    };

    // ── Helpers ───────────────────────────────────────────────────────────────
    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function fmtBRL(n) {
        n = parseFloat(n) || 0;
        return 'R$ ' + n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function fmtNum(n) {
        return (parseInt(n) || 0).toLocaleString('pt-BR');
    }

    function el(id) { return document.getElementById(id); }

    function spin(id) {
        var e = el(id);
        if (e) e.innerHTML = '<div class="rt-spin"><i class="fas fa-spinner fa-spin"></i></div>';
    }

    // ── Tab switching ─────────────────────────────────────────────────────────
    function switchTab(tab) {
        state.activeTab = tab;
        document.querySelectorAll('.rt-tab-btn').forEach(function (b) {
            b.classList.toggle('rt-tab-active', b.dataset.tab === tab);
        });
        document.querySelectorAll('.rt-tab-panel').forEach(function (p) {
            p.style.display = p.id === 'rt-panel-' + tab ? 'contents' : 'none';
        });
        if (tab === 'diagnostico') {
            setTimeout(function () {
                if (state.donutChart) state.donutChart.resize();
            }, 50);
        }
    }

    // ── Cross-filter ──────────────────────────────────────────────────────────
    function applyFilter(status) {
        if (state.statusFilter === status) {
            state.statusFilter = null;      // toggle off
        } else {
            state.statusFilter = status;
        }
        // Highlight active row
        document.querySelectorAll('.rt-status-row').forEach(function (r) {
            r.classList.toggle('rt-row-active', r.dataset.status === state.statusFilter);
        });
        loadDiagnostico();
    }

    // ── Render: Stage table (A) ───────────────────────────────────────────────
    function renderEtapaTable(rows) {
        var container = el('rt-etapa-table');
        if (!container) return;
        if (!rows || !rows.length) {
            container.innerHTML = '<p class="rt-empty">Sem dados</p>';
            return;
        }
        var thead = '<thead><tr>'
            + '<th style="text-align:left">Etapa</th>'
            + '<th style="text-align:right">Total</th>'
            + '<th style="text-align:right">Valor</th>'
            + '</tr></thead>';
        var tbody = rows.map(function (r) {
            return '<tr>'
                + '<td style="text-align:left">' + esc(r.etapa_ordenada) + '</td>'
                + '<td style="text-align:right">' + fmtNum(r.total) + '</td>'
                + '<td style="text-align:right">' + fmtBRL(r.valor_soma) + '</td>'
                + '</tr>';
        }).join('');
        container.innerHTML = '<table class="rt-table">' + thead + '<tbody>' + tbody + '</tbody></table>';
    }

    // ── Render: Status table (B) — cross-filter source ────────────────────────
    function renderStatusTable(rows) {
        var container = el('rt-status-table');
        if (!container) return;
        if (!rows || !rows.length) {
            container.innerHTML = '<p class="rt-empty">Sem dados</p>';
            return;
        }
        var thead = '<thead><tr>'
            + '<th style="text-align:left">Status</th>'
            + '<th style="text-align:right">Total</th>'
            + '<th style="text-align:right">Valor</th>'
            + '</tr></thead>';
        var tbody = rows.map(function (r) {
            var active = state.statusFilter === r.status ? ' rt-row-active' : '';
            return '<tr class="rt-status-row' + active + '" data-status="' + esc(r.status) + '" style="cursor:pointer">'
                + '<td style="text-align:left">' + esc(r.status) + '</td>'
                + '<td style="text-align:right">' + fmtNum(r.total) + '</td>'
                + '<td style="text-align:right">' + fmtBRL(r.valor_soma) + '</td>'
                + '</tr>';
        }).join('');
        var table = document.createElement('div');
        table.innerHTML = '<table class="rt-table">' + thead + '<tbody>' + tbody + '</tbody></table>';
        // Bind click on each row
        table.querySelectorAll('.rt-status-row').forEach(function (row) {
            row.addEventListener('click', function () {
                applyFilter(row.dataset.status);
            });
        });
        container.innerHTML = '';
        container.appendChild(table);
    }

    // ── Render: KPI cards (C) ─────────────────────────────────────────────────
    function renderKpis(kpis) {
        var total = el('rt-kpi-total');
        var valor = el('rt-kpi-valor');
        if (total) total.textContent = fmtNum(kpis.total);
        if (valor) valor.textContent = fmtBRL(kpis.valor_soma);
    }

    // ── Render: Donut chart (D) ───────────────────────────────────────────────
    var DONUT_COLORS = [
        '#0DC2FF','#26FF93','#7C3AED','#F59E0B','#EF4444',
        '#10B981','#3B82F6','#EC4899','#F97316','#a0aec0'
    ];

    function renderDonut(rows) {
        var container = el('rt-donut-chart');
        if (!container) return;
        if (state.donutChart) { state.donutChart.dispose(); state.donutChart = null; }
        container.innerHTML = '';

        if (!rows || !rows.length) {
            container.innerHTML = '<p class="rt-empty">Sem dados</p>';
            return;
        }

        if (!global.echarts) return;

        state.donutChart = echarts.init(container);
        var data = rows.map(function (r, i) {
            return {
                name:      r.produto,
                value:     parseInt(r.total) || 0,
                itemStyle: { color: DONUT_COLORS[i % DONUT_COLORS.length] }
            };
        });

        state.donutChart.setOption({
            tooltip: {
                trigger:   'item',
                formatter: function (p) {
                    return '<b>' + esc(p.name) + '</b><br>'
                        + p.value + ' (' + p.percent + '%)';
                }
            },
            legend: {
                orient:    'vertical',
                right:     8,
                top:       'middle',
                type:      'scroll',
                textStyle: { fontSize: 11, color: '#4a5568' },
                formatter: function (name) {
                    return name.length > 32 ? name.substring(0, 32) + '…' : name;
                }
            },
            series: [{
                type:             'pie',
                radius:           ['38%', '65%'],
                center:           ['35%', '50%'],
                data:             data,
                label:            { show: false },
                emphasis: {
                    label: { show: true, fontSize: 13, fontWeight: 'bold', color: '#1a202c' }
                },
                labelLine:        { show: false }
            }]
        });
    }

    // ── Render: Detail table (E) ──────────────────────────────────────────────
    function renderDetalhe(rows) {
        var container = el('rt-detalhe-table');
        if (!container) return;
        if (!rows || !rows.length) {
            container.innerHTML = '<p class="rt-empty">Sem registros</p>';
            return;
        }

        var thead = '<thead><tr>'
            + '<th style="text-align:left;width:70px">ID</th>'
            + '<th style="text-align:left">Cliente</th>'
            + '<th style="text-align:left">Oportunidade</th>'
            + '<th style="text-align:left">Etapa</th>'
            + '<th style="text-align:left">Observações</th>'
            + '<th style="text-align:right;width:130px">Valor</th>'
            + '</tr></thead>';

        var tbody = rows.map(function (r) {
            var idCell = r.link_deal
                ? '<a href="' + esc(r.link_deal) + '" target="_blank" rel="noopener" class="rt-deal-link">'
                    + esc(r.bitrix_id) + '</a>'
                : esc(r.bitrix_id);
            return '<tr>'
                + '<td style="text-align:left">' + idCell + '</td>'
                + '<td style="text-align:left" class="rt-td-clip">' + esc(r.cliente) + '</td>'
                + '<td style="text-align:left" class="rt-td-clip">' + esc(r.oportunidade) + '</td>'
                + '<td style="text-align:left" class="rt-td-clip">' + esc(r.etapa) + '</td>'
                + '<td style="text-align:left" class="rt-td-clip">' + esc(r.observacoes) + '</td>'
                + '<td style="text-align:right">' + fmtBRL(r.valor) + '</td>'
                + '</tr>';
        }).join('');

        container.innerHTML = '<table class="rt-table">' + thead + '<tbody>' + tbody + '</tbody></table>';
    }

    // ── Load data ─────────────────────────────────────────────────────────────
    function loadDiagnostico() {
        if (state.loading) return;
        state.loading = true;

        var icon = el('rt-refresh-icon');
        if (icon) icon.classList.add('fa-spin');

        // Show spinners in all panels
        ['rt-etapa-table', 'rt-status-table', 'rt-detalhe-table'].forEach(spin);
        var kTotal = el('rt-kpi-total'); if (kTotal) kTotal.textContent = '—';
        var kValor = el('rt-kpi-valor'); if (kValor) kValor.textContent = '—';

        var url = '/api/relatorio-teste-dados.php';
        if (state.statusFilter) {
            url += '?status_filter=' + encodeURIComponent(state.statusFilter);
        }

        fetch(url, { credentials: 'same-origin' })
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(function (d) {
                if (!d.sucesso) throw new Error(d.erro || 'Erro desconhecido');
                renderEtapaTable(d.etapa_table);
                renderStatusTable(d.status_table);
                renderKpis(d.kpis);
                renderDonut(d.donut);
                renderDetalhe(d.detalhe);
            })
            .catch(function (err) {
                console.error('[relatorio-teste]', err);
                ['rt-etapa-table', 'rt-status-table', 'rt-detalhe-table'].forEach(function (id) {
                    var e = el(id);
                    if (e) e.innerHTML = '<p class="rt-error"><i class="fas fa-exclamation-triangle"></i> ' + esc(err.message) + '</p>';
                });
            })
            .finally(function () {
                state.loading = false;
                if (icon) icon.classList.remove('fa-spin');
            });
    }

    // ── Resize charts on sidebar toggle ──────────────────────────────────────
    function resizeCharts() {
        if (state.donutChart) state.donutChart.resize();
    }

    window.addEventListener('resize', resizeCharts);
    document.addEventListener('sidebarStateChange', function () {
        setTimeout(resizeCharts, 320);
    });

    // ── Public API ─────────────────────────────────────────────────────────────
    global.rtInit = function () {
        // Tab buttons
        document.querySelectorAll('.rt-tab-btn').forEach(function (btn) {
            btn.addEventListener('click', function () { switchTab(btn.dataset.tab); });
        });

        // Refresh button
        var refreshBtn = el('rt-refresh-btn');
        if (refreshBtn) refreshBtn.addEventListener('click', loadDiagnostico);

        // Show default tab
        switchTab('diagnostico');

        // Load ECharts if absent, then fetch data
        if (global.echarts) {
            loadDiagnostico();
        } else {
            var s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/echarts@5/dist/echarts.min.js';
            s.onload  = loadDiagnostico;
            s.onerror = function () { console.error('[relatorio-teste] ECharts CDN failed'); };
            document.head.appendChild(s);
        }
    };

}(window));
