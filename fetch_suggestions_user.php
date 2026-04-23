<?php
include 'db.php';

session_start();

// Verificar se o usuário está logado e se o nível de acesso é 'usuario'
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'usuario') {
    echo "Erro: Usuário não autorizado.";
    exit;
}

$type = $_GET['type'];
$query = $_GET['q'];

// Obter o ID do estabelecimento do usuário autenticado
$usuario = $_SESSION['usuario'];
$stmt = $pdo->prepare('SELECT id FROM usuarios WHERE usuario = ?');
$stmt->execute([$usuario]);
$estabelecimento_id = $stmt->fetchColumn();

if (!$estabelecimento_id) {
    echo "Erro: Estabelecimento não encontrado para o usuário autenticado.";
    exit;
}

if ($type == 'medicamento') {
    $stmt = $pdo->prepare('SELECT id, medicamento, quantidade FROM medicamentos WHERE medicamento LIKE ? AND estabelecimento = ?');
    $stmt->execute(['%' . $query . '%', $usuario]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as $result) {
        echo '<div class="suggestion-item" onclick="selectItem(' . $result['id'] . ', \'' . $result['medicamento'] . '\', \'medicamento\', { quantidade: ' . $result['quantidade'] . ' })">';
        echo $result['medicamento'] . ' (Quantidade: ' . $result['quantidade'] . ')';
        echo '</div>';
    }
} elseif ($type == 'paciente') {
    $stmt = $pdo->prepare('SELECT id, nome, data_nascimento, endereco FROM pacientes WHERE nome LIKE ?');
    $stmt->execute(['%' . $query . '%']);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as $result) {
        // Converte a data para o formato dd/mm/yyyy
        $data_nascimento_formatada = (new DateTime($result['data_nascimento']))->format('d/m/Y');

        echo '<div class="suggestion-item" onclick="selectItem(' . $result['id'] . ', \'' . htmlspecialchars($result['nome']) . '\', \'paciente\', { data_nascimento: \'' . htmlspecialchars($result['data_nascimento']) . '\', endereco: \'' . htmlspecialchars($result['endereco']) . '\' })">';
        echo htmlspecialchars($result['nome']) . ' - ' . htmlspecialchars($data_nascimento_formatada);
        echo '</div>';
    }
}
?>
