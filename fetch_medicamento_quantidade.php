<?php
include 'db.php';

// Verificar se o ID do medicamento foi passado
if (isset($_GET['medicamento_id'])) {
    $medicamento_id = $_GET['medicamento_id'];

    // Buscar as informações do medicamento, incluindo a categoria
    $stmt = $pdo->prepare('
        SELECT
            quantidade,
            apresentacao,
            marca,
            lote,
            DATE_FORMAT(validade, "%d/%m/%Y") AS validade,
            categoria  -- Incluindo a categoria na consulta
        FROM medicamentos
        WHERE id = ?
    ');
    $stmt->execute([$medicamento_id]);
    $medicamento = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($medicamento) {
        echo json_encode($medicamento); // Retornando todas as informações, incluindo 'categoria'
    } else {
        echo json_encode(['erro' => 'Medicamento não encontrado']);
    }
} else {
    echo json_encode(['erro' => 'ID do medicamento não fornecido']);
}
?>
