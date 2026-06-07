// produtos-admin.js
async function carregarProdutos() {
    const tabela = document.querySelector("#tabelaProdutos tbody");
    tabela.innerHTML = "<tr><td colspan='5'>Carregando...</td></tr>";

    const req = await fetch("./api/listar_produtos_admin.php");
    const dados = await req.json();

    window.listaProdutos = dados || [];
    renderizarTabela(window.listaProdutos);
}

function renderizarTabela(lista) {
    const tabela = document.querySelector("#tabelaProdutos tbody");
    tabela.innerHTML = "";

    if (!lista || lista.length === 0) {
        tabela.innerHTML = "<tr><td colspan='5'>Nenhum produto encontrado</td></tr>";
        return;
    }

    lista.forEach(p => {
        const imagemSrc = p.imagem ? `../assets/images/${p.imagem}` : '../../assets/images/logo.png';
        tabela.innerHTML += `
        <tr>
            <td class="td-img"><img src="${imagemSrc}" alt="${p.nome}"></td>
            <td class="td-nome">${p.nome}</td>
            <td class="td-valor">R$ ${Number(p.valor).toFixed(2).replace('.',',')}</td>
            <td class="td-unidade">${p.unidade ?? "-"}</td>
            <td class="td-acoes">
                <a href="admin-edit-produto.html?id=${p.id}" class="btn btn-editar">Editar</a>
                <button onclick="excluir(${p.id})" class="btn btn-excluir">Excluir</button>
            </td>
        </tr>`;
    });
}

async function excluir(id) {
    if (!confirm("Deseja realmente excluir este produto?")) return;
    try {
      const req = await fetch(`api/excluir_produto.php?id=${id}`);
      const resp = await req.json();
      if (resp.sucesso) {
        alert("Produto excluído!");
        carregarProdutos();
      } else {
        alert("Erro ao excluir: " + (resp.erro || ""));
      }
    } catch (e) {
      console.error(e);
      alert("Erro na solicitação.");
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const buscarEl = document.querySelector("#buscar");
    const filtroEl = document.querySelector("#filtroValor");

    if (buscarEl) {
      buscarEl.addEventListener("input", () => {
        const busca = buscarEl.value.toLowerCase();
        const filtrado = window.listaProdutos.filter(p =>
            p.nome.toLowerCase().includes(busca)
        );
        renderizarTabela(filtrado);
      });
    }

    if (filtroEl) {
      filtroEl.addEventListener("change", () => {
        const v = Number(filtroEl.value);
        if (!v) return renderizarTabela(window.listaProdutos);
        const filtrado = window.listaProdutos.filter(p => Number(p.valor) <= v);
        renderizarTabela(filtrado);
      });
    }

    carregarProdutos();
});
