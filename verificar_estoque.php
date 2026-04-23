<?php
include 'db.php';

if (isset($_GET['id'])) {
    $medicamento_id = intval($_GET['id']);

    try {
        $stmt = $pdo->prepare('SELECT quantidade FROM medicamentos WHERE id = ?');
        $stmt->execute([$medicamento_id]);
        $quantidade = $stmt->fetchColumn();

        if ($quantidade !== false) {
            echo json_encode(['quantidade' => $quantidade]);
        } else {
            echo json_encode(['erro' => 'Medicamento não encontrado.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['erro' => $e->getMessage()]);
    }
} else {
    echo json_encode(['erro' => 'ID do medicamento não fornecido.']);
}
