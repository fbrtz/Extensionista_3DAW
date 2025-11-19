<?php
header("Content-Type: application/json; charset=UTF-8");
require "./conexao.php";

if ($con->connect_error) {
    echo json_encode(["erro" => "Falha ao conectar: " . $con->connect_error]);
    exit;
}

$sql = "SELECT id, nome, email, telefone, mensagem, enviado_em 
        FROM contatos ORDER BY enviado_em DESC";

$res = $con->query($sql);

$lista = [];
while ($row = $res->fetch_assoc()) {
    $lista[] = $row;
}

echo json_encode($lista);
