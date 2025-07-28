<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>KW24</title>
    <link rel="stylesheet" href="/Apps/assets/css/main-improved.css">
    <link rel="stylesheet" href="/Apps/assets/css/sidebar-improved.css">
    <link rel="stylesheet" href="/Apps/assets/css/topbar-improved.css">
    <link rel="stylesheet" href="/Apps/assets/css/loading-skeleton.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="preload" as="image" href="/Apps/assets/img/Fundo_Login.webp">
    <?php if (isset($additionalCSS)) echo $additionalCSS; ?>
</head>
<body>
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
    
    <div class="main-layout">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="sidebar-placeholder"></div>
        <div class="main-area">
            <?php include __DIR__ . '/topbar.php'; ?>
            <div class="main-content">
                <?php echo isset($content) ? $content : ''; ?>
            </div>
        </div>
    </div>

    <script src="/Apps/assets/js/sidebar-improved.js"></script>
    <script src="/Apps/assets/js/topbar-improved.js"></script>
    <script src="/Apps/assets/js/ajax-improved.js"></script>
    <?php if (isset($additionalJS)) echo $additionalJS; ?>
</body>
</html>
