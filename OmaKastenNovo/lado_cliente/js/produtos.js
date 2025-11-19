async function carregarProdutos() {
    try {
        const resposta = await fetch("./api/listar_produtos.php");

        if (!resposta.ok) {
            console.error("Erro HTTP:", resposta.status);
            return;
        }

        const produtos = await resposta.json();

        console.log("Produtos carregados:", produtos); // DEBUG

        const container = document.getElementById("lista-produtos");

        if (!Array.isArray(produtos)) {
            container.innerHTML = "<p>Erro ao carregar produtos.</p>";
            console.error("Resposta não é uma lista:", produtos);
            return;
        }

        let html = `<table><tr>`;

        produtos.forEach((p, index) => {

            if (index % 3 === 0 && index !== 0) {
                html += `</tr><tr>`;
            }

            const preco = Number(p.valor)
                .toFixed(2)
                .replace(".", ",");

            html += `
                <td>
                    <img class="produtos" src="../image/${p.imagem}">
                    <p><b>${p.nome}</b></p>
                    <p class="descricao">${p.descricao}</p>
                    <p class="preco">R$${preco}/${p.unidade}</p>
                </td>
            `;
        });

        html += `</tr></table>`;
        container.innerHTML = html;

    } catch (e) {
        console.error("Erro no JS:", e);
    }
}

carregarProdutos();
