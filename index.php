<?php
session_start();
include 'db.php';

// Redirect if already logged in
if (isset($_SESSION['usuario'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $senha   = $_POST['senha'] ?? '';

    if ($usuario !== '' && $senha !== '') {
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE usuario = ?');
        $stmt->execute([$usuario]);
        $user = $stmt->fetch();

        // Support both password_hash (new) and MD5 (legacy) passwords
        $valid = false;
        if ($user) {
            if (password_verify($senha, $user['senha'])) {
                $valid = true;
            } elseif ($user['senha'] === md5($senha)) {
                // Legacy MD5 – accept and upgrade to bcrypt
                $valid = true;
                $newHash = password_hash($senha, PASSWORD_DEFAULT);
                $upd = $pdo->prepare('UPDATE usuarios SET senha = ? WHERE id = ?');
                $upd->execute([$newHash, $user['id']]);
            }
        }

        if ($valid) {
            session_regenerate_id(true);
            $_SESSION['usuario']      = $user['usuario'];
            $_SESSION['usuario_id']   = $user['id'];
            $_SESSION['nivel_acesso'] = $user['nivel_acesso'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Usuário ou senha incorretos.';
        }
    } else {
        $error = 'Preencha todos os campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login – CAF Colares</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body { background: linear-gradient(135deg, #0d3b8e 0%, #1565c0 100%); min-height: 100vh; }
    .login-card { border-radius: 1rem; box-shadow: 0 8px 32px rgba(0,0,0,0.25); }
    .brand-icon { font-size: 3rem; color: #1565c0; }
  </style>
</head>
<body class="d-flex align-items-center justify-content-center">
  <div class="container" style="max-width:420px;">
    <div class="card login-card border-0">
      <div class="card-body p-5">
        <div class="text-center mb-4">
          <i class="bi bi-capsule brand-icon"></i>
          <h4 class="fw-bold mt-2 text-dark">CAF – Colares</h4>
          <p class="text-muted small">Central de Abastecimento Farmacêutico</p>
        </div>
        <?php if ($error): ?>
          <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>
        <form method="post" novalidate>
          <div class="mb-3">
            <label for="usuario" class="form-label fw-semibold">Usuário</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-person"></i></span>
              <input type="text" id="usuario" name="usuario" class="form-control" placeholder="Seu usuário" required autofocus
                     value="<?php echo htmlspecialchars($_POST['usuario'] ?? ''); ?>">
            </div>
          </div>
          <div class="mb-4">
            <label for="senha" class="form-label fw-semibold">Senha</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock"></i></span>
              <input type="password" id="senha" name="senha" class="form-control" placeholder="Sua senha" required>
            </div>
          </div>
          <button type="submit" class="btn btn-primary w-100 fw-semibold">
            <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
          </button>
        </form>
      </div>
      <div class="card-footer text-center text-muted small py-3 bg-light rounded-bottom">
        Prefeitura Municipal de Colares – Secretaria de Saúde
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
