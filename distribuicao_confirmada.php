<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Conexão com o banco de dados
include 'db.php';

// Buscar a data e hora da última distribuição
$stmt = $pdo->prepare('
    SELECT data_distribuicao, hora_distribuicao
    FROM distribuicao
    ORDER BY data_distribuicao DESC, hora_distribuicao DESC
    LIMIT 1
');
$stmt->execute();
$ultima_distribuicao = $stmt->fetch();

$ultima_data = $_GET['data'] ?? $ultima_distribuicao['data_distribuicao'] ?? null;
$ultima_hora = $_GET['hora'] ?? $ultima_distribuicao['hora_distribuicao'] ?? null;

$estabelecimento = ''; // Inicializar a variável

if ($ultima_data && $ultima_hora) {
    $stmt = $pdo->prepare('
        SELECT d.*, m.medicamento, m.apresentacao, m.categoria, m.marca, m.lote, m.validade, u.usuario as estabelecimento
        FROM distribuicao d
        JOIN medicamentos m ON d.medicamento_id = m.id
        JOIN usuarios u ON d.estabelecimento_id = u.id
        WHERE d.data_distribuicao = :ultima_data AND d.hora_distribuicao = :ultima_hora
    ');
    $stmt->execute(['ultima_data' => $ultima_data, 'ultima_hora' => $ultima_hora]);
    $distribuicoes = $stmt->fetchAll();

    if (!empty($distribuicoes)) {
        $estabelecimento = htmlspecialchars($distribuicoes[0]['estabelecimento']);
    }
} else {
    $distribuicoes = [];
}

// Função para formatar a data
function formatarData($data) {
    $date = new DateTime($data);
    return $date->format('d/m/Y');
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Distribuição Confirmada - CAF</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background-color: #fff;
        }
        .header-dist {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .header-dist img {
            height: 60px;
        }
        .header-text {
            text-align: center;
            flex-grow: 1;
        }
        .header-text h1, .header-text h2, .header-text h3 {
            margin: 0;
        }
        .entregue-recebido {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .entregue, .recebido {
            width: 45%;
            text-align: center;
            border-style: solid;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .print-button {
            margin: 20px 0;
            text-align: right;
        }
        .print-button button {
            padding: 10px 20px;
            font-size: 16px;
        }
        @media print {
            body {
                -webkit-print-color-adjust: exact;
            }
            .print-button {
                display: none;
            }
            .main-content {
                margin: 0;
                padding: 0;
                page-break-after: auto;
            }
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
        }
    </style>
    <script>
        function imprimirConteudo() {
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
            <div class="header-dist">
                <img src="img/brasao.png" alt="Logo Left">
                <div class="header-text">
                    <h3>PREFEITURA MUNICIPAL DE COLARES</h3>
                    <h3>SECRETARIA MUNICIPAL DE SAÚDE</h3>
                    <h3>CENTRAL DE ABASTECIMENTO AMBULATORIAL</h3>
                </div>
                <img src="img/prefeitura.png" alt="Logo Right">
            </div>
            <center>
                <h2>DISTRIBUIÇÃO PARA ESTABELECIMENTOS</h2>
            </center>
            <div class="print-button">
                <button onclick="imprimirConteudo()">Imprimir</button>
            </div>
            <div class="ultima-distribuicao-info">
                <p><strong>Distribuição Registrada:</strong></p>
                <p>Data: <?php echo formatarData($ultima_data); ?> - Hora: <?php echo htmlspecialchars($ultima_hora); ?> - Estabelecimento: <?php echo $estabelecimento; ?></p>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Medicamento/Produto</th>
                        <th>Apresentação</th>
                        <th>Categoria</th> <!-- Nova coluna -->
                        <th>Marca</th>
                        <th>Lote</th>
                        <th>Validade</th>
                        <th>Quantidade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($distribuicoes): ?>
                        <?php foreach ($distribuicoes as $distribuicao): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($distribuicao['medicamento']); ?></td>
                                <td><?php echo htmlspecialchars($distribuicao['apresentacao']); ?></td>
                                <td><?php echo htmlspecialchars($distribuicao['categoria']); ?></td> <!-- Nova coluna -->
                                <td><?php echo htmlspecialchars($distribuicao['marca']); ?></td>
                                <td><?php echo htmlspecialchars($distribuicao['lote']); ?></td>
                                <td><?php echo formatarData($distribuicao['validade']); ?></td>
                                <td><?php echo htmlspecialchars($distribuicao['quantidade']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">Nenhuma distribuição encontrada.</td> <!-- Atualizar colspan -->
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="entregue-recebido">
                <div class="entregue">
                    <p>ENTREGUE POR:</p>
                    <p>________________________________</p>
                    <p><?php echo formatarData($ultima_data); ?>, às <?php echo htmlspecialchars($ultima_hora); ?></p>
                </div>

                <div class="recebido">
                    <p>RECEBIDO POR:</p>
                    <p>________________________________</p>
                    <p>____/____/______, às ____:____h</p>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/foot.php'; ?>
</body>
</html>
