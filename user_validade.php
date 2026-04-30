<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'usuario') {
    header('Location: index.php');
    exit;
}

include 'db.php';

$diasParaVencimento = 30;
$dataAtual = date('Y-m-d');
$dataLimite = date('Y-m-d', strtotime("+$diasParaVencimento days"));

$estabelecimentoFiltro = $_SESSION['usuario'];
$pesquisaNome = '';

$itensPorPagina = 10;
$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($paginaAtual - 1) * $itensPorPagina;

$sql = "SELECT * FROM medicamentos
        WHERE validade <= :dataLimite
        AND estabelecimento = :estabelecimento
        ORDER BY validade ASC
        LIMIT :limite OFFSET :offset";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':dataLimite', $dataLimite);
$stmt->bindParam(':estabelecimento', $estabelecimentoFiltro);
$stmt->bindValue(':limite', $itensPorPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

try {
    $stmt->execute();
    $medicamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erro ao executar a consulta: " . $e->getMessage();
}

$countSql = "SELECT COUNT(*) FROM medicamentos
             WHERE validade <= :dataLimite
             AND estabelecimento = :estabelecimento";
$countStmt = $pdo->prepare($countSql);
$countStmt->bindParam(':dataLimite', $dataLimite);
$countStmt->bindParam(':estabelecimento', $estabelecimentoFiltro);
$countStmt->execute();
$totalItens = $countStmt->fetchColumn();
$totalPaginas = ceil($totalItens / $itensPorPagina);
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
      <i class="bi bi-calendar-x fs-3 me-2 text-warning"></i>
      <h2 class="mb-0">Medicamentos Próximos do Vencimento</h2>
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
              </tr>
            </thead>
            <tbody>
              <?php if (empty($medicamentos)): ?>
                <tr>
                  <td colspan="5" class="text-center py-3">Nenhum medicamento encontrado.</td>
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
                    <td><?php echo strtoupper(htmlspecialchars($medicamento['medicamento'])); ?></td>
                    <td><?php echo strtoupper(htmlspecialchars($medicamento['estabelecimento'])); ?></td>
                    <td><?php echo htmlspecialchars($medicamento['quantidade']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($medicamento['validade'])); ?></td>
                    <td><span class="badge <?php echo $badgeClass; ?>"><?php echo $badgeLabel; ?></span></td>
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
          <a class="page-link" href="?pagina=<?php echo $paginaAtual - 1; ?>">&laquo;</a>
        </li>
        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
          <li class="page-item <?php echo $i == $paginaAtual ? 'active' : ''; ?>">
            <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item <?php echo $paginaAtual >= $totalPaginas ? 'disabled' : ''; ?>">
          <a class="page-link" href="?pagina=<?php echo $paginaAtual + 1; ?>">&raquo;</a>
        </li>
      </ul>
    </nav>
    <?php endif; ?>
  </div>
</div>
<?php include 'includes/foot.php'; ?>
</body>
</html>
