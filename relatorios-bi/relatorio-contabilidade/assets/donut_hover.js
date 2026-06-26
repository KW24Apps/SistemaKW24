/* Destaque ao passar o mouse na legenda HTML do donut sunburst (dois anéis).
 *
 * Ao apontar o vendedor N da legenda, AMBOS os arcos daquele vendedor saltam pra
 * fora (pull) e ficam cheios, enquanto os demais esmaecem; ao sair da legenda,
 * restaura tudo. Mapeamento de índices (garantido por _vend_donut_rows no app.py):
 *   item N da legenda  ↔  fatia interna N (trace 1)  ↔  fatias externas 2N e 2N+1 (trace 0)
 *
 * Estado "pristino" (cores + pull dos dois traces) é capturado ao ENTRAR na
 * legenda e restaurado ao SAIR — assim hovers em sequência funcionam E o
 * pull/dim do cross-filter (servidor) é preservado quando há filtro ativo.
 *
 * trace 0 = anel EXTERNO (2N fatias) · trace 1 = anel INTERNO (N fatias). */
(function () {
    var PULL = 0.12;

    function fade(c) {
        if (!c) return c;
        if (c[0] === "#") {
            var h = c.slice(1);
            return "rgba(" + parseInt(h.slice(0, 2), 16) + "," +
                parseInt(h.slice(2, 4), 16) + "," + parseInt(h.slice(4, 6), 16) + ",0.20)";
        }
        var m = c.match(/rgba?\(([^)]+)\)/);
        if (m) {
            var p = m[1].split(",");
            return "rgba(" + p[0].trim() + "," + p[1].trim() + "," + p[2].trim() + ",0.20)";
        }
        return c;
    }

    function graphOf(leg) {
        var d = leg.closest ? leg.closest(".ct-donut") : null;
        return d ? d.querySelector(".js-plotly-plot") : null;
    }

    function idxOf(item) {
        var p = item.parentNode;
        return p ? Array.prototype.indexOf.call(p.children, item) : -1;
    }

    function pullArr(pull, n) {
        if (Array.isArray(pull)) return pull.slice();
        var v = typeof pull === "number" ? pull : 0;
        return new Array(n).fill(v);
    }

    function ready(gd) {
        return gd && gd.data && gd.data[0] && gd.data[1] &&
            gd.data[0].marker && gd.data[1].marker;
    }

    document.addEventListener("mouseover", function (e) {
        if (!e.target.closest) return;
        var leg = e.target.closest(".ct-donut-legend");
        if (!leg) return;
        var gd = graphOf(leg);
        if (!ready(gd)) return;

        var outerN = (gd.data[0].labels || []).length;   // 2N
        var innerN = (gd.data[1].labels || []).length;    // N

        if (!leg._ctPristine) {
            leg._ctPristine = {
                outerColors: (gd.data[0].marker.colors || []).slice(),
                innerColors: (gd.data[1].marker.colors || []).slice(),
                outerPull: pullArr(gd.data[0].pull, outerN),
                innerPull: pullArr(gd.data[1].pull, innerN),
            };
        }

        var item = e.target.closest(".ct-leg-item");
        if (!item) return;
        var idx = idxOf(item);
        if (idx < 0 || idx >= innerN) return;

        var pr = leg._ctPristine;
        var innerColors = pr.innerColors.map(function (c, i) { return i === idx ? c : fade(c); });
        var innerPull = [];
        for (var i = 0; i < innerN; i++) innerPull.push(i === idx ? PULL : 0);

        var outerColors = pr.outerColors.map(function (c, j) { return Math.floor(j / 2) === idx ? c : fade(c); });
        var outerPull = [];
        for (var j = 0; j < outerN; j++) outerPull.push((j === 2 * idx || j === 2 * idx + 1) ? PULL : 0);

        window.Plotly.restyle(gd, {
            "marker.colors": [outerColors, innerColors],
            "pull": [outerPull, innerPull],
        }, [0, 1]);
    });

    document.addEventListener("mouseout", function (e) {
        if (!e.target.closest) return;
        var leg = e.target.closest(".ct-donut-legend");
        if (!leg) return;
        var to = e.relatedTarget;
        if (to && leg.contains(to)) return;   // ainda dentro da legenda → ignora
        var gd = graphOf(leg);
        if (gd && leg._ctPristine) {
            window.Plotly.restyle(gd, {
                "marker.colors": [leg._ctPristine.outerColors, leg._ctPristine.innerColors],
                "pull": [leg._ctPristine.outerPull, leg._ctPristine.innerPull],
            }, [0, 1]);
        }
        leg._ctPristine = null;
    });
})();
