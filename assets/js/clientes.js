document.addEventListener('DOMContentLoaded', () => {
    // ...existing code...

    const searchInput = document.getElementById('clientes-search');
    const clientesTableBody = document.querySelector('#clientes-table tbody');
    const clientesLoader = document.getElementById('clientes-loader');

    function formatTelefone(telefone) {
        telefone = telefone.replace(/[^0-9]/g, '');
        if (telefone.length === 13) {
            return `(${telefone.substr(2,2)}) ${telefone.substr(4,5)}-${telefone.substr(9,4)}`;
        } else if (telefone.length === 12) {
            return `(${telefone.substr(2,2)}) ${telefone.substr(4,4)}-${telefone.substr(8,4)}`;
        } else if (telefone.length === 11) {
            return `(${telefone.substr(0,2)}) ${telefone.substr(2,5)}-${telefone.substr(7,4)}`;
        } else if (telefone.length === 10) {
            return `(${telefone.substr(0,2)}) ${telefone.substr(2,4)}-${telefone.substr(6,4)}`;
        }
        return telefone;
    }

    function renderClientesTable(clientes) {
        clientesTableBody.innerHTML = '';
        if (!clientes || clientes.length === 0) {
            clientesTableBody.innerHTML = '<tr><td colspan="6">Nenhum cliente encontrado.</td></tr>';
            return;
        }
        clientes.forEach(cliente => {
            clientesTableBody.innerHTML += `
                <tr>
                    <td>${cliente.id ? cliente.id : ''}</td>
                    <td>${cliente.nome ? cliente.nome : ''}</td>
                    <td>${cliente.cnpj ? cliente.cnpj : ''}</td>
                    <td>${cliente.link_bitrix ? cliente.link_bitrix : ''}</td>
                    <td>${cliente.email ? cliente.email : ''}</td>
                    <td>${cliente.telefone ? formatTelefone(cliente.telefone) : ''}</td>
                </tr>
            `;
        });
    }

    function buscarClientes() {
        const termo = searchInput.value.trim();
        clientesLoader.style.display = 'flex';
        fetch(`/Apps/public/clientes_search.php?q=${encodeURIComponent(termo)}`)
            .then(res => res.json())
            .then(data => {
                renderClientesTable(data);
                clientesLoader.style.display = 'none';
            })
            .catch(() => {
                clientesTableBody.innerHTML = '<tr><td colspan="6">Erro ao buscar clientes.</td></tr>';
                clientesLoader.style.display = 'none';
            });
    }

    if (searchInput) {
        searchInput.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                buscarClientes();
            }
        });
        searchInput.addEventListener('blur', () => {
            buscarClientes();
        });
    }
});
