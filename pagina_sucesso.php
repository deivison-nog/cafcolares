<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'usuario') {
    header('Location: index.php');
    exit;
}

$solicitacoes = isset($_SESSION['solicitacoes']) ? $_SESSION['solicitacoes'] : [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Solicitação Concluída – CAF</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include 'includes/head.php'; ?>
<div id="wrapper" class="d-flex">
  <?php include 'includes/menu_lateral.php'; ?>
  <div class="main-content flex-grow-1">
    <div class="alert alert-success d-flex align-items-center" role="alert">
      <i class="bi bi-check-circle-fill fs-4 me-2"></i>
      <h4 class="mb-0">Solicitação Finalizada com Sucesso!</h4>
    </div>

    <p class="lead">A seguir estão os medicamentos solicitados:</p>

    <?php if (!empty($solicitacoes)): ?>
      <div class="card">
        <ul class="list-group list-group-flush">
          <?php foreach ($solicitacoes as $solicitacao): ?>
            <li class="list-group-item">
              <i class="bi bi-capsule me-2 text-primary"></i>
              <?php echo htmlspecialchars($solicitacao['medicamento']); ?>
              – Quantidade: <strong><?php echo htmlspecialchars($solicitacao['quantidade']); ?></strong>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php else: ?>
      <p class="text-muted">Nenhum medicamento foi solicitado.</p>
    <?php endif; ?>

    <?php unset($_SESSION['solicitacoes']); ?>

    <div class="mt-3">
      <a href="solicitacoes_user.php" class="btn btn-primary">
        <i class="bi bi-arrow-left me-1"></i>Nova Solicitação
      </a>
    </div>
  </div>
</div>
<?php include 'includes/foot.php'; ?>
</body>
</html>
