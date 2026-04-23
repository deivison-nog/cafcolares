<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Conexão com o banco de dados
include 'db.php';

// Variáveis para filtros
$estabelecimentoFiltro = '';
$pesquisaNome = '';

// Variáveis de paginação
$itensPorPagina = 10; // Número de itens por página
$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1; // Página atual
$offset = ($paginaAtual - 1) * $itensPorPagina; // Cálculo do offset

// Consulta SQL básica
$sql = "SELECT * FROM medicamentos WHERE 1=1";

// Aplicando filtro de estabelecimento
if (isset($_GET['estabelecimento']) && $_GET['estabelecimento'] != '') {
    $estabelecimentoFiltro = $_GET['estabelecimento'];
    $sql .= " AND estabelecimento = :estabelecimento";
}

// Aplicando pesquisa por nome de medicamento
if (isset($_GET['pesquisa']) && $_GET['pesquisa'] != '') {
    $pesquisaNome = $_GET['pesquisa'];
    $sql .= " AND medicamento LIKE :medicamento";
}

// Adicionando cláusula ORDER BY para ordenar por data_inclusao
$sql .= " ORDER BY data_inclusao DESC"; // Adicionando a ordenação mais recente primeiro

// Adicionando limite e offset
$sql .= " LIMIT :limite OFFSET :offset";

// Preparar a consulta
$stmt = $pdo->prepare($sql);

// Bind dos parâmetros
if ($estabelecimentoFiltro != '') {
    $stmt->bindParam(':estabelecimento', $estabelecimentoFiltro);
}
if ($pesquisaNome != '') {
    $medicamentoFiltro = '%' . $pesquisaNome . '%';
    $stmt->bindParam(':medicamento', $medicamentoFiltro);
}

// Bind para limite e offset
$stmt->bindValue(':limite', $itensPorPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

// Executar a consulta
$stmt->execute();
$medicamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Contar total de medicamentos
$countSql = "SELECT COUNT(*) FROM medicamentos WHERE 1=1";
if ($estabelecimentoFiltro != '') {
    $countSql .= " AND estabelecimento = :estabelecimento";
}
if ($pesquisaNome != '') {
    $countSql .= " AND medicamento LIKE :medicamento";
}

$countStmt = $pdo->prepare($countSql);
if ($estabelecimentoFiltro != '') {
    $countStmt->bindParam(':estabelecimento', $estabelecimentoFiltro);
}
if ($pesquisaNome != '') {
    $countStmt->bindParam(':medicamento', $medicamentoFiltro);
}
$countStmt->execute();
$totalItens = $countStmt->fetchColumn(); // Total de itens
$totalPaginas = ceil($totalItens / $itensPorPagina); // Total de páginas

// Obter lista de estabelecimentos para o filtro
$estabelecimentos = $pdo->query("SELECT DISTINCT estabelecimento FROM medicamentos")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Estoque - CAF</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/head.php'; ?>
    <div class="container">
        <?php include 'includes/menu_lateral.php'; ?>
        <div class="main-content">
            <h2>Administração de Estoque</h2>
            <form method="get" action="admin_estoque.php">
                <label for="estabelecimento">Filtrar por Estabelecimento:</label>
                <select id="estabelecimento" name="estabelecimento">
                    <option value="">Todos</option>
                    <?php foreach ($estabelecimentos as $estabelecimento): ?>
                        <option value="<?php echo $estabelecimento['estabelecimento']; ?>" <?php if ($estabelecimento['estabelecimento'] == $estabelecimentoFiltro) echo 'selected'; ?>>
                            <?php echo $estabelecimento['estabelecimento']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="pesquisa">Buscar Medicamento:</label>
                <input type="text" id="pesquisa" name="pesquisa" value="<?php echo htmlspecialchars($pesquisaNome); ?>">

                <button type="submit">Filtrar</button>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Medicamento</th>
                        <th>Estabelecimento</th>
                        <th>Quantidade</th>
                        <th>Data da Inclusão</th> <!-- Nova coluna para última alteração -->
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($medicamentos as $medicamento): ?>
                        <tr>
                            <td><?php echo $medicamento['medicamento']; ?></td>
                            <td><?php echo $medicamento['estabelecimento']; ?></td>
                            <td><?php echo $medicamento['quantidade']; ?></td>
                            <td><?php echo date('d/m/Y H:i:s', strtotime($medicamento['data_inclusao'])); ?></td> <!-- Exibindo a última alteração -->
                            <td>
                                <a href="admin_editar_medicamento.php?id=<?php echo $medicamento['id']; ?>">Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Navegação de Paginação -->
            <div class="pagination">
                <?php if ($paginaAtual > 1): ?>
                    <a href="?pagina=<?php echo $paginaAtual - 1; ?>&estabelecimento=<?php echo urlencode($estabelecimentoFiltro); ?>&pesquisa=<?php echo urlencode($pesquisaNome); ?>">Anterior</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                    <a href="?pagina=<?php echo $i; ?>&estabelecimento=<?php echo urlencode($estabelecimentoFiltro); ?>&pesquisa=<?php echo urlencode($pesquisaNome); ?>" <?php if ($i == $paginaAtual) echo 'style="font-weight: bold;"'; ?>><?php echo $i; ?></a>
                <?php endfor; ?>

                <?php if ($paginaAtual < $totalPaginas): ?>
                    <a href="?pagina=<?php echo $paginaAtual + 1; ?>&estabelecimento=<?php echo urlencode($estabelecimentoFiltro); ?>&pesquisa=<?php echo urlencode($pesquisaNome); ?>">Próxima</a>
                <?php endif; ?>
            </div>

            <div style="margin-top: 20px;">
                <a href="lista_completa.php?estabelecimento=<?php echo urlencode($estabelecimentoFiltro); ?>" class="btn">Lista Completa</a>
            </div>
        </div>
    </div>
    <?php include 'includes/foot.php'; ?>
</body>
</html>
