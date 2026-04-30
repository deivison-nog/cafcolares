<?php
include 'db.php';

session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$searchTerm = '';
if (isset($_GET['search'])) {
    $searchTerm = $_GET['search'];
}

$itensPorPagina = 10;
$paginaAtual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($paginaAtual - 1) * $itensPorPagina;

$countStmt = $pdo->prepare('
    SELECT COUNT(*) FROM (
        SELECT 1
        FROM distribuicao_pacientes dp
        JOIN medicamentos m ON dp.medicamento_id = m.id
        JOIN pacientes p ON dp.paciente_id = p.id
        JOIN usuarios u ON dp.estabelecimento_id = u.id
        WHERE p.nome LIKE ?
        GROUP BY dp.data_distribuicao, dp.hora_distribuicao, p.id
    ) AS sub
');
$countStmt->execute(['%' . $searchTerm . '%']);
$totalItens = $countStmt->fetchColumn();
$totalPaginas = ceil($totalItens / $itensPorPagina);

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
    LIMIT ? OFFSET ?
');
$stmt->bindValue(1, '%' . $searchTerm . '%');
$stmt->bindValue(2, $itensPorPagina, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$distribuicoes = $stmt->fetchAll();

function formatarData($data) {
    return date('d/m/Y', strtotime($data));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Relatório de Distribuição para Pacientes – CAF</title>
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
      <i class="bi bi-people fs-3 me-2 text-primary"></i>
      <h2 class="mb-0">Relatório de Distribuição para Pacientes</h2>
    </div>

    <div class="card mb-4">
      <div class="card-body">
        <form method="get" action="relatorio_pacientes.php" class="row g-3">
          <div class="col-md-8">
            <label for="search" class="form-label">Buscar por Paciente</label>
            <input type="text" class="form-control" id="search" name="search"
              value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="Nome do paciente...">
          </div>
          <div class="col-md-4 d-flex align-items-end">
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
                <th>Data</th>
                <th>Hora</th>
                <th>Medicamento(s)</th>
                <th>Paciente</th>
                <th>Estabelecimento</th>
                <th>Ações</th>
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
                      <form action="saida_confirmada.php" method="get" class="d-inline">
                        <input type="hidden" name="data_distribuicao" value="<?php echo $distribuicao['data_distribuicao']; ?>">
                        <input type="hidden" name="hora_distribuicao" value="<?php echo $distribuicao['hora_distribuicao']; ?>">
                        <input type="hidden" name="paciente" value="<?php echo $distribuicao['paciente_nome']; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-primary">
                          <i class="bi bi-printer me-1"></i>Imprimir
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" class="text-center py-3">Nenhuma distribuição encontrada para o paciente informado.</td>
                </tr>
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
          <a class="page-link" href="?pagina=<?php echo $paginaAtual - 1; ?>&search=<?php echo urlencode($searchTerm); ?>">&laquo;</a>
        </li>
        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
          <li class="page-item <?php echo $i == $paginaAtual ? 'active' : ''; ?>">
            <a class="page-link" href="?pagina=<?php echo $i; ?>&search=<?php echo urlencode($searchTerm); ?>">
              <?php echo $i; ?>
            </a>
          </li>
        <?php endfor; ?>
        <li class="page-item <?php echo $paginaAtual >= $totalPaginas ? 'disabled' : ''; ?>">
          <a class="page-link" href="?pagina=<?php echo $paginaAtual + 1; ?>&search=<?php echo urlencode($searchTerm); ?>">&raquo;</a>
        </li>
      </ul>
    </nav>
    <?php endif; ?>

  </div>
</div>
<?php include 'includes/foot.php'; ?>
</body>
</html>
