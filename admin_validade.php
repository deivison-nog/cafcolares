<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: index.php');
    exit;
}

include 'db.php';

$diasParaVencimento = 30;
$dataAtual = date('Y-m-d');
$dataLimite = date('Y-m-d', strtotime("+$diasParaVencimento days"));

$estabelecimentoFiltro = '';
$pesquisaNome = '';

$itensPorPagina = 10;
$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($paginaAtual - 1) * $itensPorPagina;

$sql = "SELECT * FROM medicamentos WHERE validade <= :dataLimite";

if (isset($_GET['estabelecimento']) && $_GET['estabelecimento'] != '') {
    $estabelecimentoFiltro = $_GET['estabelecimento'];
    $sql .= " AND estabelecimento = :estabelecimento";
}

if (isset($_GET['pesquisa']) && $_GET['pesquisa'] != '') {
    $pesquisaNome = $_GET['pesquisa'];
    $sql .= " AND medicamento LIKE :medicamento";
}

$sql .= " ORDER BY validade ASC";
$sql .= " LIMIT :limite OFFSET :offset";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':dataLimite', $dataLimite);
if ($estabelecimentoFiltro != '') {
    $stmt->bindParam(':estabelecimento', $estabelecimentoFiltro);
}
if ($pesquisaNome != '') {
    $medicamentoFiltro = '%' . $pesquisaNome . '%';
    $stmt->bindParam(':medicamento', $medicamentoFiltro);
}
$stmt->bindValue(':limite', $itensPorPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$medicamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
$totalItens = $countStmt->fetchColumn();
$totalPaginas = ceil($totalItens / $itensPorPagina);

$estabelecimentos = $pdo->query("SELECT DISTINCT estabelecimento FROM medicamentos")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Validade de Medicamentos – CAF</title>
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
      <i class="bi bi-calendar-x fs-3 me-2 text-danger"></i>
      <h2 class="mb-0">Validade de Medicamentos – Todos os Estabelecimentos</h2>
    </div>

    <div class="card mb-4">
      <div class="card-body">
        <form method="get" action="admin_validade.php" class="row g-3">
          <div class="col-md-5">
            <label for="pesquisa" class="form-label">Buscar Medicamento</label>
            <input type="text" class="form-control" id="pesquisa" name="pesquisa"
              value="<?php echo htmlspecialchars($pesquisaNome); ?>" placeholder="Nome do medicamento...">
          </div>
          <div class="col-md-5">
            <label for="estabelecimento" class="form-label">Estabelecimento</label>
            <select class="form-select" id="estabelecimento" name="estabelecimento">
              <option value="">Todos</option>
              <?php foreach ($estabelecimentos as $estab): ?>
                <option value="<?php echo htmlspecialchars($estab['estabelecimento']); ?>"
                  <?php echo $estabelecimentoFiltro == $estab['estabelecimento'] ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($estab['estabelecimento']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">
              <i class="bi bi-funnel me-1"></i>Filtrar
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
                <th>Medicamento</th>
                <th>Estabelecimento</th>
                <th>Quantidade</th>
                <th>Validade</th>
                <th>Status</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($medicamentos)): ?>
                <tr>
                  <td colspan="6" class="text-center py-3">Nenhum medicamento encontrado.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($medicamentos as $medicamento): ?>
                  <?php
                    $validade = $medicamento['validade'];
                    if ($validade < $dataAtual) {
                      $badgeClass = 'bg-danger';
                      $badgeLabel = 'Vencido';
                    } elseif ($validade <= $dataLimite) {
                      $badgeClass = 'bg-warning text-dark';
                      $badgeLabel = 'Próximo';
                    } else {
                      $badgeClass = 'bg-success';
                      $badgeLabel = 'OK';
                    }
                  ?>
                  <tr>
                    <td><?php echo htmlspecialchars($medicamento['medicamento']); ?></td>
                    <td><?php echo htmlspecialchars($medicamento['estabelecimento']); ?></td>
                    <td><?php echo htmlspecialchars($medicamento['quantidade']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($medicamento['validade'])); ?></td>
                    <td><span class="badge <?php echo $badgeClass; ?>"><?php echo $badgeLabel; ?></span></td>
                    <td>
                      <a href="admin_editar_medicamento.php?id=<?php echo $medicamento['id']; ?>"
                         class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil me-1"></i>Editar
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

    <?php if ($totalPaginas > 1): ?>
    <nav class="mt-3">
      <ul class="pagination pagination-sm justify-content-center">
        <li class="page-item <?php echo $paginaAtual <= 1 ? 'disabled' : ''; ?>">
          <a class="page-link" href="?pagina=<?php echo $paginaAtual - 1; ?>&pesquisa=<?php echo urlencode($pesquisaNome); ?>&estabelecimento=<?php echo urlencode($estabelecimentoFiltro); ?>">&laquo;</a>
        </li>
        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
          <li class="page-item <?php echo $i == $paginaAtual ? 'active' : ''; ?>">
            <a class="page-link" href="?pagina=<?php echo $i; ?>&pesquisa=<?php echo urlencode($pesquisaNome); ?>&estabelecimento=<?php echo urlencode($estabelecimentoFiltro); ?>">
              <?php echo $i; ?>
            </a>
          </li>
        <?php endfor; ?>
        <li class="page-item <?php echo $paginaAtual >= $totalPaginas ? 'disabled' : ''; ?>">
          <a class="page-link" href="?pagina=<?php echo $paginaAtual + 1; ?>&pesquisa=<?php echo urlencode($pesquisaNome); ?>&estabelecimento=<?php echo urlencode($estabelecimentoFiltro); ?>">&raquo;</a>
        </li>
      </ul>
    </nav>
    <?php endif; ?>
  </div>
</div>
<?php include 'includes/foot.php'; ?>
</body>
</html>
