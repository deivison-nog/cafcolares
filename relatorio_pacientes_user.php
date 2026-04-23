<?php
include 'db.php';

session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'usuario') {
    header('Location: index.php');
    exit;
}

// Obter ID do estabelecimento do usuário logado
$usuarioLogado = $_SESSION['usuario'];
$stmtUsuario = $pdo->prepare('SELECT id FROM usuarios WHERE usuario = ?');
$stmtUsuario->execute([$usuarioLogado]);
$estabelecimentoLogadoId = $stmtUsuario->fetchColumn();

// Obter termo de busca, se houver
$searchTerm = '';
if (isset($_GET['search'])) {
    $searchTerm = $_GET['search'];
}

// Consulta para buscar distribuições agrupadas por data, hora e paciente
$stmt = $pdo->prepare('
    SELECT
        dp.data_distribuicao,
        dp.hora_distribuicao,
        p.nome as paciente_nome,
        u.usuario as estabelecimento_nome,
        GROUP_CONCAT(DISTINCT m.medicamento ORDER BY m.medicamento ASC SEPARATOR ", ") as medicamentos,
        GROUP_CONCAT(DISTINCT dp.quantidade ORDER BY m.medicamento ASC SEPARATOR ", ") as quantidades,
        dp.estabelecimento_id
    FROM distribuicao_pacientes dp
    JOIN medicamentos m ON dp.medicamento_id = m.id
    JOIN pacientes p ON dp.paciente_id = p.id
    JOIN usuarios u ON dp.estabelecimento_id = u.id
    WHERE p.nome LIKE ?
    GROUP BY dp.data_distribuicao, dp.hora_distribuicao, p.nome, u.usuario, dp.estabelecimento_id
    ORDER BY dp.data_distribuicao DESC, dp.hora_distribuicao DESC
    LIMIT 10
');
$stmt->execute(['%' . $searchTerm . '%']);
$distribuicoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            <form method="get" action="relatorio_pacientes_user.php">
                <label for="search">Buscar por paciente:</label>
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit">Buscar</button>
            </form>

            <h3>Distribuição para Pacientes</h3>
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Hora</th>
                        <th>Medicamentos</th>
                        <th>Quantidades</th>
                        <th>Paciente</th>
                        <th>Estabelecimento</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($distribuicoes) > 0): ?>
                        <?php foreach ($distribuicoes as $distribuicao): ?>
                            <tr>
                                <td><?php echo formatarData($distribuicao['data_distribuicao']); ?></td>
                                <td><?php echo htmlspecialchars($distribuicao['hora_distribuicao'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($distribuicao['medicamentos'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($distribuicao['quantidades'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($distribuicao['paciente_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($distribuicao['estabelecimento_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <?php if ($distribuicao['estabelecimento_id'] == $estabelecimentoLogadoId): ?>
                                    <td>
                                        <!-- Botão de Imprimir com parâmetros na URL -->
                                        <a href="saida_confirmada_user.php?data_distribuicao=<?php echo urlencode($distribuicao['data_distribuicao']); ?>&hora_distribuicao=<?php echo urlencode($distribuicao['hora_distribuicao']); ?>&paciente=<?php echo urlencode($distribuicao['paciente_nome']); ?>">
                                            <button type="button">Imprimir</button>
                                        </a>
                                    </td>
                                <?php else: ?>
                                    <td></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">Nenhuma distribuição encontrada para o paciente informado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php include 'includes/foot.php'; ?>
</body>
</html>
