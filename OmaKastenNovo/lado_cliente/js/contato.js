document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector(".formulario_contato");

    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        const fd = new FormData(form);

        try {
            const resp = await fetch("api/enviar_contato.php", {
                method: "POST",
                body: fd
            });

            const data = await resp.json();

            if (data.sucesso) {
                alert("Mensagem enviada com sucesso! Em breve entraremos em contato.");
                form.reset();
            } else {
                alert("Erro ao enviar: " + data.erro);
            }

        } catch (err) {
            console.error(err);
            alert("Erro inesperado ao enviar.");
        }
    });
});
