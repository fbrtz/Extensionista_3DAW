<?php
header("Content-Type: application/json; charset=UTF-8");
require "./conexao.php";

$id = $_POST["id"] ?? null;
$nome = $_POST["nome"] ?? null;
$descricao = $_POST["descricao"] ?? null;
$valor = $_POST["valor"] ?? null;
$unidade = $_POST["unidade_medida_id"] ?? null;

if (!$id || !$nome || !$valor) {
    echo json_encode(["erro" => "Campos obrigatórios ausentes"]);
    exit;
}

$imagemNome = null;

// upload de imagem
if (!empty($_FILES["imagem"]["name"])) {
    $orig = basename($_FILES["imagem"]["name"]);
    $orig = preg_replace("/[^a-zA-Z0-9\.\-\_]/", "_", $orig);

    $destino = __DIR__ . "/../../image/" . $orig;

    if (move_uploaded_file($_FILES["imagem"]["tmp_name"], $destino)) {
        $imagemNome = $orig;
    }
}

// SQL dinâmico simples
if ($imagemNome) {
    $sql = "UPDATE produtos SET nome=?, descricao=?, valor=?, unidade_medida_id=?, imagem=? WHERE id=?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("ssdisi", $nome, $descricao, $valor, $unidade, $imagemNome, $id);
} else {
    $sql = "UPDATE produtos SET nome=?, descricao=?, valor=?, unidade_medida_id=? WHERE id=?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("ssdii", $nome, $descricao, $valor, $unidade, $id);
}

if ($stmt->execute()) {
    echo json_encode(["sucesso" => true]);
} else {
    echo json_encode(["erro" => "Falha ao atualizar produto"]);
}
