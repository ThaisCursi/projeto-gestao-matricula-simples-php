<?php

// Habilitar o relatório de erros do MySQLi
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Configurações do banco de dados
$servername = "localhost"; // Endereço do servidor MySQL
$username = "root";        // Nome de usuário do MySQL
$password = "";            // Senha do MySQL
$dbname = "bd_fiap"; // Nome do banco de dados

// Criar conexão
$conn = new mysqli($servername, $username, $password, $dbname);

$conn->set_charset('utf8mb4');

if ($conn->errno) {
    throw new RuntimeException('mysqli error: ' . $conn->error);
}
?>
