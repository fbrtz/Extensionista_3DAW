<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "oma_kasten";

// CRIAR CONEXÃO !!!
$con = new mysqli($host, $user, $pass, $dbname);

if ($con->connect_error) {
    echo json_encode([
        "sucesso" => false,
        "erro" => "Erro de conexão: " . $con->connect_error
    ]);
    exit;
}

$nome = $_POST["nome"] ?? "";
$email = $_POST["email"] ?? "";
$telefone = $_POST["telefone"] ?? "";
$mensagem = $_POST["pedido"] ?? ""; // campo "pedido" do HTML

if (!$nome || !$email || !$telefone || !$mensagem) {
    echo json_encode([
        "sucesso" => false,
        "erro" => "Todos os campos são obrigatórios."
    ]);
    exit;
}

try {
    $sql = $con->prepare("
        INSERT INTO contatos (nome, email, telefone, mensagem)
        VALUES (?, ?, ?, ?)
    ");

    $sql->bind_param("ssss", $nome, $email, $telefone, $mensagem);

    if ($sql->execute()) {
        echo json_encode(["sucesso" => true]);
    } else {
        echo json_encode([
            "sucesso" => false,
            "erro" => "Erro ao salvar no banco."
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        "sucesso" => false,
        "erro" => $e->getMessage()
    ]);
}
