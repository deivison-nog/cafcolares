<?php
$host = 'localhost';
$db = 'u641927335_cafcolares';
$user = 'u641927335_cag'; // ou o nome de usuário do seu banco de dados
$pass = 'saude@Caf01'; // ou a senha do seu banco de dados

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES 'utf8'");
    $pdo->exec("SET time_zone = '-03:00';"); // Define o fuso horário para Brasília
} catch (PDOException $e) {
    echo 'Conexão falhou: ' . $e->getMessage();
}
?>
