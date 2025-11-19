async function carregarContatos() {
    const tabela = document.querySelector("#tabelaContatos tbody");
    tabela.innerHTML = "<tr><td colspan='5'>Carregando...</td></tr>";

    const req = await fetch("./api/listar_contatos_admin.php");
    const dados = await req.json();

    window.listaContatos = dados || [];
    renderizarTabela(window.listaContatos);
}


function formatarDataHora(dtString) {
    const dt = new Date(dtString);
    const data = dt.toLocaleDateString("pt-BR");   // dd/mm/aaaa
    const hora = dt.toLocaleTimeString("pt-BR", {hour: "2-digit", minute: "2-digit"});
    return `${data} às ${hora}`;
}


function renderizarTabela(lista) {
    const tabela = document.querySelector("#tabelaContatos tbody");
    tabela.innerHTML = "";

    if (!lista || lista.length === 0) {
        tabela.innerHTML = "<tr><td colspan='5'>Nenhum contato encontrado</td></tr>";
        return;
    }

    lista.forEach(c => {
        tabela.innerHTML += `
        <tr>
            <td>${c.nome}</td>
            <td>${c.email ?? "-"}</td>
            <td>${c.telefone ?? "-"}</td>
            <td class="td-msg">${c.mensagem}</td>
            <td>${formatarDataHora(c.enviado_em)}</td>
        </tr>`;
    });
}

function filtrar() {
    let lista = window.listaContatos;

    const busca = document.querySelector("#buscar").value.toLowerCase();
    const dataInicio = document.querySelector("#dataInicio").value;
    const dataFim = document.querySelector("#dataFim").value;

    let filtrado = lista.filter(c =>
        c.nome.toLowerCase().includes(busca)
    );

    if (dataInicio) {
        filtrado = filtrado.filter(c => c.enviado_em >= (dataInicio + " 00:00:00"));
    }

    if (dataFim) {
        filtrado = filtrado.filter(c => c.enviado_em <= (dataFim + " 23:59:59"));
    }

    renderizarTabela(filtrado);
}

document.addEventListener("DOMContentLoaded", () => {
    document.querySelector("#buscar").addEventListener("input", filtrar);
    document.querySelector("#btn-filtrar").addEventListener("click", filtrar);
    carregarContatos();
});
