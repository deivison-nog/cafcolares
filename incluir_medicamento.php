<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: index.php');
    exit;
}

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo_barras = $_POST['codigo_barras'];
    $medicamento = $_POST['medicamento'];
    $apresentacao = $_POST['apresentacao'];
    $marca = $_POST['marca'];
    $lote = $_POST['lote'];
    $validade = $_POST['validade'];
    $fornecedor = $_POST['fornecedor'];
    $quantidade = $_POST['quantidade'];
    $categoria = $_POST['categoria'];
    $estabelecimento = $_SESSION['usuario'];

    $stmt = $pdo->prepare('INSERT INTO medicamentos (codigo_barras, medicamento, apresentacao, marca, lote, validade, fornecedor, quantidade, categoria, estabelecimento) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$codigo_barras, $medicamento, $apresentacao, $marca, $lote, $validade, $fornecedor, $quantidade, $categoria, $estabelecimento]);

    $success = 'Medicamento incluído com sucesso!';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Incluir Medicamento – CAF</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div id="wrapper" class="d-flex">
  <?php include 'includes/menu_lateral.php'; ?>
  <div class="main-content flex-grow-1">
    <div class="d-flex align-items-center mb-4">
      <i class="bi bi-plus-circle fs-3 me-2 text-success"></i>
      <h2 class="mb-0">Incluir Medicamento</h2>
    </div>

    <?php if (isset($success)): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i><?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <div class="card" style="max-width: 800px;">
      <div class="card-body">
        <form method="post">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="codigo_barras" class="form-label">Código de Barras</label>
              <input type="number" class="form-control" id="codigo_barras" name="codigo_barras"
                required inputmode="numeric" pattern="[0-9]*">
            </div>
            <div class="col-md-6">
              <label for="medicamento" class="form-label">Medicamento/Produto</label>
              <input type="text" class="form-control" id="medicamento" name="medicamento" required>
            </div>
            <div class="col-md-6">
              <label for="apresentacao" class="form-label">Apresentação</label>
              <input type="text" class="form-control" id="apresentacao" name="apresentacao" required>
            </div>
            <div class="col-md-6">
              <label for="marca" class="form-label">Marca</label>
              <input type="text" class="form-control" id="marca" name="marca" required>
            </div>
            <div class="col-md-6">
              <label for="lote" class="form-label">Lote</label>
              <input type="text" class="form-control" id="lote" name="lote" required>
            </div>
            <div class="col-md-6">
              <label for="validade" class="form-label">Validade</label>
              <input type="date" class="form-control" id="validade" name="validade" required>
            </div>
            <div class="col-md-6">
              <label for="fornecedor" class="form-label">Fornecedor</label>
              <input type="text" class="form-control" id="fornecedor" name="fornecedor" required>
            </div>
            <div class="col-md-6">
              <label for="quantidade" class="form-label">Quantidade</label>
              <input type="number" class="form-control" id="quantidade" name="quantidade" required>
            </div>
            <div class="col-md-6">
              <label for="categoria" class="form-label">Categoria</label>
              <select class="form-select" id="categoria" name="categoria" required>
                <option value="Farmácia Básica">Farmácia Básica</option>
                <option value="Medicamentos Injetáveis">Injetáveis</option>
                <option value="Insumos">Insumos</option>
                <option value="Saúde Mental">Saúde Mental</option>
                <option value="Saúde Bucal">Saúde Bucal</option>
                <option value="Formulas e Suplementos">Fórmulas e Suplementos</option>
              </select>
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-success">
                <i class="bi bi-plus-circle me-1"></i>Incluir Medicamento
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include 'includes/foot.php'; ?>
</body>
</html>
