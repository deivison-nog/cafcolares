<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'usuario') {
    http_response_code(403);
    exit;
}

// Conexão com o banco de dados
include 'db.php';

// Verificar se o parâmetro de busca está definido
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Consulta para obter os medicamentos do estabelecimento "caf" que correspondem à busca
$sql = 'SELECT medicamento, quantidade FROM medicamentos WHERE estabelecimento = ? AND medicamento LIKE ?';

// Preparar a consulta
$stmt = $pdo->prepare($sql);
$stmt->execute(['caf', '%' . $search . '%']); // Adiciona o filtro de busca

// Obter os resultados
$medicamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Exibir os medicamentos encontrados em um formato clicável
if (count($medicamentos) > 0) {
    foreach ($medicamentos as $medicamento) {
        echo "<div class='suggestion-item'>" . htmlspecialchars($medicamento['medicamento']) . "</div>";
    }
} else {
    echo "<div class='suggestion-item'>Nenhum medicamento encontrado'.</div>";
}
?>
