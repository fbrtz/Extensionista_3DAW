document.getElementById("formLogin").addEventListener("submit", async (e) => {
    e.preventDefault();

    const usuario = document.getElementById("usuario").value;
    const senha = document.getElementById("senha").value;

    const formData = new FormData();
    formData.append("usuario", usuario);
    formData.append("senha", senha);

    try {
        const response = await fetch("./api/login.php", {
            method: "POST",
            body: formData
        });

        const result = await response.json();

        if (result.status === "ok") {
            window.location.href = "../admin/admin-dashboard.html";
        } else {
            document.getElementById("erroLogin").style.display = "block";
        }

    } catch (error) {
        console.error("Erro:", error);
    }
});
