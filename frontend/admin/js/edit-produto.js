document.addEventListener("DOMContentLoaded", () => {
  const params = new URLSearchParams(window.location.search);
  const id = params.get("id");
  if (!id) {
    alert("Produto não informado");
    return;
  }

  const form = document.querySelector(".formulario_edicao");
  const campoUnidade = document.getElementById("campo-unidade");

  // Criar select corretamente
  const selectUnidade = document.createElement("select");
  selectUnidade.name = "unidade_medida_id";
  selectUnidade.id = "unidade_medida_id";

  const labelUnidade = document.createElement("label");
  labelUnidade.textContent = "Unidade de Medida:";
  labelUnidade.setAttribute("for", "unidade_medida_id");

  campoUnidade.appendChild(labelUnidade);
  campoUnidade.appendChild(selectUnidade);

  // 1) Carregar unidades de medida
  fetch("api/listar_unidades_medida.php")
    .then(r => r.json())
    .then(unidades => {
      selectUnidade.innerHTML = `
        <option value="">Selecione unidade (opcional)</option>
      `;

      unidades.forEach(u => {
        const opt = document.createElement("option");
        opt.value = u.id;
        opt.textContent = u.nome + (u.abreviacao ? ` (${u.abreviacao})` : "");
        selectUnidade.appendChild(opt);
      });

      carregarProduto();
    });

  // 2) Carregar dados do produto
  async function carregarProduto() {
    const resp = await fetch("api/buscar_produto.php?id=" + id);
    const p = await resp.json();

    if (p.erro) {
      alert("Erro ao carregar produto");
      return;
    }

    form.querySelector('input[name="id"]').value = p.id;
    form.querySelector('input[name="nome"]').value = p.nome;
    form.querySelector('textarea[name="descricao"]').value = p.descricao;
    form.querySelector('input[name="valor"]').value = p.valor;

    if (p.unidade_medida_id) {
      selectUnidade.value = p.unidade_medida_id;
    }
  }

  // 3) Atualizar produto
  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const fd = new FormData(form);

    try {
      const resp = await fetch("api/atualizar_produto.php", {
        method: "POST",
        body: fd
      });
      const data = await resp.json();

      if (data.sucesso) {
        alert("Produto atualizado com sucesso!");
        window.location.href = "admin-produtos.html";
      } else {
        alert("Erro: " + (data.erro || "Erro desconhecido"));
      }
    } catch (err) {
      console.error(err);
      alert("Erro ao atualizar produto.");
    }
  });
});
