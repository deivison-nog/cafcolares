<?php
include 'db.php';

session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Obter termo de busca, se houver
$searchTerm = '';
if (isset($_GET['search'])) {
    $searchTerm = $_GET['search'];
}

// Consulta para buscar distribuições agrupadas
$stmt = $pdo->prepare('
    SELECT dp.data_distribuicao, dp.hora_distribuicao, p.nome as paciente_nome, u.usuario as estabelecimento_nome,
        GROUP_CONCAT(CONCAT(m.medicamento, " (", dp.quantidade, ")") SEPARATOR ", ") as medicamentos
    FROM distribuicao_pacientes dp
    JOIN medicamentos m ON dp.medicamento_id = m.id
    JOIN pacientes p ON dp.paciente_id = p.id
    JOIN usuarios u ON dp.estabelecimento_id = u.id
    WHERE p.nome LIKE ?
    GROUP BY dp.data_distribuicao, dp.hora_distribuicao, p.id
    ORDER BY dp.data_distribuicao DESC, dp.hora_distribuicao DESC
    LIMIT 10
');
$stmt->execute(['%' . $searchTerm . '%']);
$distribuicoes = $stmt->fetchAll();

// Função para formatar a data para dd/mm/yyyy
function formatarData($data) {
    return date('d/m/Y', strtotime($data));
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Distribuição para Pacientes</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/head.php'; ?>
    <div class="container">
        <?php include 'includes/menu_lateral.php'; ?>
        <div class="main-content">
            <h2>Relatório de Distribuição para Pacientes</h2>

            <!-- Barra de busca -->
            <form method="get" action="relatorio_pacientes.php">
                <label for="search">Buscar por paciente:</label>
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>">
                <button type="submit">Buscar</button>
            </form>

            <h3>Distribuição para Pacientes</h3>
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Hora</th>
                        <th>Medicamento</th>
                        <th>Paciente</th>
                        <th>Quantidade</th>
                        <th>Estabelecimento</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($distribuicoes) > 0): ?>
                        <?php foreach ($distribuicoes as $distribuicao): ?>
                            <tr>
                                <td><?php echo formatarData($distribuicao['data_distribuicao']); ?></td>
                                <td><?php echo htmlspecialchars($distribuicao['hora_distribuicao']); ?></td>
                                <td><?php echo htmlspecialchars($distribuicao['medicamentos']); ?></td>
                                <td><?php echo htmlspecialchars($distribuicao['paciente_nome']); ?></td>
                                <td><?php echo htmlspecialchars($distribuicao['estabelecimento_nome']); ?></td>
                                <td>
                                    <!-- Botão de Imprimir -->
                                    <form action="saida_confirmada.php" method="get">
                                        <input type="hidden" name="data_distribuicao" value="<?php echo $distribuicao['data_distribuicao']; ?>">
                                        <input type="hidden" name="hora_distribuicao" value="<?php echo $distribuicao['hora_distribuicao']; ?>">
                                        <input type="hidden" name="paciente" value="<?php echo $distribuicao['paciente_nome']; ?>">
                                        <button type="submit">Imprimir</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">Nenhuma distribuição encontrada para o paciente informado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php include 'includes/foot.php'; ?>
</body>
</html>
