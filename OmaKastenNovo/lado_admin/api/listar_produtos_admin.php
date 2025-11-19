<?php
header("Content-Type: application/json; charset=UTF-8");
require "./conexao.php";

$sql = "SELECT p.id, p.nome, p.descricao, p.valor, p.imagem, p.unidade_medida_id, u.nome AS unidade
        FROM produtos p
        LEFT JOIN unidades_medida u ON u.id = p.unidade_medida_id
        ORDER BY p.id DESC";

$res = $con->query($sql);
$produtos = [];

while ($row = $res->fetch_assoc()) {
    $produtos[] = $row;
}

echo json_encode($produtos);
