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
<div class="doc-page">

  <div class="no-print mb-2">
    <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>Voltar
    </a>
  </div>

  <?php include 'includes/doc_header.php'; ?>

  <h2 class="doc-title">Saída de Medicamentos</h2>

  <p class="doc-info">
    <strong>Paciente:</strong> <?php echo htmlspecialchars($saida[0]['paciente_nome'], ENT_QUOTES, 'UTF-8'); ?>&nbsp;|&nbsp;
    <strong>Data:</strong> <?php echo formatarData($saida[0]['data_distribuicao']); ?>
    &nbsp;|&nbsp;
    <strong>Hora:</strong> <?php echo htmlspecialchars($saida[0]['hora_distribuicao'], ENT_QUOTES, 'UTF-8'); ?>
    &nbsp;|&nbsp;
    <strong>Estabelecimento:</strong> <?php echo htmlspecialchars($saida[0]['estabelecimento'], ENT_QUOTES, 'UTF-8'); ?>
  </p>

  <table class="doc-table">
    <thead>
      <tr>
        <th>Medicamento</th>
        <th>Apresentação</th>
        <th>Marca</th>
        <th>Lote</th>
        <th>Validade</th>
        <th style="width:90px;">Qtd.</th>
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

  <div class="doc-signature-row">
    <div class="doc-signature-box">
      <p class="doc-label">ENTREGUE POR:</p>
      <div class="doc-signature-line"></div>
      <p class="doc-signature-date"><?php echo formatarData($saida[0]['data_distribuicao']); ?>, às <?php echo htmlspecialchars($saida[0]['hora_distribuicao'], ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <div class="doc-signature-box">
      <p class="doc-label">RECEBIDO POR:</p>
      <div class="doc-signature-line"></div>
      <p class="doc-signature-date">____/____/______, às ____:____h</p>
    </div>
  </div>

  <div class="doc-btn-row no-print">
    <button onclick="var u=new URL(location.href);u.searchParams.set('autoprint','1');window.open(u.toString(),'_blank');" class="btn btn-success px-4">Imprimir</button>
  </div>

</div>
<?php include 'includes/foot.php'; ?>
</body>
</html>
