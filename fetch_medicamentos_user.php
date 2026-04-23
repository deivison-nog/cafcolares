<?php
include 'db.php';

$q = $_GET['q'];

$stmt = $pdo->prepare('SELECT id, medicamento FROM medicamentos WHERE estabelecimento = ? AND medicamento LIKE ?');
$stmt->execute(['caf', '%' . $q . '%']);
$medicamentos = $stmt->fetchAll();

if ($medicamentos) {
    foreach ($medicamentos as $medicamento) {
        echo '<div onclick="selectMedicamento(' . $medicamento['id'] . ', \'' . htmlspecialchars($medicamento['medicamento'], ENT_QUOTES, 'UTF-8') . '\')">' . htmlspecialchars($medicamento['medicamento'], ENT_QUOTES, 'UTF-8') . '</div>';
    }
} else {
    echo '<div>Nenhum medicamento encontrado</div>';
}
?>
