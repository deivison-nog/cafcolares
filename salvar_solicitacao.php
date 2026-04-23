<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'usuario') {
    header('Location: index.php');
    exit;
}

include 'db.php';

// Obter o usuário ID da requisição
$data = json_decode(file_get_contents('php://input'), true);

// Verificar se o usuario_id foi enviado
if (isset($data['usuario_id'])) {
    $usuario_id = $data['usuario_id'];  // ID do usuário logado
} else {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
    exit;
}

// Obter as solicitações de medicamentos
$solicitacoes = $data['solicitacoes'];

try {
    // Começar a transação
    $pdo->beginTransaction();

    // Preparar a consulta de inserção
    $stmt = $pdo->prepare('INSERT INTO solicitacoes (usuario_id, medicamento_id, quantidade) VALUES (?, ?, ?)');

    // Inserir cada solicitação de medicamento
    foreach ($solicitacoes as $solicitacao) {
        $stmt->execute([$usuario_id, $solicitacao['medicamento_id'], $solicitacao['quantidade']]);
    }

    // Commit da transação
    $pdo->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Se houver erro, reverter a transação
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
