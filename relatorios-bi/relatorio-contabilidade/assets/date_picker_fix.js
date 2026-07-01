/* date_picker_fix.js — dropdown de mês para o dcc.DatePickerSingle (react-dates,
 * Dash 2.18). Nessa versão o calendário só tem setas ← → para trocar de mês; aqui
 * adicionamos um seletor de mês em PT-BR clicável no título, mantendo o calendário
 * aberto para o usuário escolher o dia.
 *
 * Responsabilidades:
 *   1) DROPDOWN: ao clicar no título do mês visível (.CalendarMonth_caption), abre
 *      uma lista dos 12 meses (PT-BR). Ao escolher, navega até o mês alvo clicando
 *      programaticamente nas setas ← → (mantendo o mesmo ano).
 *   2) MANTER ABERTO: a lista é anexada DENTRO do popup (.SingleDatePicker_picker),
 *      então o OutsideClickHandler do react-dates a considera "clique interno" e não
 *      fecha. Reforço: stopPropagation no mousedown dos elementos do calendário.
 *   3) PT-BR: traduz os títulos de mês (o datepicker_ptbr.js só cobre o datepicker
 *      novo/Radix, não o react-dates do Dash 2.18).
 *
 * Usa DELEGAÇÃO de eventos (um listener no document) para não depender do momento
 * em que o react-dates re-renderiza os meses. Não conflita com datepicker_close.js:
 * aquele só fecha o PAINEL em clique FORA de .rt-datawrap; o calendário fica dentro.
 */
(function () {
    var EN = ["January", "February", "March", "April", "May", "June",
              "July", "August", "September", "October", "November", "December"];
    var PT = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho",
              "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];

    // "June 2026" | "Junho 2026" → índice 0-11 (aceita EN e PT). -1 se não achar.
    function monthIdx(txt) {
        var t = (txt || "").trim();
        for (var i = 0; i < 12; i++) {
            if (t.indexOf(EN[i]) !== -1 || t.indexOf(PT[i]) !== -1) return i;
        }
        return -1;
    }

    // Traduz o rótulo do mês para PT-BR (idempotente: se já está em PT, não mexe).
    function toPt(strongEl) {
        var t = strongEl.textContent;
        for (var i = 0; i < 12; i++) {
            if (t.indexOf(EN[i]) !== -1) { strongEl.textContent = t.split(EN[i]).join(PT[i]); return; }
        }
    }

    function translateCaptions() {
        var all = document.querySelectorAll(".CalendarMonth_caption strong");
        for (var i = 0; i < all.length; i++) toPt(all[i]);
    }

    function closeDropdown() {
        var dd = document.querySelector(".rt-monthdd");
        if (dd && dd.parentNode) dd.parentNode.removeChild(dd);
    }

    // Navega até o mês alvo (mesmo ano) clicando na seta. ADAPTATIVO: relê o mês
    // visível a cada passo e só para ao chegar no alvo — assim cliques engolidos
    // pela animação (~200ms) do react-dates não fazem a navegação parar no meio.
    function goToMonth(targetIdx) {
        if (targetIdx < 0) return;
        var guard = 0;   // trava anti-loop (ex.: min/max date impedindo navegar)
        function step() {
            var visCap = document.querySelector('.CalendarMonth[data-visible="true"] .CalendarMonth_caption strong');
            if (!visCap) return;
            var cur = monthIdx(visCap.textContent);
            if (cur < 0 || cur === targetIdx || guard++ > 24) return;
            var sel = targetIdx > cur
                ? '.DayPickerNavigation_button[aria-label*="next"]'
                : '.DayPickerNavigation_button[aria-label*="previous"]';
            var btn = document.querySelector(sel);
            if (btn) btn.click();
            setTimeout(step, 260);   // > animação do react-dates, p/ o clique valer
        }
        setTimeout(step, 0);
    }

    // Monta a lista de meses ancorada logo abaixo do título, DENTRO do popup.
    function openDropdown(captionEl) {
        closeDropdown();
        var cur = monthIdx(captionEl.textContent);
        var root = captionEl.closest(".SingleDatePicker_picker")
                || captionEl.closest(".SingleDatePicker") || document.body;
        if (getComputedStyle(root).position === "static") root.style.position = "relative";

        var dd = document.createElement("div");
        dd.className = "rt-monthdd";
        // Layout VERTICAL (sequência de cima p/ baixo): 2 colunas × 6 linhas com
        // fluxo por coluna → Jan–Jun na 1ª coluna, Jul–Dez na 2ª (nunca esquerda→
        // direita). Aplicado inline p/ garantir a ordem independente do style.css.
        dd.style.display = "grid";
        dd.style.gridAutoFlow = "column";
        dd.style.gridTemplateRows = "repeat(6, auto)";
        dd.style.gridTemplateColumns = "repeat(2, 1fr)";
        for (var i = 0; i < 12; i++) {
            (function (idx) {
                var it = document.createElement("div");
                it.className = "rt-monthdd-item" + (idx === cur ? " rt-monthdd-active" : "");
                it.textContent = PT[idx];
                it.addEventListener("click", function (ev) {
                    ev.stopPropagation();
                    closeDropdown();
                    goToMonth(idx);
                });
                dd.appendChild(it);
            })(i);
        }
        var cr = captionEl.getBoundingClientRect();
        var rr = root.getBoundingClientRect();
        dd.style.top = (cr.bottom - rr.top + 4) + "px";
        dd.style.left = (cr.left - rr.left) + "px";
        root.appendChild(dd);
    }

    // (2) Reforço p/ manter aberto: impede o mousedown dentro do calendário de
    // subir até o handler de "outside click" do react-dates. (A seleção do DIA usa
    // click, não mousedown, então isso não interfere na escolha do dia.)
    document.addEventListener("mousedown", function (e) {
        if (e.target.closest &&
            e.target.closest(".DayPicker, .CalendarMonth_caption, .DayPickerNavigation, .rt-monthdd")) {
            e.stopPropagation();
        }
    }, true);

    // (1) Delegação: clique no título do mês visível abre/fecha a lista; clique em
    // qualquer outro lugar (fora da lista) fecha a lista.
    document.addEventListener("click", function (e) {
        if (!e.target.closest) return;
        if (e.target.closest(".rt-monthdd")) return;              // item já tratou
        var cap = e.target.closest('.CalendarMonth[data-visible="true"] .CalendarMonth_caption');
        if (cap) {
            e.stopPropagation();
            if (document.querySelector(".rt-monthdd")) closeDropdown();
            else openDropdown(cap);
            return;
        }
        closeDropdown();
    });

    function start() {
        translateCaptions();
        new MutationObserver(translateCaptions).observe(
            document.body, { childList: true, subtree: true }
        );
    }
    if (document.body) start();
    else document.addEventListener("DOMContentLoaded", start);
})();
