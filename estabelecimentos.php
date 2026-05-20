<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: index.php');
    exit;
}

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'register') {
    $estabelecimento = $_POST['estabelecimento'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $nivel_acesso = $_POST['nivel_acesso'];

    $stmt = $pdo->prepare('INSERT INTO usuarios (usuario, senha, nivel_acesso) VALUES (?, ?, ?)');
    if ($stmt->execute([$estabelecimento, $senha, $nivel_acesso])) {
        $success = 'Estabelecimento registrado com sucesso!';
    } else {
        $error = 'Erro ao registrar estabelecimento. Tente novamente.';
    }

    header('Location: estabelecimentos.php?success=' . urlencode($success));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id = $_POST['id'];

    $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id = ? AND nivel_acesso = ?');
    if ($stmt->execute([$id, 'usuario'])) {
        $success = 'Estabelecimento excluído com sucesso!';
    } else {
        $error = 'Erro ao excluir estabelecimento. Tente novamente.';
    }

    header('Location: estabelecimentos.php?success=' . urlencode($success));
    exit;
}

$stmt = $pdo->query('SELECT * FROM usuarios');
$estabelecimentos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Estabelecimentos – CAF</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="css/style.css">
  <script>
    function confirmRegister() {
      return confirm('Deseja realmente registrar esse usuário?');
    }
    function confirmDelete() {
      return confirm('Deseja realmente excluir esse usuário?');
    }
  </script>
</head>
<body>

<div id="wrapper" class="d-flex">
  <?php include 'includes/menu_lateral.php'; ?>
  <div class="main-content flex-grow-1">
    <div class="d-flex align-items-center mb-4">
      <i class="bi bi-building fs-3 me-2 text-primary"></i>
      <h2 class="mb-0">Estabelecimentos Registrados</h2>
    </div>

    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i><?php echo htmlspecialchars($_GET['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
      <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i><?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <div class="card mb-4">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover table-striped mb-0">
            <thead class="table-dark">
              <tr>
                <th>Estabelecimento</th>
                <th>Nível de Acesso</th>
                <th>Ação</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($estabelecimentos as $estabelecimento): ?>
                <tr>
                  <td><?php echo htmlspecialchars($estabelecimento['usuario']); ?></td>
                  <td>
                    <span class="badge <?php echo $estabelecimento['nivel_acesso'] === 'admin' ? 'bg-danger' : 'bg-primary'; ?>">
                      <?php echo htmlspecialchars($estabelecimento['nivel_acesso']); ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($estabelecimento['nivel_acesso'] === 'usuario'): ?>
                      <form method="post" class="d-inline" onsubmit="return confirmDelete();">
                        <input type="hidden" name="id" value="<?php echo $estabelecimento['id']; ?>">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="btn btn-sm btn-danger">
                          <i class="bi bi-trash me-1"></i>Excluir
                        </button>
                      </form>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="card" style="max-width: 600px;">
      <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Registrar Novo Estabelecimento</h5>
      </div>
      <div class="card-body">
        <form method="post" onsubmit="return confirmRegister();">
          <input type="hidden" name="action" value="register">
          <div class="row g-3">
            <div class="col-12">
              <label for="estabelecimento" class="form-label">Estabelecimento</label>
              <input type="text" class="form-control" id="estabelecimento" name="estabelecimento" required>
            </div>
            <div class="col-12">
              <label for="senha" class="form-label">Senha</label>
              <input type="password" class="form-control" id="senha" name="senha" required>
            </div>
            <div class="col-12">
              <label for="nivel_acesso" class="form-label">Nível de Acesso</label>
              <select class="form-select" id="nivel_acesso" name="nivel_acesso" required>
                <option value="usuario">Usuário</option>
                <option value="admin">Admin</option>
              </select>
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-success">
                <i class="bi bi-person-plus me-1"></i>Registrar Estabelecimento
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
