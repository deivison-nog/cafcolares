<?php
include 'db.php';

if (isset($_GET['q'])) {
    $query = $_GET['q'];
    $stmt = $pdo->prepare('SELECT id, nome, data_nascimento, endereco FROM pacientes WHERE nome LIKE ?');
    $stmt->execute(['%' . $query . '%']);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($result);
}
?>
