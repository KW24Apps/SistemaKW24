/* Destaque ao passar o mouse na legenda HTML do donut:
 * ao apontar um item da legenda, a fatia correspondente fica cheia e as demais
 * esmaecem; ao sair da legenda, restaura. O item N corresponde à fatia N (mesma
 * ordem garantida por _donut_sorted no app.py).
 *
 * Estratégia robusta: captura o estado "pristino" das cores ao ENTRAR na legenda
 * e restaura ao SAIR dela; enquanto está dentro, cada item recalcula o destaque a
 * partir do pristino (não depende de parear mouseover/mouseout entre itens). */
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

    document.addEventListener("mouseover", function (e) {
        if (!e.target.closest) return;
        var leg = e.target.closest(".rt-donut-legend");
        if (!leg) return;
        var gd = graphOf(leg);
        if (!gd || !gd.data || !gd.data[0] || !gd.data[0].marker) return;
        if (!leg._rtPristine) {
            leg._rtPristine = (gd.data[0].marker.colors || []).slice();
        }
        var item = e.target.closest(".rt-donut-leg-item");
        if (!item) return;
        var idx = idxOf(item);
        if (idx < 0) return;
        var cols = leg._rtPristine.map(function (c, i) { return i === idx ? c : fade(c); });
        window.Plotly.restyle(gd, { "marker.colors": [cols] });
    });

    document.addEventListener("mouseout", function (e) {
        if (!e.target.closest) return;
        var leg = e.target.closest(".rt-donut-legend");
        if (!leg) return;
        var to = e.relatedTarget;
        if (to && leg.contains(to)) return;   // ainda dentro da legenda → ignora
        var gd = graphOf(leg);
        if (gd && leg._rtPristine) {
            window.Plotly.restyle(gd, { "marker.colors": [leg._rtPristine] });
        }
        leg._rtPristine = null;
    });
})();
