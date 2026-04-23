<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}
include 'db.php';

$totalMeds    = $pdo->query("SELECT COUNT(*) FROM medicamentos")->fetchColumn();
$lowStock     = $pdo->query("SELECT COUNT(*) FROM medicamentos WHERE quantidade <= 10")->fetchColumn();
$expiringSoon = $pdo->query("SELECT COUNT(*) FROM medicamentos WHERE validade <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND validade >= CURDATE()")->fetchColumn();
$pendingSol   = $pdo->query("SELECT COUNT(*) FROM solicitacoes")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard – CAF</title>
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
      <div>
        <h3 class="mb-0 fw-bold">Dashboard</h3>
        <small class="text-muted">Bem-vindo(a), <strong><?php echo htmlspecialchars($_SESSION['usuario']); ?></strong></small>
      </div>
      <span class="badge bg-primary fs-6"><?php echo htmlspecialchars($_SESSION['nivel_acesso']); ?></span>
    </div>

    <?php if ($_SESSION['nivel_acesso'] === 'admin'): ?>
    <div class="row g-3 mb-4">
      <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="rounded-circle bg-primary bg-opacity-10 p-3">
              <i class="bi bi-capsule fs-3 text-primary"></i>
            </div>
            <div>
              <div class="fs-2 fw-bold"><?php echo $totalMeds; ?></div>
              <div class="text-muted small">Medicamentos cadastrados</div>
            </div>
          </div>
          <div class="card-footer bg-transparent border-0 pt-0">
            <a href="admin_estoque.php" class="btn btn-sm btn-outline-primary w-100">Ver estoque</a>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="rounded-circle bg-warning bg-opacity-10 p-3">
              <i class="bi bi-exclamation-triangle fs-3 text-warning"></i>
            </div>
            <div>
              <div class="fs-2 fw-bold"><?php echo $lowStock; ?></div>
              <div class="text-muted small">Estoque baixo (≤ 10)</div>
            </div>
          </div>
          <div class="card-footer bg-transparent border-0 pt-0">
            <a href="admin_estoque.php" class="btn btn-sm btn-outline-warning w-100">Verificar</a>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="rounded-circle bg-danger bg-opacity-10 p-3">
              <i class="bi bi-calendar-x fs-3 text-danger"></i>
            </div>
            <div>
              <div class="fs-2 fw-bold"><?php echo $expiringSoon; ?></div>
              <div class="text-muted small">Vencendo em 30 dias</div>
            </div>
          </div>
          <div class="card-footer bg-transparent border-0 pt-0">
            <a href="admin_validade.php" class="btn btn-sm btn-outline-danger w-100">Ver validades</a>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="rounded-circle bg-info bg-opacity-10 p-3">
              <i class="bi bi-clipboard-check fs-3 text-info"></i>
            </div>
            <div>
              <div class="fs-2 fw-bold"><?php echo $pendingSol; ?></div>
              <div class="text-muted small">Solicitações registradas</div>
            </div>
          </div>
          <div class="card-footer bg-transparent border-0 pt-0">
            <a href="solicitacoes_caf.php" class="btn btn-sm btn-outline-info w-100">Ver solicitações</a>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-md-6">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white fw-semibold border-bottom">
            <i class="bi bi-lightning-charge-fill text-warning me-2"></i>Ações rápidas
          </div>
          <div class="card-body">
            <div class="d-grid gap-2">
              <a href="incluir_medicamento.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Incluir Medicamento
              </a>
              <a href="distribuicao.php" class="btn btn-success">
                <i class="bi bi-truck me-2"></i>Registrar Distribuição
              </a>
              <a href="saida_medicamento.php" class="btn btn-info text-white">
                <i class="bi bi-person-lines-fill me-2"></i>Saída para Paciente
              </a>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white fw-semibold border-bottom">
            <i class="bi bi-file-earmark-bar-graph-fill text-primary me-2"></i>Relatórios
          </div>
          <div class="card-body">
            <div class="d-grid gap-2">
              <a href="relatorio.php" class="btn btn-outline-primary">
                <i class="bi bi-file-earmark-bar-graph me-2"></i>Relatório de Distribuições
              </a>
              <a href="relatorio_pacientes.php" class="btn btn-outline-secondary">
                <i class="bi bi-file-earmark-person me-2"></i>Relatório de Pacientes
              </a>
              <a href="ultima_distribuicao.php" class="btn btn-outline-secondary">
                <i class="bi bi-clock-history me-2"></i>Última Distribuição
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <?php else: /* nivel_acesso = usuario */ ?>
    <?php
    $usuario_nome = $_SESSION['usuario'];
    $myMeds  = $pdo->prepare("SELECT COUNT(*) FROM medicamentos WHERE estabelecimento = ?");
    $myMeds->execute([$usuario_nome]);
    $myMedsCount = $myMeds->fetchColumn();

    $myLow  = $pdo->prepare("SELECT COUNT(*) FROM medicamentos WHERE estabelecimento = ? AND quantidade <= 10");
    $myLow->execute([$usuario_nome]);
    $myLowCount = $myLow->fetchColumn();

    $myExp  = $pdo->prepare("SELECT COUNT(*) FROM medicamentos WHERE estabelecimento = ? AND validade <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND validade >= CURDATE()");
    $myExp->execute([$usuario_nome]);
    $myExpCount = $myExp->fetchColumn();
    ?>
    <div class="row g-3 mb-4">
      <div class="col-sm-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="rounded-circle bg-primary bg-opacity-10 p-3">
              <i class="bi bi-capsule fs-3 text-primary"></i>
            </div>
            <div>
              <div class="fs-2 fw-bold"><?php echo $myMedsCount; ?></div>
              <div class="text-muted small">Medicamentos no estoque</div>
            </div>
          </div>
          <div class="card-footer bg-transparent border-0 pt-0">
            <a href="user_estoque.php" class="btn btn-sm btn-outline-primary w-100">Ver estoque</a>
          </div>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="rounded-circle bg-warning bg-opacity-10 p-3">
              <i class="bi bi-exclamation-triangle fs-3 text-warning"></i>
            </div>
            <div>
              <div class="fs-2 fw-bold"><?php echo $myLowCount; ?></div>
              <div class="text-muted small">Estoque baixo</div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="rounded-circle bg-danger bg-opacity-10 p-3">
              <i class="bi bi-calendar-x fs-3 text-danger"></i>
            </div>
            <div>
              <div class="fs-2 fw-bold"><?php echo $myExpCount; ?></div>
              <div class="text-muted small">Vencendo em 30 dias</div>
            </div>
          </div>
          <div class="card-footer bg-transparent border-0 pt-0">
            <a href="user_validade.php" class="btn btn-sm btn-outline-danger w-100">Ver validades</a>
          </div>
        </div>
      </div>
    </div>
    <div class="row g-3">
      <div class="col-md-6">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white fw-semibold border-bottom">
            <i class="bi bi-lightning-charge-fill text-warning me-2"></i>Ações rápidas
          </div>
          <div class="card-body">
            <div class="d-grid gap-2">
              <a href="saida_medicamento_user.php" class="btn btn-primary">
                <i class="bi bi-person-lines-fill me-2"></i>Registrar Saída
              </a>
              <a href="solicitacoes_user.php" class="btn btn-success">
                <i class="bi bi-clipboard-plus me-2"></i>Solicitar Medicamentos
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>
<?php include 'includes/foot.php'; ?>
</body>
</html>
