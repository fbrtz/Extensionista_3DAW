<?php
session_start();
header("Content-Type: application/json");

// Conexão com o BD
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "oma_kasten";

$con = new mysqli($servername, $username, $password, $dbname);

if ($con->connect_error) {
    echo json_encode(["status" => "erro", "msg" => "Falha na conexão"]);
    exit;
}

$usuario = $_POST["usuario"] ?? "";
$senhaDigitada = $_POST["senha"] ?? "";

// Buscar usuário
$sql = $con->prepare("SELECT id, nome, senha FROM usuarios WHERE nome = ?");
$sql->bind_param("s", $usuario);
$sql->execute();
$result = $sql->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status" => "erro"]); // usuário não existe
    exit;
}

$dados = $result->fetch_assoc();
$senhaBanco = $dados["senha"];
$senhaCorreta = false;

// Caso 1: senha armazenada com hash
if (password_verify($senhaDigitada, $senhaBanco)) {
    $senhaCorreta = true;
}

// Caso 2: senha armazenada em texto puro (compatibilidade)
if ($senhaDigitada === $senhaBanco) {
    $senhaCorreta = true;
}

// Validação final
if ($senhaCorreta) {
    $_SESSION["admin_logado"] = true;
    $_SESSION["admin_id"] = $dados["id"];
    $_SESSION["admin_nome"] = $dados["nome"];

    echo json_encode(["status" => "ok"]);
} else {
    echo json_encode(["status" => "erro"]);
}
