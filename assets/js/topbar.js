// Exemplo de JS para trocar submenus dinamicamente
function setTopbarSubmenu(items) {
    const submenu = document.getElementById('topbar-submenu');
    submenu.innerHTML = '';
    if (Array.isArray(items)) {
        items.forEach(item => {
            const btn = document.createElement('button');
            btn.className = 'topbar-submenu-btn';
            btn.textContent = item.label;
            btn.onclick = item.onClick || null;
            submenu.appendChild(btn);
        });
    }
}
// Exemplo de uso:
// setTopbarSubmenu([{label: 'Ação 1'}, {label: 'Ação 2'}]);
