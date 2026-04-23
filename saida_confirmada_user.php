<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'usuario') {
    header('Location: index.php');
    exit;
}

include 'db.php';

if (!isset($_GET['data_distribuicao']) || !isset($_GET['hora_distribuicao']) || !isset($_GET['paciente'])) {
    echo "Dados de distribuição não encontrados ou inválidos.";
    exit;
}

$dataDistribuicao = $_GET['data_distribuicao'];
$horaDistribuicao = $_GET['hora_distribuicao'];
$paciente = $_GET['paciente'];

$stmt = $pdo->prepare('
    SELECT dp.id, dp.data_distribuicao, dp.hora_distribuicao, p.nome as paciente_nome, m.medicamento, m.apresentacao, m.marca, m.lote, m.validade, dp.quantidade, u.usuario as estabelecimento
    FROM distribuicao_pacientes dp
    JOIN medicamentos m ON dp.medicamento_id = m.id
    JOIN pacientes p ON dp.paciente_id = p.id
    JOIN usuarios u ON dp.estabelecimento_id = u.id
    WHERE dp.data_distribuicao = ? AND dp.hora_distribuicao = ? AND p.nome = ?
');
$stmt->execute([$dataDistribuicao, $horaDistribuicao, $paciente]);
$saida = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($saida) == 0) {
    echo "Nenhuma distribuição encontrada para os dados fornecidos.";
    exit;
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
  <title>Saída de Medicamentos Confirmada – CAF</title>
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

  <h2 class="text-center mb-3">SAÍDA DE MEDICAMENTOS</h2>

  <p>
    <strong>Paciente:</strong> <?php echo htmlspecialchars($saida[0]['paciente_nome'], ENT_QUOTES, 'UTF-8'); ?><br>
    <strong>Data:</strong> <?php echo formatarData($saida[0]['data_distribuicao']); ?>
    &nbsp;|&nbsp; <strong>Hora:</strong> <?php echo htmlspecialchars($saida[0]['hora_distribuicao'], ENT_QUOTES, 'UTF-8'); ?>
    &nbsp;|&nbsp; <strong>Estabelecimento:</strong> <?php echo htmlspecialchars($saida[0]['estabelecimento'], ENT_QUOTES, 'UTF-8'); ?>
  </p>

  <h5>Detalhes dos Medicamentos</h5>
  <table class="table table-bordered">
    <thead class="table-light">
      <tr>
        <th>Medicamento</th>
        <th>Apresentação</th>
        <th>Marca</th>
        <th>Lote</th>
        <th>Validade</th>
        <th>Quantidade</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($saida as $item): ?>
        <tr>
          <td><?php echo htmlspecialchars($item['medicamento'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo htmlspecialchars($item['apresentacao'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo htmlspecialchars($item['marca'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo htmlspecialchars($item['lote'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo htmlspecialchars($item['validade'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo htmlspecialchars($item['quantidade'], ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="row mt-4">
    <div class="col-5 text-center border p-3">
      <p><strong>ENTREGUE POR:</strong></p>
      <p>________________________________</p>
      <p><?php echo formatarData($saida[0]['data_distribuicao']); ?>, às <?php echo htmlspecialchars($saida[0]['hora_distribuicao'], ENT_QUOTES, 'UTF-8'); ?></p>
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
