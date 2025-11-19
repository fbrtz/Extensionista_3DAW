<?php
header("Content-Type: application/json; charset=UTF-8");
require "./conexao.php";

$res = $con->query("SELECT id, nome, abreviacao FROM unidades_medida ORDER BY nome");
$lista = [];
while ($r = $res->fetch_assoc()) $lista[] = $r;
echo json_encode($lista);
