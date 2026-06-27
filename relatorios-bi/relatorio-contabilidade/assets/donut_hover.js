/* Hover do donut por vendedor (go.Barpolar — 2 anéis).
 *
 * Ao apontar um vendedor (na LEGENDA ou numa FATIA do donut), a barra interna
 * dele E suas barras externas (interno/indicado) saltam pra fora RADIALMENTE —
 * a barra é empurrada ao longo do seu próprio theta aumentando base/r (pull
 * exatamente radial, sem o efeito "torto" dos pies aninhados). As demais
 * esmaecem. Mouse-out restaura.
 *
 * Mapeamento (garantido por _vend_donut_rows no app.py):
 *   vendedor i  ↔  barra interna i (trace 0)  ↔  barras externas 2i / 2i+1 (trace 1)
 *
 * trace 0 = anel INTERNO (N barras) · trace 1 = anel EXTERNO (2N barras)
 * trace 2 = textos de % (Scatterpolar, hoverinfo skip — não dispara pull).
 *
 * Além do hover, este arquivo desenha os NOMES dos vendedores FORA do donut
 * (linhas-guia + rótulos em SVG puro) no plotly_afterplot — ver drawLabels(). */
(function () {
    var DR = 0.06;   // deslocamento radial do pull (fração do raio)

    function fade(c) {
        if (!c) return c;
        if (c[0] === "#") {
            var h = c.slice(1);
            return "rgba(" + parseInt(h.slice(0, 2), 16) + "," +
                parseInt(h.slice(2, 4), 16) + "," + parseInt(h.slice(4, 6), 16) + ",0.22)";
        }
        var m = c.match(/rgba?\(([^)]+)\)/);
        if (m) {
            var p = m[1].split(",");
            return "rgba(" + p[0].trim() + "," + p[1].trim() + "," + p[2].trim() + ",0.22)";
        }
        return c;
    }

    function donutGd() {
        var d = document.querySelector(".ct-donut");
        return d ? d.querySelector(".js-plotly-plot") : null;
    }

    function ready(gd) {
        return gd && gd.data && gd.data[0] && gd.data[1] &&
            gd.data[0].marker && gd.data[1].marker &&
            Array.isArray(gd.data[0].r) && Array.isArray(gd.data[1].r);
    }

    function snap(gd) {
        return {
            b0: gd.data[0].base.slice(), r0: gd.data[0].r.slice(), c0: gd.data[0].marker.color.slice(),
            b1: gd.data[1].base.slice(), r1: gd.data[1].r.slice(), c1: gd.data[1].marker.color.slice(),
        };
    }

    function applyPull(gd, idx) {
        if (!ready(gd)) return;
        if (!gd._ctPr) gd._ctPr = snap(gd);
        var pr = gd._ctPr;
        var n = pr.b0.length;        // N (interno)
        var m = pr.b1.length;        // 2N (externo)
        if (idx < 0 || idx >= n) return;

        var b0 = [], r0 = [], c0 = [];
        for (var i = 0; i < n; i++) {
            var on = i === idx;
            b0.push(pr.b0[i] + (on ? DR : 0));
            r0.push(pr.r0[i] + (on ? DR : 0));
            c0.push(on ? pr.c0[i] : fade(pr.c0[i]));
        }
        var b1 = [], r1 = [], c1 = [];
        for (var j = 0; j < m; j++) {
            var on2 = Math.floor(j / 2) === idx;
            b1.push(pr.b1[j] + (on2 ? DR : 0));
            r1.push(pr.r1[j] + (on2 ? DR : 0));
            c1.push(on2 ? pr.c1[j] : fade(pr.c1[j]));
        }
        window.Plotly.restyle(gd, {
            base: [b0, b1], r: [r0, r1], "marker.color": [c0, c1],
        }, [0, 1]);
    }

    function restore(gd) {
        if (gd && gd._ctPr) {
            var pr = gd._ctPr;
            window.Plotly.restyle(gd, {
                base: [pr.b0, pr.b1], r: [pr.r0, pr.r1], "marker.color": [pr.c0, pr.c1],
            }, [0, 1]);
            gd._ctPr = null;
        }
    }

    // ── Hover na LEGENDA (índice = posição do item entre os itens de vendedor) ──
    document.addEventListener("mouseover", function (e) {
        if (!e.target.closest) return;
        var leg = e.target.closest(".ct-donut-legend");
        if (!leg) return;
        var item = e.target.closest(".ct-leg-item");
        if (!item) return;                       // rodapé (.ct-leg-foot) é ignorado
        var items = leg.querySelectorAll(".ct-leg-item");
        var idx = Array.prototype.indexOf.call(items, item);
        if (idx >= 0) applyPull(donutGd(), idx);
    });

    document.addEventListener("mouseout", function (e) {
        if (!e.target.closest) return;
        var leg = e.target.closest(".ct-donut-legend");
        if (!leg) return;
        var to = e.relatedTarget;
        if (to && leg.contains(to)) return;      // ainda dentro da legenda → ignora
        restore(donutGd());
    });

    // ── Nomes dos vendedores FORA do donut (linhas-guia + rótulos em SVG puro) ──
    // Sem textposition/leader-line nativo do Plotly: lemos a geometria do polar do
    // SVG renderizado e desenhamos tudo à mão. Dados via gd._fullLayout.meta
    // (nome, ângulo da fatia, cor) — sem aproximação paper↔polar.
    var SVGNS = "http://www.w3.org/2000/svg";

    function metaOf(gd) {
        var fl = gd._fullLayout || gd.layout || {};
        return fl.meta || (gd.layout && gd.layout.meta) || null;
    }

    function drawLabels(gd) {
        var meta = metaOf(gd);
        if (!meta || !meta.vendedores || !meta.vendedores.length) return;

        // 1. Geometria do polar a partir do SVG renderizado.
        var bg = gd.querySelector(".polar .plotbg rect, .polar .bg");
        if (!bg) bg = gd.querySelector('[class*="polar"] [class*="bg"]');
        if (!bg) return;
        var b;
        try { b = bg.getBBox(); } catch (e) { return; }
        if (!b || (!b.width && !b.height)) return;
        var cx = b.x + b.width / 2;
        var cy = b.y + b.height / 2;
        var R = Math.min(b.width, b.height) / 2;   // R = raio do disco polar = dados r=1.0

        // 2. Remove o grupo de rótulos anterior (evita duplicar a cada afterplot).
        var prev = gd.querySelector(".ct-donut-labels");
        if (prev && prev.parentNode) prev.parentNode.removeChild(prev);

        // 3. <g> novo no MESMO <svg> do polar (coords no mesmo espaço do getBBox).
        var svg = bg.ownerSVGElement || gd.querySelector("svg");
        if (!svg) return;
        var g = document.createElementNS(SVGNS, "g");
        g.setAttribute("class", "ct-donut-labels");
        svg.appendChild(g);

        var rOuter = R * (meta.r_outer_top || 0.96);

        // 4. Uma linha-guia + rótulo por vendedor.
        meta.vendedores.forEach(function (v) {
            var theta = v.theta;                              // graus, horário a partir do topo
            var rad = (theta - 90) * Math.PI / 180;           // → ângulo padrão (x dir., y p/ baixo no SVG)
            var ca = Math.cos(rad), sa = Math.sin(rad);

            var x0 = cx + rOuter * ca, y0 = cy + rOuter * sa;          // início (borda do anel externo)
            var x1 = cx + (rOuter + 28) * ca, y1 = cy + (rOuter + 28) * sa;  // ponta do segmento radial

            var rightHalf = (theta > 270 || theta <= 90);
            var x2 = rightHalf ? x1 + 16 : x1 - 16;
            var y2 = y1;
            var anchor = rightHalf ? "start" : "end";

            g.appendChild(mkLine(x0, y0, x1, y1));   // segmento radial (angulado)
            g.appendChild(mkLine(x1, y1, x2, y2));   // segmento horizontal

            // 5. Rótulo — mesma fonte do nome na legenda (.ct-leg-name):
            //    Inter, 12px, weight 600, fill #1f2937.
            var t = document.createElementNS(SVGNS, "text");
            t.setAttribute("x", x2);
            t.setAttribute("y", y2);
            t.setAttribute("dy", "0.35em");
            t.setAttribute("text-anchor", anchor);
            t.setAttribute("font-family", "Inter, sans-serif");
            t.setAttribute("font-size", "12px");
            t.setAttribute("font-weight", "600");
            t.setAttribute("fill", "#1f2937");
            t.textContent = v.name;
            g.appendChild(t);
        });
    }

    function mkLine(x1, y1, x2, y2) {
        var ln = document.createElementNS(SVGNS, "line");
        ln.setAttribute("x1", x1); ln.setAttribute("y1", y1);
        ln.setAttribute("x2", x2); ln.setAttribute("y2", y2);
        ln.setAttribute("stroke", "#64748b");
        ln.setAttribute("stroke-width", "1");
        ln.setAttribute("fill", "none");
        return ln;
    }

    // ── Hover nas FATIAS do donut + rótulos externos (eventos do Plotly) ──────
    function bind() {
        var gd = donutGd();
        if (gd && !gd._ctBound && typeof gd.on === "function") {
            gd._ctBound = true;
            gd.on("plotly_hover", function (d) {
                var p = d && d.points && d.points[0];
                if (!p) return;
                var idx = -1;
                if (p.curveNumber === 0) idx = p.pointNumber;
                else if (p.curveNumber === 1) idx = Math.floor(p.pointNumber / 2);
                if (idx >= 0) applyPull(gd, idx);
            });
            gd.on("plotly_unhover", function () { restore(gd); });
            // Redesenha os rótulos a cada render (1ª carga + re-render por filtro/data).
            gd.on("plotly_afterplot", function () { drawLabels(gd); });
            drawLabels(gd);   // o afterplot inicial pode ter ocorrido antes do bind
        }
    }
    // O gráfico é (re)criado de forma assíncrona pelo Dash; tentamos ligar até achar.
    setInterval(bind, 700);
    document.addEventListener("DOMContentLoaded", bind);
})();
