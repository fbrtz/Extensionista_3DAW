<?php
header("Content-Type: application/json; charset=UTF-8");
require "./conexao.php";

$id = $_GET['id'] ?? null;
if (!$id) {
    echo json_encode(["erro" => "ID não informado"]);
    exit;
}

$sql = "SELECT p.*, u.nome AS unidade
        FROM produtos p
        LEFT JOIN unidades_medida u ON u.id = p.unidade_medida_id
        WHERE p.id = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

echo json_encode($res->fetch_assoc());
