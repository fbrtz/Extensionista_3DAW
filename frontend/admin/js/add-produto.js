// add-produto.js
document.addEventListener("DOMContentLoaded", () => {

  const form = document.querySelector(".formulario_contato");

  // Criar select de unidade
  const selectUnidade = document.createElement("select");
  selectUnidade.name = "unidade_medida_id";

  // Inserir select antes do label "imagem"
  const imagemLabel = document.querySelector('label[for="imagem"]');
  imagemLabel.parentNode.insertBefore(selectUnidade, imagemLabel);

  // Carregar unidades
  fetch("./api/listar_unidades_medida.php")
    .then(r => r.json())
    .then(unidades => {

      const opt = document.createElement("option");
      opt.value = "";
      opt.textContent = "Selecione unidade (opcional)";
      selectUnidade.appendChild(opt);

      unidades.forEach(u => {
        const o = document.createElement("option");
        o.value = u.id;
        o.textContent = u.nome + (u.abreviacao ? ` (${u.abreviacao})` : "");
        selectUnidade.appendChild(o);
      });

    });

  // Enviar via fetch
  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const fd = new FormData(form);

    try {
      const resp = await fetch("./api/inserir_produto.php", {
        method: "POST",
        body: fd
      });

      const data = await resp.json();

      if (data.sucesso) {
        alert("Produto criado com sucesso!");
        window.location.href = "admin-produtos.html";
      } else {
        alert("Erro: " + (data.erro || "Erro desconhecido"));
      }

    } catch (err) {
      console.error(err);
      alert("Erro ao enviar dados.");
    }
  });

});
