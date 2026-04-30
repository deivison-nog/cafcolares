<?php
include 'db.php';

session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'usuario') {
    header('Location: index.php');
    exit;
}

$usuarioLogado = $_SESSION['usuario'];
$stmtUsuario = $pdo->prepare('SELECT id FROM usuarios WHERE usuario = ?');
$stmtUsuario->execute([$usuarioLogado]);
$estabelecimentoLogadoId = $stmtUsuario->fetchColumn();

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
        GROUP BY dp.data_distribuicao, dp.hora_distribuicao, p.nome, u.usuario, dp.estabelecimento_id
    ) AS sub
');
$countStmt->execute(['%' . $searchTerm . '%']);
$totalItens = $countStmt->fetchColumn();
$totalPaginas = ceil($totalItens / $itensPorPagina);

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
    LIMIT ? OFFSET ?
');
$stmt->bindValue(1, '%' . $searchTerm . '%');
$stmt->bindValue(2, $itensPorPagina, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$distribuicoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        <form method="get" action="relatorio_pacientes_user.php" class="row g-3">
          <div class="col-md-8">
            <label for="search" class="form-label">Buscar por Paciente</label>
            <input type="text" class="form-control" id="search" name="search"
              value="<?php echo htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Nome do paciente...">
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
                    <td>
                      <?php if ($distribuicao['estabelecimento_id'] == $estabelecimentoLogadoId): ?>
                        <a href="saida_confirmada_user.php?data_distribuicao=<?php echo urlencode($distribuicao['data_distribuicao']); ?>&hora_distribuicao=<?php echo urlencode($distribuicao['hora_distribuicao']); ?>&paciente=<?php echo urlencode($distribuicao['paciente_nome']); ?>"
                           class="btn btn-sm btn-outline-primary">
                          <i class="bi bi-printer me-1"></i>Imprimir
                        </a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="7" class="text-center py-3">Nenhuma distribuição encontrada para o paciente informado.</td>
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
