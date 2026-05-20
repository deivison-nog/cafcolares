<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'usuario') {
    header('Location: index.php');
    exit;
}

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_SESSION['usuario'])) {
        $username = $_SESSION['usuario'];

        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE usuario = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(['error' => 'Usuário não encontrado.']);
            exit;
        }

        $usuario_id = $user['id'];
        $pedidos = json_decode($_POST['pedidos'], true);

        foreach ($pedidos as $pedido) {
            $medicamento_id = (int)$pedido['medicamento_id'];
            $quantidade = (int)$pedido['quantidade'];

            $stmt = $pdo->prepare('INSERT INTO solicitacoes (usuario_id, medicamento_id, quantidade, data_solicitacao) VALUES (?, ?, ?, NOW())');
            $stmt->execute([$usuario_id, $medicamento_id, $quantidade]);
        }

        header('Location: solicitacoes_user.php?success=1');
        exit;
    } else {
        echo json_encode(['error' => 'Usuário não autenticado.']);
    }
}
?>

