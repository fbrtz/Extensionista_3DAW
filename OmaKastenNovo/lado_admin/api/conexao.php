<?php
// conexao.php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "oma_kasten";

$con = new mysqli($host, $user, $pass, $dbname);
if ($con->connect_error) {
    http_response_code(500);
    echo json_encode(["erro" => "Falha na conexão: " . $con->connect_error]);
    exit;
}
$con->set_charset("utf8mb4");
?>