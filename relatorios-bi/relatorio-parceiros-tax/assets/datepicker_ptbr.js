/* Traduz os nomes de mês e dia do calendário (dcc.DatePickerSingle do Dash 4.x)
 * para português. O componente usa date-fns/navigator.language e não respeita o
 * locale automaticamente, então substituímos os textos via MutationObserver,
 * escopado APENAS aos elementos do datepicker (não toca no resto da página). */
(function () {
    var MONTHS = {
        January: "Janeiro", February: "Fevereiro", March: "Março", April: "Abril",
        May: "Maio", June: "Junho", July: "Julho", August: "Agosto",
        September: "Setembro", October: "Outubro", November: "Novembro", December: "Dezembro"
    };
    var DAYS = { Su: "Dom", Mo: "Seg", Tu: "Ter", We: "Qua", Th: "Qui", Fr: "Sex", Sa: "Sáb" };

    function translate() {
        var cals = document.querySelectorAll('[class*="datepicker"]');
        for (var c = 0; c < cals.length; c++) {
            var els = cals[c].querySelectorAll("*");
            for (var i = 0; i < els.length; i++) {
                var el = els[i];
                if (el.children.length) continue;              // só folhas de texto
                var t = el.textContent;
                var tt = t.trim();
                if (DAYS[tt]) { el.textContent = DAYS[tt]; continue; }   // cabeçalho de dias
                for (var en in MONTHS) {                        // rótulo do mês
                    if (t.indexOf(en) !== -1) { el.textContent = t.split(en).join(MONTHS[en]); break; }
                }
            }
        }
    }

    function start() {
        translate();
        new MutationObserver(translate).observe(
            document.body, { childList: true, subtree: true, characterData: true }
        );
    }

    if (document.body) start();
    else document.addEventListener("DOMContentLoaded", start);
})();
