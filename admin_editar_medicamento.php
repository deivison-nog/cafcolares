<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Conexão com o banco de dados
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

// Atualizar medicamento
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

// Excluir medicamento
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
    <title>Editar Medicamento - CAF</title>
    <link rel="stylesheet" href="css/style.css">
    <script>
        function confirmDelete() {
            return confirm('Deseja realmente excluir esse medicamento?');
        }
    </script>
</head>
<body>
    <?php include 'includes/head.php'; ?>
    <div class="container">
        <?php include 'includes/menu_lateral.php'; ?>
        <div class="main-content">
            <h2>Editar Medicamento</h2>
            <form method="post">
                <div>
                    <label for="nome">Nome:</label>
                    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($medicamento['medicamento']); ?>" required>
                </div>
                <div>
                    <label for="apresentacao">Apresentação:</label>
                    <input type="text" id="apresentacao" name="apresentacao" value="<?php echo htmlspecialchars($medicamento['apresentacao']); ?>" required>
                </div>
                <div>
                    <label for="marca">Marca:</label>
                    <input type="text" id="marca" name="marca" value="<?php echo htmlspecialchars($medicamento['marca']); ?>" required>
                </div>
                <div>
                    <label for="lote">Lote:</label>
                    <input type="text" id="lote" name="lote" value="<?php echo htmlspecialchars($medicamento['lote']); ?>" required>
                </div>
                <div>
                    <label for="validade">Validade:</label>
                    <input type="date" id="validade" name="validade" value="<?php echo htmlspecialchars($medicamento['validade']); ?>" required>
                </div>
                <div>
                    <label for="fornecedor">Fornecedor:</label>
                    <input type="text" id="fornecedor" name="fornecedor" value="<?php echo htmlspecialchars($medicamento['fornecedor']); ?>" required>
                </div>
                <div>
                    <label for="quantidade">Quantidade:</label>
                    <input type="number" id="quantidade" name="quantidade" value="<?php echo htmlspecialchars($medicamento['quantidade']); ?>" required>
                </div>
                <button type="submit" name="update">Atualizar</button> <br>
            </form>
            <form method="post" onsubmit="return confirmDelete();">
                <button type="submit" name="delete">Excluir Medicamento</button>
            </form>
        </div>
    </div>
    <?php include 'includes/foot.php'; ?>
</body>
</html>
