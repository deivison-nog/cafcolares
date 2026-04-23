<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Conexão com o banco de dados
include 'db.php';

// Verificar se o filtro de estabelecimento foi enviado via URL
$estabelecimentoFiltro = isset($_GET['estabelecimento']) ? $_GET['estabelecimento'] : '';

// Montar a consulta SQL
$sql = "SELECT medicamento, quantidade FROM medicamentos WHERE 1=1";

// Adicionar filtro de estabelecimento se ele foi enviado
if (!empty($estabelecimentoFiltro)) {
    $sql .= " AND estabelecimento = :estabelecimento";
}
$sql .= " ORDER BY medicamento ASC";

// Preparar e executar a consulta
$stmt = $pdo->prepare($sql);

// Bind do parâmetro de estabelecimento, se necessário
if (!empty($estabelecimentoFiltro)) {
    $stmt->bindParam(':estabelecimento', $estabelecimentoFiltro, PDO::PARAM_STR);
}

$stmt->execute();
$medicamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista Completa de Medicamentos</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .header-estoque {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .header-estoque img {
            height: 80px;
        }
        .header-text {
            text-align: center;
            flex-grow: 1;
        }
        .header-text h3 {
            margin: 0;
        }
        .print-button {
            text-align: right;
            margin: 20px 0;
        }
        .print-button button {
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                  background-color: white;
            }
            .print-button {
                display: none;
            }
            .header-estoque {
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            .header-estoque img {
                height: 80px;
            }
            .header-text {
                text-align: center;
                flex-grow: 1;
            }
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
        }
    </style>
    <script>
        function imprimirConteudo() {
            const conteudo = document.querySelector('.main-content').innerHTML;
            const estilos = document.querySelector('style').innerHTML;
            const janelaImpressao = window.open('', '_blank', 'width=800,height=600');
            janelaImpressao.document.write('<html><head><title>Impressão</title>');
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
            <!-- Cabeçalho -->
            <div class="header-estoque">
                <img src="img/brasao.png" alt="Logo Left">
                <div class="header-text">
                    <h3>PREFEITURA MUNICIPAL DE COLARES</h3>
                    <h3>SECRETARIA MUNICIPAL DE SAÚDE</h3>
                    <h3>CENTRAL DE ABASTECIMENTO AMBULATORIAL</h3>
                </div>
                <img src="img/prefeitura.png" alt="Logo Right">
            </div>
            <center>
                <h2>ESTOQUE COMPLETO DE MEDICAMENTOS</h2>
            </center>

            <!-- Botão Imprimir -->
            <div class="print-button">
                <button onclick="imprimirConteudo()">Imprimir</button>
            </div>

            <!-- Tabela de Medicamentos -->
            <table>
                <thead>
                    <tr>
                        <th>Medicamento</th>
                        <th>Quantidade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($medicamentos as $medicamento): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($medicamento['medicamento']); ?></td>
                            <td><?php echo htmlspecialchars($medicamento['quantidade']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Botão Voltar -->
            <div style="margin-top: 20px;">
                <a href="admin_estoque.php" class="btn">Voltar</a>
            </div>
        </div>
    </div>
    <?php include 'includes/foot.php'; ?>
</body>
</html>
