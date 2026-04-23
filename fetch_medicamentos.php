<?php
include 'db.php';

session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$query = $_GET['q'];
$usuario_logado = $_SESSION['usuario'];

// Usar utf8_general_ci para garantir insensibilidade a acentos
$stmt = $pdo->prepare('
    SELECT id, medicamento, quantidade
    FROM medicamentos
    WHERE CONVERT(medicamento USING utf8) COLLATE utf8_general_ci LIKE CONVERT(? USING utf8) COLLATE utf8_general_ci
    AND estabelecimento = ?
');
$stmt->execute(["%$query%", $usuario_logado]);
$medicamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($medicamentos as $medicamento) {
    echo '<div onclick="selectMedicamento(' . $medicamento['id'] . ', \'' . htmlspecialchars($medicamento['medicamento']) . '\')">';
    echo htmlspecialchars($medicamento['medicamento']) . ' - Quantidade: ' . htmlspecialchars($medicamento['quantidade']);
    echo '</div>';
}
?>
