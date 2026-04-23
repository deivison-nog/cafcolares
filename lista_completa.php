<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: index.php');
    exit;
}

include 'db.php';

$estabelecimentoFiltro = isset($_GET['estabelecimento']) ? $_GET['estabelecimento'] : '';

$sql = "SELECT medicamento, quantidade FROM medicamentos WHERE 1=1";

if (!empty($estabelecimentoFiltro)) {
    $sql .= " AND estabelecimento = :estabelecimento";
}
$sql .= " ORDER BY medicamento ASC";

$stmt = $pdo->prepare($sql);

if (!empty($estabelecimentoFiltro)) {
    $stmt->bindParam(':estabelecimento', $estabelecimentoFiltro, PDO::PARAM_STR);
}

$stmt->execute();
$medicamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lista Completa de Medicamentos – CAF</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container py-3">
  <div class="mb-3 no-print">
    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary btn-print">
      <i class="bi bi-printer me-1"></i>Imprimir
    </button>
    <a href="admin_estoque.php" class="btn btn-sm btn-outline-secondary ms-2">
      <i class="bi bi-arrow-left me-1"></i>Voltar
    </a>
  </div>
  <div class="print-header">
    <img src="img/brasao.png" alt="Brasão">
    <div class="print-header-text">
      <h3>PREFEITURA MUNICIPAL DE COLARES</h3>
      <h3>SECRETARIA MUNICIPAL DE SAÚDE</h3>
      <h3>CENTRAL DE ABASTECIMENTO AMBULATORIAL</h3>
    </div>
    <img src="img/prefeitura.png" alt="Prefeitura">
  </div>

  <h2 class="text-center mb-3">ESTOQUE COMPLETO DE MEDICAMENTOS</h2>

  <table class="table table-bordered">
    <thead class="table-light">
      <tr>
        <th>Medicamento</th>
        <th>Quantidade</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($medicamentos as $medicamento): ?>
        <tr>
          <td><?php echo htmlspecialchars($medicamento['medicamento']); ?></td>
          <td><?php echo htmlspecialchars($medicamento['quantidade']); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include 'includes/foot.php'; ?>
</body>
</html>
