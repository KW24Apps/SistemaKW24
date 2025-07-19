// Aplica a classe collapsed no sidebar antes do DOM aparecer, evitando flicker
(function() {
  try {
    var state = localStorage.getItem('sidebarState');
    if (state === 'collapsed') {
      var sidebar = document.getElementById('sidebar');
      if (sidebar) sidebar.classList.add('collapsed');
      else document.addEventListener('DOMContentLoaded', function() {
        var sidebar = document.getElementById('sidebar');
        if (sidebar) sidebar.classList.add('collapsed');
      });
    }
  } catch(e){}
})();
