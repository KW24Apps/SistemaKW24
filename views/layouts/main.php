<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>KW24</title>
    <link rel="stylesheet" href="/Apps/assets/css/main.css">
    <link rel="stylesheet" href="/Apps/assets/css/sidebar.css">
    <link rel="stylesheet" href="/Apps/assets/css/area-atuacao.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="preload" as="image" href="/Apps/assets/img/Fundo_Login.webp">
</head>
<body>
    <!-- Script para aplicar o estado do sidebar antes do HTML aparecer -->

    <script>
      try {
        var state = localStorage.getItem("sidebarState");
        if (state === "collapsed") {
          document.addEventListener("DOMContentLoaded", function() {
            var sidebar = document.getElementById("sidebar");
            if (sidebar) sidebar.classList.add("collapsed");
            document.body.classList.add("sidebar-collapsed");
          });
        } else {
          document.addEventListener("DOMContentLoaded", function() {
            document.body.classList.remove("sidebar-collapsed");
          });
        }
      } catch(e){}
    </script>

    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="area-atuacao-wrapper">
      <div class="area-atuacao">
        <?php echo isset($content) ? $content : ''; ?>
      </div>
    </div>

    <!-- JS: Sidebar (deixe sempre no final) -->
    <script src="/Apps/assets/js/sidebar.js"></script>
</body>
</html>