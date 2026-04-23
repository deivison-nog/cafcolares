<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'usuario') {
    header('Location: index.php');
    exit;
}

include 'db.php';

if (!isset($_GET['medicamentos']) || !isset($_GET['quantidades']) || !isset($_GET['data'])) {
    echo "Dados de solicitação não encontrados ou inválidos.";
    exit;
}

$medicamentos = urldecode($_GET['medicamentos']);
$quantidades = urldecode($_GET['quantidades']);
$data = urldecode($_GET['data']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Solicitações Confirmadas – CAF</title>
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
    <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary ms-2">
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

  <h2 class="text-center text-uppercase mb-3">
    Solicitação de Medicamentos do <?php echo htmlspecialchars($_SESSION['usuario'], ENT_QUOTES, 'UTF-8'); ?>
  </h2>

  <table class="table table-bordered">
    <thead class="table-light">
      <tr>
        <th>Medicamento/Produto</th>
        <th>Quantidade</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $medicamentosArray = explode(', ', $medicamentos);
      $quantidadesArray  = explode(', ', $quantidades);
      // Ensure both arrays have the same length to prevent silent data loss
      $count = min(count($medicamentosArray), count($quantidadesArray));
      for ($i = 0; $i < $count; $i++):
      ?>
        <tr>
          <td><?php echo htmlspecialchars($medicamentosArray[$i], ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo htmlspecialchars($quantidadesArray[$i], ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
      <?php endfor; ?>
    </tbody>
  </table>

  <div class="row mt-4">
    <div class="col-5 text-center border p-3">
      <p><strong>SOLICITADO POR:</strong></p>
      <p>________________________________</p>
      <p>Em <?php echo htmlspecialchars($data, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <div class="col-2"></div>
    <div class="col-5 text-center border p-3">
      <p><strong>RECEBIDO POR:</strong></p>
      <p>________________________________</p>
      <p>____/____/______, às ____:____h</p>
    </div>
  </div>
</div>
<?php include 'includes/foot.php'; ?>
</body>
</html>
