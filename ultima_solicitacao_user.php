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

try {
    $sql_ultima_data = "
        SELECT MAX(data_solicitacao) AS ultima_data
        FROM solicitacoes
        WHERE usuario_id = :usuario_id
    ";
    $stmt = $pdo->prepare($sql_ultima_data);
    $stmt->bindParam(':usuario_id', $usuario_id);
    $stmt->execute();
    $ultima_data = $stmt->fetchColumn();
} catch (PDOException $e) {
    echo 'Erro na consulta: ' . $e->getMessage();
}

try {
    $sql_solicitacao = "
        SELECT solicitacoes.*, medicamentos.medicamento
        FROM solicitacoes
        JOIN medicamentos ON solicitacoes.medicamento_id = medicamentos.id
        WHERE solicitacoes.usuario_id = :usuario_id
        AND solicitacoes.data_solicitacao = :ultima_data
        ORDER BY solicitacoes.id
    ";
    $stmt = $pdo->prepare($sql_solicitacao);
    $stmt->bindParam(':usuario_id', $usuario_id);
    $stmt->bindParam(':ultima_data', $ultima_data);
    $stmt->execute();
    $solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo 'Erro na consulta: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Última Solicitação – CAF</title>
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
    Solicitação de Medicamentos do(a) <?php echo htmlspecialchars($usuario_nome); ?>
  </h2>

  <?php if ($solicitacoes): ?>
    <table class="table table-bordered">
      <thead class="table-light">
        <tr>
          <th>Medicamento/Produto</th>
          <th>Quantidade</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($solicitacoes as $solicitacao): ?>
          <tr>
            <td><?php echo htmlspecialchars($solicitacao['medicamento']); ?></td>
            <td><?php echo htmlspecialchars($solicitacao['quantidade']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="row mt-4">
      <div class="col-5 text-center border p-3">
        <p><strong>SOLICITADO POR:</strong></p>
        <p>________________________________</p>
        <p>Em <?php echo date('d/m/y \à\s H:i', strtotime($solicitacao['data_solicitacao'])); ?></p>
      </div>
      <div class="col-2"></div>
      <div class="col-5 text-center border p-3">
        <p><strong>RECEBIDO POR:</strong></p>
        <p>________________________________</p>
        <p>____/____/______, às ____:____h</p>
      </div>
    </div>
  <?php else: ?>
    <p>Nenhuma solicitação encontrada.</p>
  <?php endif; ?>
</div>
<?php include 'includes/foot.php'; ?>
</body>
</html>
