<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: index.php');
    exit;
}

include 'db.php';

$itensPorPagina = 10;
$paginaAtual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($paginaAtual - 1) * $itensPorPagina;

// Count distinct request groups
$countStmt = $pdo->query('SELECT COUNT(DISTINCT data_solicitacao) FROM solicitacoes');
$totalItens = $countStmt->fetchColumn();
$totalPaginas = ceil($totalItens / $itensPorPagina);

// Fetch the paginated set of distinct timestamps
$tsStmt = $pdo->prepare('SELECT DISTINCT data_solicitacao FROM solicitacoes ORDER BY data_solicitacao DESC LIMIT :limite OFFSET :offset');
$tsStmt->bindValue(':limite', $itensPorPagina, PDO::PARAM_INT);
$tsStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$tsStmt->execute();
$timestamps = $tsStmt->fetchAll(PDO::FETCH_COLUMN);

if (!empty($timestamps)) {
    $placeholders = implode(',', array_fill(0, count($timestamps), '?'));
    $stmt = $pdo->prepare("SELECT solicitacoes.*, medicamentos.medicamento, usuarios.usuario
                           FROM solicitacoes
                           JOIN medicamentos ON solicitacoes.medicamento_id = medicamentos.id
                           JOIN usuarios ON solicitacoes.usuario_id = usuarios.id
                           WHERE solicitacoes.data_solicitacao IN ($placeholders)
                           ORDER BY solicitacoes.data_solicitacao DESC");
    $stmt->execute($timestamps);
    $solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $solicitacoes = [];
}

if ($solicitacoes === false) {
    $solicitacoes = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Histórico de Solicitações – CAF</title>
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
      <i class="bi bi-clipboard-check fs-3 me-2 text-primary"></i>
      <h2 class="mb-0">Histórico de Solicitações de Medicamentos</h2>
    </div>

    <div class="card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover table-striped mb-0">
            <thead class="table-dark">
              <tr>
                <th>Usuário</th>
                <th>Medicamento</th>
                <th>Quantidade</th>
                <th>Data da Solicitação</th>
                <th>Ação</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($solicitacoes)): ?>
                <?php
                $grouped_solicitacoes = [];
                foreach ($solicitacoes as $solicitacao) {
                    $data = date('d/m/Y, \à\s H:i', strtotime($solicitacao['data_solicitacao']));
                    if (!isset($grouped_solicitacoes[$data])) {
                        $grouped_solicitacoes[$data] = [
                            'medicamentos' => [],
                            'quantidades' => [],
                            'usuarios' => [],
                            'data' => $data
                        ];
                    }
                    $grouped_solicitacoes[$data]['medicamentos'][] = $solicitacao['medicamento'];
                    $grouped_solicitacoes[$data]['quantidades'][] = $solicitacao['quantidade'];
                    $grouped_solicitacoes[$data]['usuarios'][] = $solicitacao['usuario'];
                }
                ?>
                <?php foreach ($grouped_solicitacoes as $group): ?>
                  <?php
                    $medicamentosStr = implode(', ', array_unique($group['medicamentos']));
                    $quantidadesStr = implode(', ', array_unique($group['quantidades']));
                  ?>
                  <tr>
                    <td><span class="badge bg-secondary"><?php echo strtoupper(implode(', ', array_unique($group['usuarios']))); ?></span></td>
                    <td><?php echo strtoupper(mb_substr($medicamentosStr, 0, 40)); ?></td>
                    <td><?php echo strtoupper(mb_substr($quantidadesStr, 0, 6)); ?></td>
                    <td><?php echo strtoupper($group['data']); ?></td>
                    <td>
                      <form method="get" action="solicitacoes_confirmadas_admin.php" class="d-inline">
                        <input type="hidden" name="medicamentos" value="<?php echo urlencode(mb_substr($medicamentosStr, 0, 40)); ?>">
                        <input type="hidden" name="quantidades" value="<?php echo urlencode(mb_substr($quantidadesStr, 0, 6)); ?>">
                        <input type="hidden" name="usuarios" value="<?php echo $_SESSION['usuarios'] = urlencode(implode(', ', array_unique($group['usuarios']))); ?>">
                        <input type="hidden" name="data" value="<?php echo urlencode($group['data']); ?>">
                        <button type="submit" class="btn btn-sm btn-outline-primary">
                          <i class="bi bi-printer me-1"></i>Imprimir
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="5" class="text-center py-3">Nenhuma solicitação encontrada.</td>
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
          <a class="page-link" href="?pagina=<?php echo $paginaAtual - 1; ?>">
            <i class="bi bi-chevron-left"></i>
          </a>
        </li>
        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
          <li class="page-item <?php echo $i == $paginaAtual ? 'active' : ''; ?>">
            <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item <?php echo $paginaAtual >= $totalPaginas ? 'disabled' : ''; ?>">
          <a class="page-link" href="?pagina=<?php echo $paginaAtual + 1; ?>">
            <i class="bi bi-chevron-right"></i>
          </a>
        </li>
      </ul>
    </nav>
    <?php endif; ?>

  </div>
</div>
<?php include 'includes/foot.php'; ?>
</body>
</html>
