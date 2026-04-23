<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Conexão com o banco de dados
include 'db.php';

// Obtendo a lista de estabelecimentos para o filtro
$stmt_estabelecimentos = $pdo->prepare('SELECT id, usuario FROM usuarios ORDER BY usuario');
$stmt_estabelecimentos->execute();
$estabelecimentos = $stmt_estabelecimentos->fetchAll();

// Definindo variáveis para os filtros
$searchMedicamento = '';
$filterEstabelecimento = '';

// Processar filtro por estabelecimento
if (isset($_GET['filterEstabelecimento'])) {
    $filterEstabelecimento = $_GET['filterEstabelecimento'];
}

// Consulta para buscar distribuições agrupadas por data e hora
$sql = 'SELECT
            d.data_distribuicao,
            d.hora_distribuicao,
            GROUP_CONCAT(m.medicamento SEPARATOR \', \') AS medicamentos,
            GROUP_CONCAT(d.quantidade SEPARATOR \', \') AS quantidades,
            u.usuario as estabelecimento
        FROM distribuicao d
        JOIN medicamentos m ON d.medicamento_id = m.id
        JOIN usuarios u ON d.estabelecimento_id = u.id';

$whereClause = ' WHERE 1=1';
$params = [];

// Aplicar filtro por medicamento
if (!empty($_GET['searchMedicamento'])) {
    $searchMedicamento = $_GET['searchMedicamento'];
    $whereClause .= ' AND m.medicamento LIKE :medicamento';
    $params['medicamento'] = '%' . $searchMedicamento . '%';
}

// Aplicar filtro por estabelecimento
if (!empty($filterEstabelecimento)) {
    $whereClause .= ' AND d.estabelecimento_id = :estabelecimento';
    $params['estabelecimento'] = $filterEstabelecimento;
}

$sql .= $whereClause . '
        GROUP BY d.data_distribuicao, d.hora_distribuicao, u.usuario
        ORDER BY d.data_distribuicao DESC, d.hora_distribuicao DESC
        LIMIT 10';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$distribuicoes = $stmt->fetchAll();

// Função para formatar a data
function formatarData($data) {
    $date = new DateTime($data);
    return $date->format('d/m/Y');
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Distribuições - CAF</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/head.php'; ?>
    <div class="container">
        <?php include 'includes/menu_lateral.php'; ?>
        <div class="main-content">
            <h2>Relatório de Distribuições</h2>

            <!-- Formulário de busca por medicamento -->
            <form method="get" action="relatorio.php">
                <label for="searchMedicamento">Buscar por Medicamento:</label>
                <input type="text" id="searchMedicamento" name="searchMedicamento" value="<?php echo htmlspecialchars($searchMedicamento); ?>">
                <select id="filterEstabelecimento" name="filterEstabelecimento">
                    <option value="">Filtrar por Estabelecimento</option>
                    <?php foreach ($estabelecimentos as $estabelecimento): ?>
                        <option value="<?php echo $estabelecimento['id']; ?>" <?php echo ($estabelecimento['id'] == $filterEstabelecimento) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($estabelecimento['usuario']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Buscar</button>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Medicamentos/Produtos</th>
                        <th>Estabelecimento</th>
                        <th>Quantidades</th>
                        <th>Data</th>
                        <th>Hora</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($distribuicoes as $distribuicao): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($distribuicao['medicamentos']); ?></td>
                            <td><?php echo htmlspecialchars($distribuicao['estabelecimento']); ?></td>
                            <td><?php echo htmlspecialchars($distribuicao['quantidades']); ?></td>
                            <td><?php echo formatarData($distribuicao['data_distribuicao']); ?></td>
                            <td><?php echo htmlspecialchars($distribuicao['hora_distribuicao']); ?></td>
                            <td>
                                <a href="distribuicao_confirmada.php?data=<?php echo urlencode($distribuicao['data_distribuicao']); ?>&hora=<?php echo urlencode($distribuicao['hora_distribuicao']); ?>" >
                                    <button type="button">Imprimir</button>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php include 'includes/foot.php'; ?>
</body>
</html>
