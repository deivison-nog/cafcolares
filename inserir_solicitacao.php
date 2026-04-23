<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'usuario') {
    header('Location: index.php');
    exit;
}

// Conexão com o banco de dados
include 'db.php';

// Verificar se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ler o corpo da requisição JSON
    $dados = json_decode(file_get_contents('php://input'), true);

    // Obter o id do usuário logado
    $usuario_id = $_SESSION['usuario_id'];

    // Preparar a consulta para inserir na tabela `solicitacoes`
    $sql = 'INSERT INTO solicitacoes (usuario_id, medicamento_id, quantidade, data_solicitacao) VALUES (?, ?, ?, NOW())';
    $stmt = $pdo->prepare($sql);

    // Iterar sobre os dados recebidos e inserir cada linha na tabela `solicitacoes`
    foreach ($dados as $item) {
        $medicamento_id = $item['medicamento_id'];
        $quantidade = $item['quantidade'];

        // Inserir na tabela `solicitacoes`
        $stmt->execute([$usuario_id, $medicamento_id, $quantidade]);
    }

    // Retornar uma resposta de sucesso
    echo json_encode(['status' => 'success']);
} else {
    // Caso a requisição não seja POST, exibir uma mensagem de erro
    echo json_encode(['status' => 'error', 'message' => 'Requisição inválida']);
}
?>
