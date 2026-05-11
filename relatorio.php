<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: index.php');
    exit;
}

include 'db.php';
require_once 'includes/pagination_helper.php';

$stmt_estabelecimentos = $pdo->prepare('SELECT id, usuario FROM usuarios ORDER BY usuario');
$stmt_estabelecimentos->execute();
$estabelecimentos = $stmt_estabelecimentos->fetchAll();

$searchMedicamento = '';
$filterEstabelecimento = '';

if (isset($_GET['filterEstabelecimento'])) {
    $filterEstabelecimento = $_GET['filterEstabelecimento'];
}

$itensPorPagina = 10;
$paginaAtual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($paginaAtual - 1) * $itensPorPagina;

$baseSql = 'FROM distribuicao d
        JOIN medicamentos m ON d.medicamento_id = m.id
        JOIN usuarios u ON d.estabelecimento_id = u.id';

$whereClause = ' WHERE 1=1';
$params = [];

if (!empty($_GET['searchMedicamento'])) {
    $searchMedicamento = $_GET['searchMedicamento'];
    $whereClause .= ' AND m.medicamento LIKE :medicamento';
    $params['medicamento'] = '%' . $searchMedicamento . '%';
}

if (!empty($filterEstabelecimento)) {
    $whereClause .= ' AND d.estabelecimento_id = :estabelecimento';
    $params['estabelecimento'] = $filterEstabelecimento;
}

$countSql = 'SELECT COUNT(*) FROM (SELECT 1 ' . $baseSql . $whereClause
    . ' GROUP BY d.data_distribuicao, d.hora_distribuicao, u.usuario) AS sub';
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalItens = $countStmt->fetchColumn();
$totalPaginas = ceil($totalItens / $itensPorPagina);

$sql = 'SELECT d.data_distribuicao, d.hora_distribuicao,
            GROUP_CONCAT(m.medicamento SEPARATOR \', \') AS medicamentos,
            GROUP_CONCAT(d.quantidade SEPARATOR \', \') AS quantidades,
            u.usuario as estabelecimento '
    . $baseSql . $whereClause . '
        GROUP BY d.data_distribuicao, d.hora_distribuicao, u.usuario
        ORDER BY d.data_distribuicao DESC, d.hora_distribuicao DESC
        LIMIT :limite OFFSET :offset';

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue(':' . $key, $val);
}
$stmt->bindValue(':limite', $itensPorPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$distribuicoes = $stmt->fetchAll();

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
  <title>Relatório de Distribuições – CAF</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include 'includes/head.php'; ?>
<div id="wrapper" class="d-flex">
  <?php include 'includes/menu_lateral.php'; ?>
  <div class="main-content flex-grow-1">
    <div class="d-flex align-items-center mb-4">
      <i class="bi bi-bar-chart-line fs-3 me-2 text-primary"></i>
      <h2 class="mb-0">Relatório de Distribuições</h2>
    </div>

    <div class="card mb-4">
      <div class="card-body">
        <form method="get" action="relatorio.php" class="row g-3">
          <div class="col-md-5">
            <label for="searchMedicamento" class="form-label">Buscar por Medicamento</label>
            <input type="text" class="form-control" id="searchMedicamento" name="searchMedicamento"
              value="<?php echo htmlspecialchars($searchMedicamento); ?>" placeholder="Nome do medicamento...">
          </div>
          <div class="col-md-5">
            <label for="filterEstabelecimento" class="form-label">Estabelecimento</label>
            <select class="form-select" id="filterEstabelecimento" name="filterEstabelecimento">
              <option value="">Todos os Estabelecimentos</option>
              <?php foreach ($estabelecimentos as $estabelecimento): ?>
                <option value="<?php echo $estabelecimento['id']; ?>"
                  <?php echo ($estabelecimento['id'] == $filterEstabelecimento) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($estabelecimento['usuario']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">
              <i class="bi bi-search me-1"></i>Buscar
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover table-striped mb-0">
            <thead class="table-dark">
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
              <?php if (empty($distribuicoes)): ?>
                <tr>
                  <td colspan="6" class="text-center py-3">Nenhuma distribuição encontrada.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($distribuicoes as $distribuicao): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($distribuicao['medicamentos']); ?></td>
                    <td><?php echo htmlspecialchars($distribuicao['estabelecimento']); ?></td>
                    <td><?php echo htmlspecialchars($distribuicao['quantidades']); ?></td>
                    <td><?php echo formatarData($distribuicao['data_distribuicao']); ?></td>
                    <td><?php echo htmlspecialchars($distribuicao['hora_distribuicao']); ?></td>
                    <td>
                      <a href="distribuicao_confirmada.php?data=<?php echo urlencode($distribuicao['data_distribuicao']); ?>&hora=<?php echo urlencode($distribuicao['hora_distribuicao']); ?>"
                         class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-printer me-1"></i>Imprimir
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <?php echo renderPaginacao($paginaAtual, $totalPaginas, 'searchMedicamento=' . urlencode($searchMedicamento) . '&filterEstabelecimento=' . urlencode($filterEstabelecimento)); ?>

  </div>
</div>
<?php include 'includes/foot.php'; ?>
</body>
</html>
