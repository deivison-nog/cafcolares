<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: index.php');
    exit;
}

include 'db.php';
require_once 'includes/pagination_helper.php';

$estabelecimentoFiltro= '';
$pesquisaNome = '';

$itensPorPagina = 10;
$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($paginaAtual - 1) * $itensPorPagina;

$sql = "SELECT * FROM medicamentos WHERE 1=1";

if (isset($_GET['estabelecimento']) && $_GET['estabelecimento'] != '') {
    $estabelecimentoFiltro = $_GET['estabelecimento'];
    $sql .= " AND estabelecimento = :estabelecimento";
}
if (isset($_GET['pesquisa']) && $_GET['pesquisa'] != '') {
    $pesquisaNome = $_GET['pesquisa'];
    $sql .= " AND medicamento LIKE :medicamento";
}

$sql .= " ORDER BY data_inclusao DESC LIMIT :limite OFFSET :offset";

$stmt = $pdo->prepare($sql);
if ($estabelecimentoFiltro != '') $stmt->bindParam(':estabelecimento', $estabelecimentoFiltro);
if ($pesquisaNome != '') {
    $medicamentoFiltro = '%' . $pesquisaNome . '%';
    $stmt->bindParam(':medicamento', $medicamentoFiltro);
}
$stmt->bindValue(':limite', $itensPorPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$medicamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$countSql = "SELECT COUNT(*) FROM medicamentos WHERE 1=1";
if ($estabelecimentoFiltro != '') $countSql .= " AND estabelecimento = :estabelecimento";
if ($pesquisaNome != '') $countSql .= " AND medicamento LIKE :medicamento";

$countStmt = $pdo->prepare($countSql);
if ($estabelecimentoFiltro != '') $countStmt->bindParam(':estabelecimento', $estabelecimentoFiltro);
if ($pesquisaNome != '') $countStmt->bindParam(':medicamento', $medicamentoFiltro);
$countStmt->execute();
$totalItens = $countStmt->fetchColumn();
$totalPaginas = ceil($totalItens / $itensPorPagina);

$estabelecimentos = $pdo->query("SELECT DISTINCT estabelecimento FROM medicamentos ORDER BY estabelecimento")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Estoque – CAF</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include 'includes/head.php'; ?>
<div id="wrapper" class="d-flex">
  <?php include 'includes/menu_lateral.php'; ?>
  <div class="main-content flex-grow-1">

    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="mb-0 fw-bold"><i class="bi bi-boxes me-2 text-primary"></i>Estoque de Medicamentos</h3>
      <a href="incluir_medicamento.php" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Incluir Medicamento
      </a>
    </div>

    <!-- Filter form -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
          <div class="col-sm-4">
            <label class="form-label fw-semibold small">Estabelecimento</label>
            <select name="estabelecimento" class="form-select form-select-sm">
              <option value="">Todos</option>
              <?php foreach ($estabelecimentos as $e): ?>
                <option value="<?php echo htmlspecialchars($e['estabelecimento']); ?>"
                  <?php echo $e['estabelecimento'] == $estabelecimentoFiltro ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($e['estabelecimento']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-sm-5">
            <label class="form-label fw-semibold small">Buscar Medicamento</label>
            <input type="text" name="pesquisa" class="form-control form-control-sm"
                   placeholder="Nome do medicamento..."
                   value="<?php echo htmlspecialchars($pesquisaNome); ?>">
          </div>
          <div class="col-sm-3">
            <button type="submit" class="btn btn-primary btn-sm w-100">
              <i class="bi bi-search me-1"></i>Filtrar
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover table-striped mb-0 align-middle">
            <thead class="table-dark">
              <tr>
                <th>Medicamento</th>
                <th>Estabelecimento</th>
                <th class="text-center">Quantidade</th>
                <th>Data de Inclusão</th>
                <th class="text-center">Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($medicamentos)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Nenhum medicamento encontrado.</td></tr>
              <?php else: ?>
                <?php foreach ($medicamentos as $m): ?>
                  <tr>
                    <td class="fw-semibold"><?php echo htmlspecialchars($m['medicamento']); ?></td>
                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($m['estabelecimento']); ?></span></td>
                    <td class="text-center">
                      <?php if ($m['quantidade'] <= 10): ?>
                        <span class="badge bg-danger"><?php echo htmlspecialchars($m['quantidade']); ?></span>
                      <?php elseif ($m['quantidade'] <= 30): ?>
                        <span class="badge bg-warning text-dark"><?php echo htmlspecialchars($m['quantidade']); ?></span>
                      <?php else: ?>
                        <span class="badge bg-success"><?php echo htmlspecialchars($m['quantidade']); ?></span>
                      <?php endif; ?>
                    </td>
                    <td class="text-muted small"><?php echo date('d/m/Y H:i', strtotime($m['data_inclusao'])); ?></td>
                    <td class="text-center">
                      <a href="admin_editar_medicamento.php?id=<?php echo $m['id']; ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil-square"></i>
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

    <!-- Pagination -->
    <?php echo renderPaginacao($paginaAtual, $totalPaginas, 'estabelecimento=' . urlencode($estabelecimentoFiltro) . '&pesquisa=' . urlencode($pesquisaNome)); ?>

    <div class="mt-3">
      <a href="lista_completa.php?estabelecimento=<?php echo urlencode($estabelecimentoFiltro); ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-list-ul me-1"></i>Lista Completa
      </a>
    </div>

  </div>
</div>
<?php include 'includes/foot.php'; ?>
</body>
</html>
