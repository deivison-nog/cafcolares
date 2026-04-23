<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: index.php');
    exit;
}

include 'db.php';

$stmt = $pdo->prepare('
    SELECT d.data_distribuicao, d.hora_distribuicao, u.usuario AS estabelecimento
    FROM distribuicao d
    JOIN usuarios u ON d.estabelecimento_id = u.id
    ORDER BY d.data_distribuicao DESC, d.hora_distribuicao DESC
    LIMIT 1
');
$stmt->execute();
$ultima_distribuicao = $stmt->fetch();

if ($ultima_distribuicao) {
    $ultima_data = $ultima_distribuicao['data_distribuicao'];
    $ultima_hora = $ultima_distribuicao['hora_distribuicao'];
    $estabelecimento = $ultima_distribuicao['estabelecimento'];

    $stmt = $pdo->prepare('
        SELECT d.*, m.medicamento, m.apresentacao, m.categoria, m.marca, m.lote, m.validade, u.usuario as estabelecimento
        FROM distribuicao d
        JOIN medicamentos m ON d.medicamento_id = m.id
        JOIN usuarios u ON d.estabelecimento_id = u.id
        WHERE d.data_distribuicao = :ultima_data AND d.hora_distribuicao = :ultima_hora
    ');
    $stmt->execute(['ultima_data' => $ultima_data, 'ultima_hora' => $ultima_hora]);
    $distribuicoes = $stmt->fetchAll();
} else {
    $distribuicoes = [];
    $ultima_data = '';
    $ultima_hora = '';
    $estabelecimento = '';
}

function formatarData($data) {
    $date = new DateTime($data);
    return $date->format('d/m/Y');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Última Distribuição – CAF</title>
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

  <h2 class="text-center mb-3">DISTRIBUIÇÃO PARA ESTABELECIMENTOS</h2>

  <?php if ($ultima_data): ?>
    <p><strong>Distribuição Registrada:</strong>
    Data: <?php echo formatarData($ultima_data); ?>
    &nbsp;|&nbsp; Hora: <?php echo htmlspecialchars($ultima_hora); ?>
    &nbsp;|&nbsp; Estabelecimento: <?php echo htmlspecialchars($estabelecimento); ?></p>
  <?php endif; ?>

  <table class="table table-bordered">
    <thead class="table-light">
      <tr>
        <th>Medicamento/Produto</th>
        <th>Apresentação</th>
        <th>Categoria</th>
        <th>Marca</th>
        <th>Lote</th>
        <th>Validade</th>
        <th>Quantidade</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($distribuicoes): ?>
        <?php foreach ($distribuicoes as $distribuicao): ?>
          <tr>
            <td><?php echo htmlspecialchars($distribuicao['medicamento']); ?></td>
            <td><?php echo htmlspecialchars($distribuicao['apresentacao']); ?></td>
            <td><?php echo htmlspecialchars($distribuicao['categoria']); ?></td>
            <td><?php echo htmlspecialchars($distribuicao['marca']); ?></td>
            <td><?php echo htmlspecialchars($distribuicao['lote']); ?></td>
            <td><?php echo formatarData($distribuicao['validade']); ?></td>
            <td><?php echo htmlspecialchars($distribuicao['quantidade']); ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="7" class="text-center">Nenhuma distribuição encontrada.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>

  <?php if ($ultima_data): ?>
  <div class="row mt-4">
    <div class="col-5 text-center border p-3">
      <p><strong>ENTREGUE POR:</strong></p>
      <p>________________________________</p>
      <p><?php echo formatarData($ultima_data); ?>, às <?php echo htmlspecialchars($ultima_hora); ?></p>
    </div>
    <div class="col-2"></div>
    <div class="col-5 text-center border p-3">
      <p><strong>RECEBIDO POR:</strong></p>
      <p>________________________________</p>
      <p>____/____/______, às ____:____h</p>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php include 'includes/foot.php'; ?>
</body>
</html>
