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
<div class="doc-page">

  <div class="no-print mb-2">
    <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>Voltar
    </a>
  </div>

  <?php include 'includes/doc_header.php'; ?>

  <h2 class="doc-title">
    Solicitação de Medicamentos do <?php echo htmlspecialchars($_SESSION['usuario'], ENT_QUOTES, 'UTF-8'); ?>
  </h2>

  <table class="doc-table">
    <thead>
      <tr>
        <th>Medicamento/Produto</th>
        <th style="width:180px;">Quantidade</th>
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

  <div class="doc-signature-row">
    <div class="doc-signature-box">
      <p class="doc-label">SOLICITADO POR:</p>
      <div class="doc-signature-line"></div>
      <p class="doc-signature-date">Em <?php echo htmlspecialchars($data, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <div class="doc-signature-box">
      <p class="doc-label">RECEBIDO POR:</p>
      <div class="doc-signature-line"></div>
      <p class="doc-signature-date">____/____/______, às ____:____h</p>
    </div>
  </div>

  <div class="doc-btn-row no-print">
    <button onclick="window.print()" class="btn btn-success px-4">Imprimir</button>
  </div>

</div>
<?php include 'includes/foot.php'; ?>
</body>
</html>
