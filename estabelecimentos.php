<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Conexão com o banco de dados
include 'db.php';

// Tratamento do formulário de registro de novo estabelecimento
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'register') {
    $estabelecimento = $_POST['estabelecimento'];
    $senha = md5($_POST['senha']); // Hash da senha usando MD5
    $nivel_acesso = $_POST['nivel_acesso'];

    $stmt = $pdo->prepare('INSERT INTO usuarios (usuario, senha, nivel_acesso) VALUES (?, ?, ?)');
    if ($stmt->execute([$estabelecimento, $senha, $nivel_acesso])) {
        $success = 'Estabelecimento registrado com sucesso!';
    } else {
        $error = 'Erro ao registrar estabelecimento. Tente novamente.';
    }

    // Redireciona para evitar reenvio do formulário
    header('Location: estabelecimentos.php?success=' . urlencode($success));
    exit;
}

// Tratamento da exclusão de estabelecimento
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id = $_POST['id'];

    $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id = ? AND nivel_acesso = ?');
    if ($stmt->execute([$id, 'usuario'])) {
        $success = 'Estabelecimento excluído com sucesso!';
    } else {
        $error = 'Erro ao excluir estabelecimento. Tente novamente.';
    }

    // Redireciona para evitar reenvio do formulário
    header('Location: estabelecimentos.php?success=' . urlencode($success));
    exit;
}

// Consulta todos os estabelecimentos
$stmt = $pdo->query('SELECT * FROM usuarios');
$estabelecimentos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estabelecimentos - CAF</title>
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
    <?php include 'includes/head.php'; ?>
    <div class="container">
        <?php include 'includes/menu_lateral.php'; ?>
        <div class="main-content">
            <h2>Estabelecimentos Registrados</h2>
            <?php if (isset($_GET['success'])) echo "<p>" . htmlspecialchars($_GET['success']) . "</p>"; ?>
            <?php if (isset($error)) echo "<p>$error</p>"; ?>

            <table>
                <thead>
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
                            <td><?php echo htmlspecialchars($estabelecimento['nivel_acesso']); ?></td>
                            <td>
                                <?php if ($estabelecimento['nivel_acesso'] === 'usuario'): ?>
                                    <form method="post" style="display:inline;" onsubmit="return confirmDelete();">
                                        <input type="hidden" name="id" value="<?php echo $estabelecimento['id']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit">Excluir</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2>Registrar Novo Estabelecimento</h2>
            <form method="post" onsubmit="return confirmRegister();">
                <input type="hidden" name="action" value="register">
                <div>
                    <label for="estabelecimento">Estabelecimento:</label>
                    <input type="text" id="estabelecimento" name="estabelecimento" required>
                </div>
                <div>
                    <label for="senha">Senha:</label>
                    <input type="password" id="senha" name="senha" required>
                </div>
                <div>
                    <label for="nivel_acesso">Nível de Acesso:</label>
                    <select id="nivel_acesso" name="nivel_acesso" required>
                        <option value="usuario">Usuário</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button type="submit">Registrar Estabelecimento</button>
            </form>
        </div>
    </div>
    <?php include 'includes/foot.php'; ?>
</body>
</html>
