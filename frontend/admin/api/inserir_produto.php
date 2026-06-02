<?php
header("Content-Type: application/json; charset=UTF-8");
require "./conexao.php";

$nome = $_POST["nome"] ?? null;
$descricao = $_POST["descricao"] ?? null;
$valor = $_POST["valor"] ?? null;
$unidade = $_POST["unidade_medida_id"] ?? null;

if (!$nome || !$valor) {
    echo json_encode(["erro" => "Campos obrigatórios ausentes"]);
    exit;
}

$imagemNome = null;

if (!empty($_FILES["imagem"]["name"])) {
    $tmp = $_FILES["imagem"]["tmp_name"];
    $orig = basename($_FILES["imagem"]["name"]);
    $orig = preg_replace("/[^a-zA-Z0-9\.\-_]/", "_", $orig);

    $dest = __DIR__ . "/../../image/" . $orig;

    if (move_uploaded_file($tmp, $dest)) {
        $imagemNome = $orig;
    }
}

$sql = "INSERT INTO produtos (nome, descricao, valor, unidade_medida_id, imagem) VALUES (?,?,?,?,?)";
$stmt = $con->prepare($sql);
$stmt->bind_param("ssdss", $nome, $descricao, $valor, $unidade, $imagemNome);

if ($stmt->execute()) {
    echo json_encode(["sucesso" => true]);
} else {
    echo json_encode(["erro" => "Falha ao inserir"]);
}
