<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: index.php');
    exit;
}

date_default_timezone_set('America/Sao_Paulo');
include 'db.php';

try {
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE usuario = ? LIMIT 1');
    $stmt->execute([$_SESSION['usuario']]);
    $usuario_id = $stmt->fetchColumn();

    if (!$usuario_id) {
        throw new Exception('Usuário não encontrado.');
    }

    $stmt = $pdo->prepare('
        SELECT data_distribuicao, hora_distribuicao
        FROM distribuicao_pacientes
        WHERE estabelecimento_id = ?
        ORDER BY data_distribuicao DESC, hora_distribuicao DESC
        LIMIT 1
    ');
    $stmt->execute([$usuario_id]);
    $ultima_saida = $stmt->fetch();

    if ($ultima_saida) {
        $stmt = $pdo->prepare('
            SELECT dp.*, m.medicamento, m.apresentacao, p.nome as paciente
            FROM distribuicao_pacientes dp
            JOIN medicamentos m ON dp.medicamento_id = m.id
            JOIN pacientes p ON dp.paciente_id = p.id
            WHERE dp.data_distribuicao = ? AND dp.hora_distribuicao = ? AND dp.estabelecimento_id = ?
        ');
        $stmt->execute([
            $ultima_saida['data_distribuicao'],
            $ultima_saida['hora_distribuicao'],
            $usuario_id
        ]);
        $saidas = $stmt->fetchAll();
    } else {
        $saidas = [];
    }

} catch (Exception $e) {
    echo 'Erro: ' . $e->getMessage();
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
  <title>Última Saída de Medicamentos – CAF</title>
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
    <p class="doc-info">
      <strong>Data:</strong> <?php echo formatarData($ultima_saida['data_distribuicao']); ?>
      &nbsp;|&nbsp;
      <strong>Hora:</strong> <?php echo htmlspecialchars($ultima_saida['hora_distribuicao']); ?>
      &nbsp;|&nbsp;
      <strong>Paciente:</strong> <?php echo htmlspecialchars($paciente); ?>
    </p>
  <?php else: ?>
    <p>Nenhuma saída registrada ainda.</p>
  <?php endif; ?>

  <table class="doc-table">
    <thead>
      <tr>
        <th>Medicamento/Produto</th>
        <th style="width:180px;">Quantidade</th>
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
  <div class="doc-signature-row">
    <div class="doc-signature-box">
      <p class="doc-label">ENTREGUE POR:</p>
      <div class="doc-signature-line"></div>
      <p class="doc-signature-date"><?php echo formatarData($ultima_saida['data_distribuicao']); ?>, às <?php echo htmlspecialchars($ultima_saida['hora_distribuicao']); ?></p>
    </div>
    <div class="doc-signature-box">
      <p class="doc-label">RECEBIDO POR:</p>
      <div class="doc-signature-line"></div>
      <p class="doc-signature-date">____/____/______, às ____:____h</p>
    </div>
  </div>
  <?php endif; ?>

  <div class="doc-btn-row no-print">
    <button onclick="window.print()" class="btn btn-success px-4">Imprimir</button>
  </div>

</div>
<?php include 'includes/foot.php'; ?>
</body>
</html>
