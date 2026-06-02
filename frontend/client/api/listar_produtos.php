<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "oma_kasten";

$con = new mysqli($host, $user, $pass, $dbname);

if ($con->connect_error) {
    echo json_encode(["erro" => "Falha na conexão"]);
    exit;
}

$sql = "SELECT p.id, p.nome, p.descricao, p.valor, p.imagem, 
               u.nome AS unidade
        FROM produtos p
        LEFT JOIN unidades_medida u ON p.unidade_medida_id = u.id";

$res = $con->query($sql);

$produtos = [];

while ($row = $res->fetch_assoc()) {
    $produtos[] = $row;
}

echo json_encode($produtos);
