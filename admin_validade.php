<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Conexão com o banco de dados
include 'db.php';

// Definir o número de dias para considerar como validade próxima (30 dias neste caso)
$diasParaVencimento = 30;
$dataAtual = date('Y-m-d');
$dataLimite = date('Y-m-d', strtotime("+$diasParaVencimento days"));

// Variáveis para filtros
$estabelecimentoFiltro = '';
$pesquisaNome = '';

// Variáveis de paginação
$itensPorPagina = 10; // Número de itens por página
$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1; // Página atual
$offset = ($paginaAtual - 1) * $itensPorPagina; // Cálculo do offset

// Consulta SQL para listar medicamentos com validade próxima, sem filtrar inicialmente por estabelecimento
$sql = "SELECT * FROM medicamentos WHERE validade <= :dataLimite";

// Aplicando filtro de estabelecimento, caso tenha sido selecionado no formulário
if (isset($_GET['estabelecimento']) && $_GET['estabelecimento'] != '') {
    $estabelecimentoFiltro = $_GET['estabelecimento'];
    $sql .= " AND estabelecimento = :estabelecimento";
}

// Aplicando pesquisa por nome de medicamento
if (isset($_GET['pesquisa']) && $_GET['pesquisa'] != '') {
    $pesquisaNome = $_GET['pesquisa'];
    $sql .= " AND medicamento LIKE :medicamento";
}

// Adicionando cláusula ORDER BY para ordenar por validade
$sql .= " ORDER BY validade ASC";

// Adicionando limite e offset para paginação
$sql .= " LIMIT :limite OFFSET :offset";

// Preparar a consulta
$stmt = $pdo->prepare($sql);

// Bind dos parâmetros
$stmt->bindParam(':dataLimite', $dataLimite);
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
$countSql = "SELECT COUNT(*) FROM medicamentos WHERE validade <= :dataLimite";
if ($estabelecimentoFiltro != '') {
    $countSql .= " AND estabelecimento = :estabelecimento";
}
if ($pesquisaNome != '') {
    $countSql .= " AND medicamento LIKE :medicamento";
}

$countStmt = $pdo->prepare($countSql);
$countStmt->bindParam(':dataLimite', $dataLimite);
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
    <title>Estoque de Medicamentos - Todos os Estabelecimentos</title>
    <link rel="stylesheet" href="css/style.css">
    
</head>
<body>
    <?php include 'includes/head.php'; ?>
    <div class="container">
        <?php include 'includes/menu_lateral.php'; ?>
        <div class="main-content">
            <h2>Estoque de Medicamentos - Todos os Estabelecimentos</h2>

            <!-- Formulário de busca -->
            <form method="get" action="admin_validade.php">
                <label for="pesquisa">Buscar Medicamento:</label>
                <input type="text" id="pesquisa" name="pesquisa" value="<?php echo htmlspecialchars($pesquisaNome); ?>">

                <label for="estabelecimento">Estabelecimento:</label>
                <select id="estabelecimento" name="estabelecimento">
                    <option value="">Todos</option>
                    <?php foreach ($estabelecimentos as $estab): ?>
                        <option value="<?php echo $estab['estabelecimento']; ?>" <?php echo $estabelecimentoFiltro == $estab['estabelecimento'] ? 'selected' : ''; ?>>
                            <?php echo $estab['estabelecimento']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit">Filtrar</button>
            </form>

            <!-- Tabela de medicamentos -->
            <table>
                <thead>
                    <tr>
                        <th>Medicamento</th>
                        <th>Estabelecimento</th>
                        <th>Quantidade</th>
                        <th>Validade</th>
                        <th>Ações</th>
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
                                <td><?php echo $medicamento['medicamento']; ?></td>
                                <td><?php echo $medicamento['estabelecimento']; ?></td>
                                <td><?php echo $medicamento['quantidade']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($medicamento['validade'])); ?></td>
                                <td>
                                    <a href="admin_editar_medicamento.php?id=<?php echo $medicamento['id']; ?>">Editar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Paginação -->
            <div class="pagination">
                <?php if ($paginaAtual > 1): ?>
                    <a href="?pagina=<?php echo $paginaAtual - 1; ?>&pesquisa=<?php echo urlencode($pesquisaNome); ?>&estabelecimento=<?php echo urlencode($estabelecimentoFiltro); ?>">Anterior</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                    <a href="?pagina=<?php echo $i; ?>&pesquisa=<?php echo urlencode($pesquisaNome); ?>&estabelecimento=<?php echo urlencode($estabelecimentoFiltro); ?>" <?php if ($i == $paginaAtual) echo 'style="font-weight: bold;"'; ?>><?php echo $i; ?></a>
                <?php endfor; ?>

                <?php if ($paginaAtual < $totalPaginas): ?>
                    <a href="?pagina=<?php echo $paginaAtual + 1; ?>&pesquisa=<?php echo urlencode($pesquisaNome); ?>&estabelecimento=<?php echo urlencode($estabelecimentoFiltro); ?>">Próxima</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php include 'includes/foot.php'; ?>
</body>
</html>
