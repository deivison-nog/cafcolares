<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'usuario') {
    header('Location: index.php');
    exit;
}

// Conexão com o banco de dados
include 'db.php';

// Definir o número de dias para considerar como validade próxima (30 dias neste caso)
$diasParaVencimento = 30;
$dataAtual = date('Y-m-d');
$dataLimite = date('Y-m-d', strtotime("+$diasParaVencimento days"));

// Variáveis de filtros
$estabelecimentoFiltro = $_SESSION['usuario']; // O nome do usuário logado como estabelecimento
$pesquisaNome = '';

// Variáveis de paginação
$itensPorPagina = 10; // Número de itens por página
$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1; // Página atual
$offset = ($paginaAtual - 1) * $itensPorPagina; // Cálculo do offset

// Consulta SQL para listar medicamentos com validade próxima para o estabelecimento do usuário
$sql = "SELECT * FROM medicamentos
        WHERE validade <= :dataLimite
        AND estabelecimento = :estabelecimento
        ORDER BY validade ASC
        LIMIT :limite OFFSET :offset";

// Preparar a consulta
$stmt = $pdo->prepare($sql);

// Bind dos parâmetros
$stmt->bindParam(':dataLimite', $dataLimite);
$stmt->bindParam(':estabelecimento', $estabelecimentoFiltro);
$stmt->bindValue(':limite', $itensPorPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

try {
    // Executar a consulta
    $stmt->execute();
    $medicamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erro ao executar a consulta: " . $e->getMessage();
}

// Contar total de medicamentos
$countSql = "SELECT COUNT(*) FROM medicamentos
             WHERE validade <= :dataLimite
             AND estabelecimento = :estabelecimento";
$countStmt = $pdo->prepare($countSql);
$countStmt->bindParam(':dataLimite', $dataLimite);
$countStmt->bindParam(':estabelecimento', $estabelecimentoFiltro);
$countStmt->execute();
$totalItens = $countStmt->fetchColumn(); // Total de itens
$totalPaginas = ceil($totalItens / $itensPorPagina); // Total de páginas

// Exibir medicamentos
if (empty($medicamentos)) {
    echo "<tr><td colspan='5'>Nenhum medicamento encontrado.</td></tr>";
} else {
    foreach ($medicamentos as $medicamento) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($medicamento['medicamento']) . "</td>";
        echo "<td>" . htmlspecialchars($medicamento['estabelecimento']) . "</td>";
        echo "<td>" . htmlspecialchars($medicamento['quantidade']) . "</td>";
        echo "<td>" . date('d/m/Y', strtotime($medicamento['validade'])) . "</td>";
        echo "<td><a href='admin_editar_medicamento.php?id=" . $medicamento['id'] . "'>Editar</a></td>";
        echo "</tr>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicamentos Próximos do Vencimento</title>
    <link rel="stylesheet" href="css/style.css">
    <style media="screen">
    .container {
      display: flex;
      flex-direction: column;
      margin-top: 52px;
      padding-top: 20px;
      z-index: 900;
    }
    </style>

</head>
<body>
    <?php include 'includes/head.php'; ?>
    <div class="container">
        <?php include 'includes/menu_lateral.php'; ?>
        <div class="main-content">
            <h2>Medicamentos Próximos do Vencimento</h2>

            <!-- Tabela de medicamentos -->
            <table>
                <thead>
                    <tr>
                        <th>Medicamento</th>
                        <th>Estabelecimento</th>
                        <th>Quantidade</th>
                        <th>Validade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($medicamentos)): ?>
                        <tr>
                            <td colspan="5">Nenhum medicamento encontrado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($medicamentos as $medicamento): ?>
                            <tr>
                                <td><?php echo strtoupper($medicamento['medicamento']); ?></td>
                                <td><?php echo strtoupper($medicamento['estabelecimento']); ?></td>
                                <td><?php echo $medicamento['quantidade']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($medicamento['validade'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Paginação -->
            <div class="pagination">
                <?php if ($paginaAtual > 1): ?>
                    <a href="?pagina=<?php echo $paginaAtual - 1; ?>">Anterior</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                    <a href="?pagina=<?php echo $i; ?>" <?php if ($i == $paginaAtual) echo 'style="font-weight: bold;"'; ?>><?php echo $i; ?></a>
                <?php endfor; ?>

                <?php if ($paginaAtual < $totalPaginas): ?>
                    <a href="?pagina=<?php echo $paginaAtual + 1; ?>">Próximo</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
