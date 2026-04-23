<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'usuario') {
    header('Location: index.php');
    exit;
}

include 'db.php';

$stmt = $pdo->prepare('
    SELECT data_distribuicao, hora_distribuicao
    FROM distribuicao_pacientes
    WHERE estabelecimento_id = (SELECT id FROM usuarios WHERE usuario = ?)
    ORDER BY data_distribuicao DESC, hora_distribuicao DESC
    LIMIT 1
');
$stmt->execute([$_SESSION['usuario']]);
$ultima_saida = $stmt->fetch();

if ($ultima_saida) {
    $stmt = $pdo->prepare('
        SELECT dp.*, m.medicamento, m.apresentacao, p.nome as paciente
        FROM distribuicao_pacientes dp
        JOIN medicamentos m ON dp.medicamento_id = m.id
        JOIN pacientes p ON dp.paciente_id = p.id
        WHERE dp.data_distribuicao = ? AND dp.hora_distribuicao = ?
        AND dp.estabelecimento_id = (SELECT id FROM usuarios WHERE usuario = ?)
    ');
    $stmt->execute([
        $ultima_saida['data_distribuicao'],
        $ultima_saida['hora_distribuicao'],
        $_SESSION['usuario']
    ]);
    $saidas = $stmt->fetchAll();
} else {
    $saidas = [];
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
  <title>Última Saída de Medicamentos – CAF</title>
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

  <?php if ($ultima_saida): ?>
    <?php
    $stmt = $pdo->prepare('
        SELECT p.nome as paciente
        FROM distribuicao_pacientes dp
        JOIN pacientes p ON dp.paciente_id = p.id
        WHERE dp.data_distribuicao = ? AND dp.hora_distribuicao = ?
        LIMIT 1
    ');
    $stmt->execute([$ultima_saida['data_distribuicao'], $ultima_saida['hora_distribuicao']]);
    $paciente = $stmt->fetchColumn();
    ?>
    <p><strong>Data:</strong> <?php echo formatarData($ultima_saida['data_distribuicao']); ?>
    &nbsp;|&nbsp; <strong>Hora:</strong> <?php echo htmlspecialchars($ultima_saida['hora_distribuicao']); ?>
    &nbsp;|&nbsp; <strong>Paciente:</strong> <?php echo htmlspecialchars($paciente); ?></p>
  <?php else: ?>
    <p>Nenhuma saída registrada ainda.</p>
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

  <?php if ($ultima_saida): ?>
  <div class="row mt-4">
    <div class="col-5 text-center border p-3">
      <p><strong>ENTREGUE POR:</strong></p>
      <p>________________________________</p>
      <p><?php echo formatarData($ultima_saida['data_distribuicao']); ?>, às <?php echo htmlspecialchars($ultima_saida['hora_distribuicao']); ?></p>
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
