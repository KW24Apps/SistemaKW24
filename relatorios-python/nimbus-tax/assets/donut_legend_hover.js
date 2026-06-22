/* Destaque ao passar o mouse na legenda HTML do donut:
 * ao apontar um item da legenda, a fatia correspondente SALTA pra fora (pull 0.1)
 * e fica cheia, enquanto as demais esmaecem; ao sair da legenda, restaura tudo.
 * O item N corresponde à fatia N (mesma ordem garantida por _donut_sorted no app.py).
 *
 * Captura o estado "pristino" (cores + pull) ao ENTRAR na legenda e restaura ao
 * SAIR — assim funciona com hovers em sequência E preserva o pull do cross-filter
 * (fatia selecionada) quando há um filtro ativo. */
(function () {
    function fade(c) {
        if (!c) return c;
        if (c[0] === "#") {
            var h = c.slice(1);
            return "rgba(" + parseInt(h.slice(0, 2), 16) + "," +
                parseInt(h.slice(2, 4), 16) + "," + parseInt(h.slice(4, 6), 16) + ",0.25)";
        }
        var m = c.match(/rgba?\(([^)]+)\)/);
        if (m) {
            var p = m[1].split(",");
            return "rgba(" + p[0].trim() + "," + p[1].trim() + "," + p[2].trim() + ",0.25)";
        }
        return c;
    }

    function graphOf(leg) {
        var d = leg.closest ? leg.closest(".rt-donut") : null;
        return d ? d.querySelector(".js-plotly-plot") : null;
    }

    function idxOf(item) {
        var p = item.parentNode;
        return p ? Array.prototype.indexOf.call(p.children, item) : -1;
    }

    function pullArray(pull, n) {
        if (Array.isArray(pull)) return pull.slice();
        var v = typeof pull === "number" ? pull : 0;
        return new Array(n).fill(v);
    }

    document.addEventListener("mouseover", function (e) {
        if (!e.target.closest) return;
        var leg = e.target.closest(".rt-donut-legend");
        if (!leg) return;
        var gd = graphOf(leg);
        if (!gd || !gd.data || !gd.data[0] || !gd.data[0].marker) return;
        var n = (gd.data[0].labels || []).length;
        if (!leg._rtPristine) {
            leg._rtPristine = {
                colors: (gd.data[0].marker.colors || []).slice(),
                pull: pullArray(gd.data[0].pull, n),
            };
        }
        var item = e.target.closest(".rt-donut-leg-item");
        if (!item) return;
        var idx = idxOf(item);
        if (idx < 0) return;
        var cols = leg._rtPristine.colors.map(function (c, i) { return i === idx ? c : fade(c); });
        var pull = [];
        for (var i = 0; i < n; i++) pull.push(i === idx ? 0.1 : 0);
        window.Plotly.restyle(gd, { "marker.colors": [cols], "pull": [pull] }, [0]);
    });

    document.addEventListener("mouseout", function (e) {
        if (!e.target.closest) return;
        var leg = e.target.closest(".rt-donut-legend");
        if (!leg) return;
        var to = e.relatedTarget;
        if (to && leg.contains(to)) return;   // ainda dentro da legenda → ignora
        var gd = graphOf(leg);
        if (gd && leg._rtPristine) {
            window.Plotly.restyle(gd, {
                "marker.colors": [leg._rtPristine.colors],
                "pull": [leg._rtPristine.pull],
            }, [0]);
        }
        leg._rtPristine = null;
    });
})();
