<div class="logs-container">
    <h1 id="logs-title">Logs (AJAX)</h1>
    <div id="logs-date" class="logs-date" aria-live="polite"><?php echo date('Y-m-d H:i:s'); ?></div>
    <button id="btn-refresh-logs">Atualizar</button>
    <div id="logs-loader" class="logs-loader" style="display:none">
        <span class="loading-spinner"></span>
        <span class="loading-text">Atualizando...</span>
    </div>
</div>
<script src="/Apps/assets/js/logs.js"></script>
