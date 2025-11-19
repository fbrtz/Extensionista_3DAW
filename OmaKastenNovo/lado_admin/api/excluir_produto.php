<?php
header("Content-Type: application/json; charset=UTF-8");
require "./conexao.php";

$id = $_GET['id'] ?? null;
if (!$id) {
    echo json_encode(["erro" => "ID não informado"]);
    exit;
}

// opcional: excluir imagem do disco (se quiser)
/*
$sqlImg = "SELECT imagem FROM produtos WHERE id = ?";
$stmtImg = $con->prepare($sqlImg);
$stmtImg->bind_param("i", $id);
$stmtImg->execute();
$resImg = $stmtImg->get_result()->fetch_assoc();
if (!empty($resImg['imagem'])) {
    @unlink(__DIR__ . "/../../image/" . $resImg['imagem']);
}
*/

$sql = "DELETE FROM produtos WHERE id = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(["sucesso" => true]);
} else {
    echo json_encode(["erro" => "Falha ao excluir"]);
}
