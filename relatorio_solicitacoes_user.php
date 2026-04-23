<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'usuario') {
    header('Location: index.php');
    exit;
}

include 'db.php';

$usuario_nome = $_SESSION['usuario'];

$stmt = $pdo->prepare('SELECT id FROM usuarios WHERE usuario = ?');
$stmt->execute([$usuario_nome]);
$user = $stmt->fetch();

if ($user) {
    $usuario_id = $user['id'];
} else {
    echo "Erro: usuário não encontrado.";
    exit;
}

$stmt = $pdo->prepare('SELECT id, medicamento FROM medicamentos WHERE estabelecimento = ?');
$stmt->execute(['caf']);
$medicamentos = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT solicitacoes.*, medicamentos.medicamento
                       FROM solicitacoes
                       JOIN medicamentos ON solicitacoes.medicamento_id = medicamentos.id
                       WHERE solicitacoes.usuario_id = ?
                       ORDER BY solicitacoes.data_solicitacao DESC
                       LIMIT 5');
$stmt->execute([$usuario_id]);
$ultimos_pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($ultimos_pedidos === false) {
    $ultimos_pedidos = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Relatório de Solicitações – CAF</title>
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
      <i class="bi bi-clipboard-data fs-3 me-2 text-primary"></i>
      <h2 class="mb-0">Relatório de Solicitações de Medicamentos</h2>
    </div>

    <div class="card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover table-striped mb-0">
            <thead class="table-dark">
              <tr>
                <th>Medicamento</th>
                <th>Quantidade</th>
                <th>Data da Solicitação</th>
                <th>Ação</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($ultimos_pedidos)): ?>
                <?php
                $grouped_pedidos = [];
                foreach ($ultimos_pedidos as $pedido) {
                    $data = date('d/m/Y, \à\s H:i', strtotime($pedido['data_solicitacao']));
                    if (!isset($grouped_pedidos[$data])) {
                        $grouped_pedidos[$data] = [
                            'medicamentos' => [],
                            'quantidades' => [],
                            'data' => $data
                        ];
                    }
                    $grouped_pedidos[$data]['medicamentos'][] = $pedido['medicamento'];
                    $grouped_pedidos[$data]['quantidades'][] = $pedido['quantidade'];
                }
                ?>
                <?php foreach ($grouped_pedidos as $group): ?>
                  <tr>
                    <td><?php echo htmlspecialchars(implode(', ', $group['medicamentos'])); ?></td>
                    <td><?php echo htmlspecialchars(implode(', ', $group['quantidades'])); ?></td>
                    <td><?php echo htmlspecialchars($group['data']); ?></td>
                    <td>
                      <form method="get" action="solicitacoes_confirmadas_user.php" class="d-inline">
                        <input type="hidden" name="medicamentos" value="<?php echo urlencode(implode(', ', $group['medicamentos'])); ?>">
                        <input type="hidden" name="quantidades" value="<?php echo urlencode(implode(', ', $group['quantidades'])); ?>">
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
                  <td colspan="4" class="text-center py-3">Nenhuma solicitação recente encontrada.</td>
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
</body>
</html>
