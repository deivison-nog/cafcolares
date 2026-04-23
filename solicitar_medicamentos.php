<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'usuario') {
    header('Location: index.php');
    exit;
}

include 'db.php';

// Exibir detalhes da sessão para depuração
echo "Sessão do usuário:<br>";
echo "usuario: " . htmlspecialchars($_SESSION['usuario']) . "<br>";
echo "nivel_acesso: " . htmlspecialchars($_SESSION['nivel_acesso']) . "<br>";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_SESSION['usuario'])) {
        $username = $_SESSION['usuario'];

        // Buscar o id do usuário baseado no nome de usuário
        $stmt = $pdo->prepare('SELECT id, usuario FROM usuarios WHERE usuario = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            echo "Erro: usuário não encontrado.";
            exit;
        } else {
            echo "Usuário autenticado: " . htmlspecialchars($user['usuario']) . "<br>";
            $usuario_id = $user['id'];
        }

        $pedidos = json_decode($_POST['pedidos'], true);

        foreach ($pedidos as $pedido) {
            $medicamento_id = $pedido['medicamento_id'];
            $quantidade = $pedido['quantidade'];

            $stmt = $pdo->prepare('INSERT INTO solicitacoes (usuario_id, medicamento_id, quantidade, data_solicitacao) VALUES (?, ?, ?, NOW())');
            $stmt->execute([$usuario_id, $medicamento_id, $quantidade]);
        }

        header('Location: solicitacoes_user.php?success=1');
        exit;
    } else {
        echo "Erro: usuário não autenticado.";
    }
}
?>
