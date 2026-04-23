<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Conexão com o banco de dados
include 'db.php';

// Tratamento do formulário de inclusão de medicamento
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo_barras = $_POST['codigo_barras'];
    $medicamento = $_POST['medicamento'];
    $apresentacao = $_POST['apresentacao'];
    $marca = $_POST['marca'];
    $lote = $_POST['lote'];
    $validade = $_POST['validade'];
    $fornecedor = $_POST['fornecedor'];
    $quantidade = $_POST['quantidade'];
    $categoria = $_POST['categoria']; // Captura a categoria selecionada
    $estabelecimento = $_SESSION['usuario']; // Captura o nome do usuário logado

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
    <title>Incluir Medicamento - CAF</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/head.php'; ?>
    <div class="container">
        <?php include 'includes/menu_lateral.php'; ?>
        <div class="main-content">
            <div class="form-container">
                <h2>Incluir Medicamento</h2>
                <?php if (isset($success)) echo "<p>$success</p>"; ?>
                <form method="post">
                    <div>
                      <label for="codigo_barras">Código de Barras:</label>
                      <input type="number" id="codigo_barras" name="codigo_barras" required inputmode="numeric" pattern="[0-9]*">
                    </div>

                    <div>
                        <label for="medicamento">Medicamento/Produto:</label>
                        <input type="text" id="medicamento" name="medicamento" required>
                    </div>

                    <div>
                        <label for="apresentacao">Apresentação:</label>
                        <input type="text" id="apresentacao" name="apresentacao" required>
                    </div>

                    <div>
                        <label for="marca">Marca:</label>
                        <input type="text" id="marca" name="marca" required>
                    </div>

                    <div>
                        <label for="lote">Lote:</label>
                        <input type="text" id="lote" name="lote" required>
                    </div>

                    <div>
                        <label for="validade">Validade:</label>
                        <input type="date" id="validade" name="validade" required>
                    </div>

                    <div>
                        <label for="fornecedor">Fornecedor:</label>
                        <input type="text" id="fornecedor" name="fornecedor" required>
                    </div>

                    <div>
                        <label for="quantidade">Quantidade:</label>
                        <input type="number" id="quantidade" name="quantidade" required>
                    </div>

                    <div>
                        <label for="categoria">Categoria:</label>
                        <select id="categoria" name="categoria" required>
                            <option value="Farmácia Básica">Farmácia Básica</option>
                            <option value="Medicamentos Injetáveis">Injetáveis</option>
                            <option value="Insumos">Insumos</option>
                            <option value="Saúde Mental">Saúde Mental</option>
                            <option value="Saúde Mental">Saúde Bucal</option>
                            <option value="Saúde Mental">Formulas e Suplementos</option>
                        </select>
                    </div>

                    <button type="submit">Incluir Medicamento</button>
                </form>
            </div>
        </div>
    </div>
    <?php include 'includes/foot.php'; ?>
</body>
</html>
