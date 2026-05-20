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
<div class="doc-page">

  <div class="no-print mb-2">
    <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>Voltar
    </a>
  </div>

  <?php include 'includes/doc_header.php'; ?>

  <h2 class="doc-title">
    Solicitação de Medicamentos do(a) <?php echo htmlspecialchars($usuario_nome); ?>
  </h2>

  <?php if ($solicitacoes): ?>
    <table class="doc-table">
      <thead>
        <tr>
          <th>Medicamento/Produto</th>
          <th style="width:180px;">Quantidade</th>
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

    <div class="doc-signature-row">
      <div class="doc-signature-box">
        <p class="doc-label">SOLICITADO POR:</p>
        <div class="doc-signature-line"></div>
        <p class="doc-signature-date">Em <?php echo date('d/m/Y, \à\s H:i', strtotime($solicitacao['data_solicitacao'])); ?></p>
      </div>
      <div class="doc-signature-box">
        <p class="doc-label">RECEBIDO POR:</p>
        <div class="doc-signature-line"></div>
        <p class="doc-signature-date">____/____/______, às ____:____h</p>
      </div>
    </div>
  <?php else: ?>
    <p>Nenhuma solicitação encontrada.</p>
  <?php endif; ?>

  <div class="doc-btn-row no-print">
    <button onclick="var sep=location.search?'&':'?';window.open(location.href+sep+'autoprint=1','_blank');" class="btn btn-success px-4">Imprimir</button>
  </div>

</div>
<?php include 'includes/foot.php'; ?>
</body>
</html>
