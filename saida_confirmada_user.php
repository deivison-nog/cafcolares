<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'usuario') {
    header('Location: index.php');
    exit;
}

// Conexão com o banco de dados
include 'db.php';

// Verifica se os parâmetros estão presentes
if (!isset($_GET['data_distribuicao']) || !isset($_GET['hora_distribuicao']) || !isset($_GET['paciente'])) {
    echo "Dados de distribuição não encontrados ou inválidos.";
    exit;
}

$dataDistribuicao = $_GET['data_distribuicao'];
$horaDistribuicao = $_GET['hora_distribuicao'];
$paciente = $_GET['paciente'];

// Consulta para buscar as distribuições pelo paciente, data e hora
$stmt = $pdo->prepare('
    SELECT dp.id, dp.data_distribuicao, dp.hora_distribuicao, p.nome as paciente_nome, m.medicamento, m.apresentacao, m.marca, m.lote, m.validade, dp.quantidade, u.usuario as estabelecimento
    FROM distribuicao_pacientes dp
    JOIN medicamentos m ON dp.medicamento_id = m.id
    JOIN pacientes p ON dp.paciente_id = p.id
    JOIN usuarios u ON dp.estabelecimento_id = u.id
    WHERE dp.data_distribuicao = ? AND dp.hora_distribuicao = ? AND p.nome = ?
');
$stmt->execute([$dataDistribuicao, $horaDistribuicao, $paciente]);
$saida = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Verificar se as distribuições foram encontradas
if (count($saida) == 0) {
    echo "Nenhuma distribuição encontrada para os dados fornecidos.";
    exit;
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
    <title>Saída de Medicamentos Confirmada - CAF</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background-color: #fff;
            font-family: Arial, sans-serif;
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
        .header-text h1, .header-text h2, .header-text h3 {
            margin: 0;
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
            <!-- Conteúdo para visualização e impressão -->
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
                <h2>SAÍDA DE MEDICAMENTOS</h2>
            </center>
            <div class="print-button">
                <button onclick="imprimirConteudo()">Imprimir</button>
            </div>
            <div class="ultima-saida-info">
                <h4>Paciente: <?php echo htmlspecialchars($saida[0]['paciente_nome'], ENT_QUOTES, 'UTF-8'); ?> <br>
                <?php echo formatarData($saida[0]['data_distribuicao']); ?>
                - <?php echo htmlspecialchars($saida[0]['hora_distribuicao'], ENT_QUOTES, 'UTF-8'); ?>
                - <?php echo htmlspecialchars($saida[0]['estabelecimento'], ENT_QUOTES, 'UTF-8'); ?><br>
            </div>
            <h2>Detalhes dos Medicamentos</h2>
            <table>
                <thead>
                    <tr>
                        <th>Medicamento</th>
                        <th>Apresentação</th>
                        <th>Marca</th>
                        <th>Lote</th>
                        <th>Validade</th>
                        <th>Quantidade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($saida) > 0): ?>
                        <?php foreach ($saida as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['medicamento'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($item['apresentacao'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($item['marca'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($item['lote'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($item['validade'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($item['quantidade'], ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">Nenhum detalhe encontrado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="entregue-recebido">
                <div class="entregue">
                    <p>ENTREGUE POR:</p>
                    <p>________________________________</p>
                    <p><?php echo formatarData($saida[0]['data_distribuicao']); ?>, às <?php echo htmlspecialchars($saida[0]['hora_distribuicao'], ENT_QUOTES, 'UTF-8'); ?></p>
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
