<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: index.php');
    exit;
}

include 'db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare('SELECT * FROM medicamentos WHERE id = ?');
    $stmt->execute([$id]);
    $medicamento = $stmt->fetch();

    if (!$medicamento) {
        echo 'Medicamento não encontrado.';
        exit;
    }
} else {
    echo 'ID do medicamento não fornecido.';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $nome = $_POST['nome'];
    $apresentacao = $_POST['apresentacao'];
    $marca = $_POST['marca'];
    $lote = $_POST['lote'];
    $validade = $_POST['validade'];
    $fornecedor = $_POST['fornecedor'];
    $quantidade = $_POST['quantidade'];

    $stmt = $pdo->prepare('UPDATE medicamentos SET medicamento = ?, apresentacao = ?, marca = ?, lote = ?, validade = ?, fornecedor = ?, quantidade = ? WHERE id = ?');
    $stmt->execute([$nome, $apresentacao, $marca, $lote, $validade, $fornecedor, $quantidade, $id]);

    echo 'Medicamento atualizado com sucesso!';
    header("Location: admin_estoque.php?id=$id");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
    $stmt = $pdo->prepare('DELETE FROM medicamentos WHERE id = ?');
    $stmt->execute([$id]);

    echo 'Medicamento excluído com sucesso!';
    header('Location: admin_estoque.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Editar Medicamento – CAF</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="css/style.css">
  <script>
    function confirmDelete() {
      return confirm('Deseja realmente excluir esse medicamento?');
    }
  </script>
</head>
<body>

<div id="wrapper" class="d-flex">
  <?php include 'includes/menu_lateral.php'; ?>
  <div class="main-content flex-grow-1">
    <div class="d-flex align-items-center mb-4">
      <i class="bi bi-pencil-square fs-3 me-2 text-primary"></i>
      <h2 class="mb-0">Editar Medicamento</h2>
    </div>

    <div class="card" style="max-width: 700px;">
      <div class="card-body">
        <form method="post">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="nome" class="form-label">Nome</label>
              <input type="text" class="form-control" id="nome" name="nome"
                value="<?php echo htmlspecialchars($medicamento['medicamento']); ?>" required>
            </div>
            <div class="col-md-6">
              <label for="apresentacao" class="form-label">Apresentação</label>
              <input type="text" class="form-control" id="apresentacao" name="apresentacao"
                value="<?php echo htmlspecialchars($medicamento['apresentacao']); ?>" required>
            </div>
            <div class="col-md-6">
              <label for="marca" class="form-label">Marca</label>
              <input type="text" class="form-control" id="marca" name="marca"
                value="<?php echo htmlspecialchars($medicamento['marca']); ?>" required>
            </div>
            <div class="col-md-6">
              <label for="lote" class="form-label">Lote</label>
              <input type="text" class="form-control" id="lote" name="lote"
                value="<?php echo htmlspecialchars($medicamento['lote']); ?>" required>
            </div>
            <div class="col-md-6">
              <label for="validade" class="form-label">Validade</label>
              <input type="date" class="form-control" id="validade" name="validade"
                value="<?php echo htmlspecialchars($medicamento['validade']); ?>" required>
            </div>
            <div class="col-md-6">
              <label for="fornecedor" class="form-label">Fornecedor</label>
              <input type="text" class="form-control" id="fornecedor" name="fornecedor"
                value="<?php echo htmlspecialchars($medicamento['fornecedor']); ?>" required>
            </div>
            <div class="col-md-6">
              <label for="quantidade" class="form-label">Quantidade</label>
              <input type="number" class="form-control" id="quantidade" name="quantidade"
                value="<?php echo htmlspecialchars($medicamento['quantidade']); ?>" required>
            </div>
            <div class="col-12 d-flex gap-2 mt-2">
              <button type="submit" name="update" class="btn btn-primary">
                <i class="bi bi-check-circle me-1"></i>Atualizar
              </button>
              <a href="admin_estoque.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i>Voltar
              </a>
            </div>
          </div>
        </form>

        <hr class="my-4">

        <form method="post" onsubmit="return confirmDelete();">
          <button type="submit" name="delete" class="btn btn-danger">
            <i class="bi bi-trash me-1"></i>Excluir Medicamento
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include 'includes/foot.php'; ?>
</body>
</html>
