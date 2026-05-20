<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'usuario') {
    header('Location: index.php');
    exit;
}

include 'db.php';

$usuario_nome = $_SESSION['usuario'];
$pesquisaNome = '';

$sql = 'SELECT * FROM medicamentos WHERE estabelecimento = ?';

if (isset($_GET['pesquisa']) && $_GET['pesquisa'] != '') {
    $pesquisaNome = $_GET['pesquisa'];
    $sql .= ' AND medicamento LIKE ?';
}

$sql .= ' ORDER BY quantidade ASC';

$stmt = $pdo->prepare($sql);

if ($pesquisaNome != '') {
    $medicamentoFiltro = '%' . $pesquisaNome . '%';
    $stmt->execute([$usuario_nome, $medicamentoFiltro]);
} else {
    $stmt->execute([$usuario_nome]);
}

$medicamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

<div id="wrapper" class="d-flex">
  <?php include 'includes/menu_lateral.php'; ?>
  <div class="main-content flex-grow-1">
    <div class="print-header" style="display:none;" id="print-header">
      <img src="img/brasao.png" alt="Brasão">
      <div class="print-header-text">
        <h3>PREFEITURA MUNICIPAL DE COLARES</h3>
        <h3>SECRETARIA MUNICIPAL DE SAÚDE</h3>
        <h3>CENTRAL DE ABASTECIMENTO AMBULATORIAL</h3>
      </div>
      <img src="img/prefeitura.png" alt="Prefeitura">
    </div>

    <div class="d-flex align-items-center justify-content-between mb-4 no-print">
      <div class="d-flex align-items-center">
        <i class="bi bi-boxes fs-3 me-2 text-primary"></i>
        <h2 class="mb-0">Estoque de Medicamentos – <?php echo htmlspecialchars(strtoupper($usuario_nome)); ?></h2>
      </div>
      <button onclick="window.print()" class="btn btn-outline-secondary btn-print">
        <i class="bi bi-printer me-1"></i>Imprimir
      </button>
    </div>

    <div class="card mb-4 no-print">
      <div class="card-body">
        <form method="get" action="user_estoque.php" class="row g-3">
          <div class="col-md-8">
            <label for="pesquisa" class="form-label">Buscar Medicamento</label>
            <input type="text" class="form-control" id="pesquisa" name="pesquisa"
              value="<?php echo htmlspecialchars($pesquisaNome); ?>" placeholder="Nome do medicamento...">
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
                <th>Medicamento</th>
                <th>Quantidade</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($medicamentos)): ?>
                <?php foreach ($medicamentos as $medicamento): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($medicamento['medicamento']); ?></td>
                    <td><?php echo htmlspecialchars($medicamento['quantidade']); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="2" class="text-center py-3">Nenhum medicamento encontrado.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include 'includes/foot.php'; ?>
<script>
  window.addEventListener('beforeprint', function() {
    document.getElementById('print-header').style.display = 'flex';
  });
  window.addEventListener('afterprint', function() {
    document.getElementById('print-header').style.display = 'none';
  });
</script>
</body>
</html>
