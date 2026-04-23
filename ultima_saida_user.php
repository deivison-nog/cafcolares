<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'usuario') {
    header('Location: index.php');
    exit;
}

// Conexão com o banco de dados
include 'db.php';

// Buscar a data e hora da última saída
$stmt = $pdo->prepare('
    SELECT data_distribuicao, hora_distribuicao
    FROM distribuicao_pacientes
    WHERE estabelecimento_id = (SELECT id FROM usuarios WHERE usuario = ?)
    ORDER BY data_distribuicao DESC, hora_distribuicao DESC
    LIMIT 1
');
$stmt->execute([$_SESSION['usuario']]);
$ultima_saida = $stmt->fetch();

// Verificar se há saídas registradas
if ($ultima_saida) {
    // Buscar todas as saídas que possuem a mesma data e hora da última saída
    $stmt = $pdo->prepare('
        SELECT dp.*, m.medicamento, m.apresentacao, p.nome as paciente
        FROM distribuicao_pacientes dp
        JOIN medicamentos m ON dp.medicamento_id = m.id
        JOIN pacientes p ON dp.paciente_id = p.id
        WHERE dp.data_distribuicao = ? AND dp.hora_distribuicao = ?
        AND dp.estabelecimento_id = (SELECT id FROM usuarios WHERE usuario = ?)
    ');
    $stmt->execute([
        $ultima_saida['data_distribuicao'],
        $ultima_saida['hora_distribuicao'],
        $_SESSION['usuario']
    ]);
    $saidas = $stmt->fetchAll();
} else {
    $saidas = [];
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
    <title>Última Saída de Medicamentos - CAF</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background-color: #fff;
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
                <h2>Saída de Medicamentos</h2>
            </center>
            <div class="print-button">
                <button onclick="imprimirConteudo()">Imprimir</button>
            </div>
            <div class="ultima-saida-info">
                <?php if ($ultima_saida): ?>
                    <?php
                    // Encontrar o nome do paciente na última saída
                    $stmt = $pdo->prepare('
                        SELECT p.nome as paciente
                        FROM distribuicao_pacientes dp
                        JOIN pacientes p ON dp.paciente_id = p.id
                        WHERE dp.data_distribuicao = ? AND dp.hora_distribuicao = ?
                        LIMIT 1
                    ');
                    $stmt->execute([
                        $ultima_saida['data_distribuicao'],
                        $ultima_saida['hora_distribuicao']
                    ]);
                    $paciente = $stmt->fetchColumn();
                    ?>

                    <h4>Data: <?php echo formatarData($ultima_saida['data_distribuicao']); ?> - Hora: <?php echo htmlspecialchars($ultima_saida['hora_distribuicao']); ?> <br>
                      Paciente: <?php echo htmlspecialchars($paciente); ?></h4>
                <?php else: ?>
                    <p>Nenhuma saída registrada ainda.</p>
                <?php endif; ?>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Medicamento/Produto</th>
                        <th>Quantidade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($saidas): ?>
                        <?php foreach ($saidas as $saida): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($saida['medicamento']); ?></td>
                                <td><?php echo htmlspecialchars($saida['quantidade']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2">Nenhuma saída encontrada.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="entregue-recebido">
                <div class="entregue">
                    <p>ENTREGUE POR:</p>
                    <p>________________________________</p>
                    <p><?php echo formatarData($ultima_saida['data_distribuicao']); ?>, às <?php echo htmlspecialchars($ultima_saida['hora_distribuicao']); ?></p>
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
