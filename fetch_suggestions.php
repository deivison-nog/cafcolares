<?php
include 'db.php';

session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: index.php');
    exit;
}

if (isset($_GET['q']) && isset($_GET['type'])) {
    $query = $_GET['q'];
    $type = $_GET['type'];

    if ($type === 'medicamento') {
        $stmt = $pdo->prepare('
            SELECT id, medicamento, quantidade
            FROM medicamentos
            WHERE medicamento LIKE ? AND estabelecimento IN (SELECT usuario FROM usuarios WHERE nivel_acesso = ?)
        ');
        $stmt->execute(['%' . $query . '%', 'admin']);
        $results = $stmt->fetchAll();

        foreach ($results as $result) {
            echo '<div class="suggestion-item" onclick="selectItem(' . $result['id'] . ', \'' . htmlspecialchars($result['medicamento']) . '\', \'' . $type . '\', {quantidade: \'' . htmlspecialchars($result['quantidade']) . '\'})">';
            echo htmlspecialchars($result['medicamento']) . ' - Quantidade: ' . htmlspecialchars($result['quantidade']);
            echo '</div>';
        }
    } elseif ($type === 'paciente') {
        $stmt = $pdo->prepare('
            SELECT id, nome, data_nascimento, endereco
            FROM pacientes
            WHERE nome LIKE ?
        ');
        $stmt->execute(['%' . $query . '%']);
        $results = $stmt->fetchAll();

        foreach ($results as $result) {
            echo '<div class="suggestion-item" onclick="selectItem(' . $result['id'] . ', \'' . htmlspecialchars($result['nome']) . '\', \'' . $type . '\', {data_nascimento: \'' . htmlspecialchars($result['data_nascimento']) . '\', endereco: \'' . htmlspecialchars($result['endereco']) . '\'})">';
            echo htmlspecialchars($result['nome']) . ' - Data de Nascimento: ' . htmlspecialchars($result['data_nascimento']);
            echo '</div>';
        }
    }
}
?>
