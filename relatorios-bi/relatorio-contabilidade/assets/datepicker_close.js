/* Fecha o painel do filtro de data ao clicar FORA dele.
 *
 * O abre/fecha continua sendo do Dash (callback toggle_data_panel sobre o
 * botão #ct-data-btn). Aqui, ao detectar um clique fora de .rt-datawrap com o
 * painel aberto, "clicamos" o botão programaticamente para o Dash fechar — assim
 * o estado (className) permanece sincronizado com o servidor.
 *
 * Cliques DENTRO de .rt-datawrap (botão, campos De/Até e o calendário, que o
 * react-dates renderiza dentro do próprio container) são ignorados. */
(function () {
    document.addEventListener("click", function (e) {
        var panel = document.getElementById("ct-data-panel");
        if (!panel) return;
        // Só age quando o painel está aberto.
        if ((panel.className || "").indexOf("open") === -1) return;
        // Clique dentro do container do filtro (botão / campos / calendário) → ignora.
        if (e.target.closest && e.target.closest(".rt-datawrap")) return;
        // Clique fora → dispara o toggle do Dash, que fecha o painel.
        var btn = document.getElementById("ct-data-btn");
        if (btn) btn.click();
    });
})();
