<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: index.php');
    exit;
}

include 'db.php';

$data_distribuicao = $_GET['data_distribuicao'] ?? '';
$hora_distribuicao = $_GET['hora_distribuicao'] ?? '';
$paciente_nome = $_GET['paciente'] ?? '';

if ($data_distribuicao && $hora_distribuicao && $paciente_nome) {
    $stmt = $pdo->prepare('
        SELECT dp.*, m.medicamento, m.apresentacao, m.marca, m.lote, m.validade, u.usuario as estabelecimento, p.nome as paciente
        FROM distribuicao_pacientes dp
        JOIN medicamentos m ON dp.medicamento_id = m.id
        JOIN usuarios u ON dp.estabelecimento_id = u.id
        JOIN pacientes p ON dp.paciente_id = p.id
        WHERE dp.data_distribuicao = :data_distribuicao
          AND dp.hora_distribuicao = :hora_distribuicao
          AND p.nome = :paciente_nome
    ');
    $stmt->execute([
        'data_distribuicao' => $data_distribuicao,
        'hora_distribuicao' => $hora_distribuicao,
        'paciente_nome' => $paciente_nome
    ]);
    $saidas = $stmt->fetchAll();

    $data_saida = $data_distribuicao;
    $hora_saida = $hora_distribuicao;
    $nome_paciente = $paciente_nome;
} else {
    $saidas = [];
    $data_saida = '';
    $hora_saida = '';
    $nome_paciente = '';
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

  <?php if ($data_saida): ?>
    <p><strong>Data:</strong> <?php echo formatarData($data_saida); ?>
    &nbsp;|&nbsp; <strong>Hora:</strong> <?php echo htmlspecialchars($hora_saida); ?>
    &nbsp;|&nbsp; <strong>Paciente:</strong> <?php echo htmlspecialchars($nome_paciente); ?></p>
  <?php endif; ?>

  <table class="table table-bordered">
    <thead class="table-light">
      <tr>
        <th>Medicamento/Produto</th>
        <th>Quantidade</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($saidas): ?>
        <?php foreach ($saidas as $saida): ?>
          <tr>
            <td><?php echo htmlspecialchars($saida['medicamento']); ?></td>
            <td><?php echo htmlspecialchars($saida['quantidade']); ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="2" class="text-center">Nenhuma saída encontrada.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>

  <?php if ($data_saida): ?>
  <div class="row mt-4">
    <div class="col-5 text-center border p-3">
      <p><strong>ENTREGUE POR:</strong></p>
      <p>________________________________</p>
      <p><?php echo formatarData($data_saida); ?>, às <?php echo htmlspecialchars($hora_saida); ?></p>
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
