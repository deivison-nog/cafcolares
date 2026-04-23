<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'usuario') {
    header('Location: index.php');
    exit;
}

// Conexão com o banco de dados
include 'db.php';

// Obter o nome do usuário logado
$usuario_nome = $_SESSION['usuario'];

// Variável para pesquisa
$pesquisaNome = '';

// Consulta SQL básica com ordenação
$sql = 'SELECT * FROM medicamentos WHERE estabelecimento = ?';

// Aplicando pesquisa por nome de medicamento
if (isset($_GET['pesquisa']) && $_GET['pesquisa'] != '') {
    $pesquisaNome = $_GET['pesquisa'];
    $sql .= ' AND medicamento LIKE ?';
}

// Adicionando a cláusula ORDER BY para ordenar pela quantidade
$sql .= ' ORDER BY quantidade ASC';

// Preparar a consulta
$stmt = $pdo->prepare($sql);

// Bind dos parâmetros
if ($pesquisaNome != '') {
    $medicamentoFiltro = '%' . $pesquisaNome . '%';
    $stmt->execute([$usuario_nome, $medicamentoFiltro]);
} else {
    $stmt->execute([$usuario_nome]);
}

// Executar a consulta
$medicamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuário Estoque - CAF</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .header-saida {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .header-saida img {
            height: 60px;
        }

        .header-text {
            text-align: center;
            flex-grow: 1;
        }

        .header-text h1,
        .header-text h2,
        .header-text h3 {
            margin: 0;
        }

        .container {
            display: flex;
            flex-direction: column;
            margin-top: 70px;
            padding-top: 20px;
        }

        .main-content {
            flex-grow: 1;
            padding: 15px;
            margin-left: 220px;
            margin-bottom: 50px;
            overflow-y: auto;
        }

        h2, h3 {
            margin-top: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .print-button {
            margin-top: 20px;
            text-align: right;
        }

        .print-button button {
            padding: 10px 20px;
            font-size: 16px;
            background-color: #28a745;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .print-button button:hover {
            background-color: #218838;
        }

        @media print {
            body {
                background-color: #fff; /* Define o fundo da impressão como branco */
                -webkit-print-color-adjust: exact; /* Garante que as cores sejam impressas como no design */
            }

            .print-button {
                display: none; /* Oculta o botão de impressão na impressão */
            }

            .main-content {
                margin: 0;
                padding: 0;
                page-break-after: auto;
            }

            form {
                display: none; /* Oculta o formulário de busca na impressão */
            }

            @page {
                size: A4 portrait;
                margin: 10mm;
            }
        }
    </style>
    <script>
        function imprimirPagina() {
            var conteudo = document.querySelector('.main-content').innerHTML;
            var estilos = document.querySelector('style').innerHTML;
            var janelaImpressao = window.open('', '', 'width=800, height=600');
            janelaImpressao.document.write('<html><head><title>Imprimir</title>');
            janelaImpressao.document.write('<link rel="stylesheet" href="css/style.css">');
            janelaImpressao.document.write('<style>' + estilos + '</style>');
            janelaImpressao.document.write('</head><body>');
            janelaImpressao.document.write(conteudo);
            janelaImpressao.document.write('</body></html>');
            janelaImpressao.document.close();
            janelaImpressao.print();
        }
    </script>
</head>
<body>
    <?php include 'includes/head.php'; ?>
    <div class="container">
        <?php include 'includes/menu_lateral.php'; ?>
        <div class="main-content">
            <div class="header-saida">
                <img src="img/brasao.png" alt="Logo Left">
                <div class="header-text">
                    <h3>PREFEITURA MUNICIPAL DE COLARES</h3>
                    <h3>SECRETARIA MUNICIPAL DE SAÚDE</h3>
                    <h3>CENTRAL DE ABASTECIMENTO AMBULATORIAL</h3>
                </div>
                <img src="img/prefeitura.png" alt="Logo Right">
            </div>
            <center>
                <h2 style="text-transform: uppercase;">Estoque de medicamentos do(a) <?php echo htmlspecialchars($usuario_nome); ?></h2>
            </center>

            <form method="get" action="user_estoque.php">
                <label for="pesquisa">Buscar Medicamento:</label>
                <input type="text" id="pesquisa" name="pesquisa" value="<?php echo htmlspecialchars($pesquisaNome); ?>">
                <button type="submit">Buscar</button>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Medicamento</th>
                        <th>Quantidade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($medicamentos)) : ?>
                        <?php foreach ($medicamentos as $medicamento): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($medicamento['medicamento']); ?></td>
                                <td><?php echo htmlspecialchars($medicamento['quantidade']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="2">Nenhum medicamento encontrado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="print-button">
                <button onclick="imprimirPagina()">Imprimir</button>
            </div>
        </div>
    </div>
    <?php include 'includes/foot.php'; ?>
</body>
</html>
