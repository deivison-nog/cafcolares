<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'usuario') {
    header('Location: index.php');
    exit;
}

// Conexão com o banco de dados
include 'db.php'; // Certifique-se de que este arquivo define a variável $pdo

// Obter o nome do usuário logado
$usuario_nome = $_SESSION['usuario'];

// Buscar o id do usuário baseado no nome de usuário
$stmt = $pdo->prepare('SELECT id FROM usuarios WHERE usuario = ?');
$stmt->execute([$usuario_nome]);
$user = $stmt->fetch();

if ($user) {
    $usuario_id = $user['id'];
} else {
    echo "Erro: usuário não encontrado.";
    exit;
}

// Exibir para depuração
var_dump($usuario_id);

// Consulta para obter a última data de solicitação de medicamentos do usuário
try {
    $sql_ultima_data = "
        SELECT MAX(data_solicitacao) AS ultima_data
        FROM solicitacoes
        WHERE usuario_id = :usuario_id
    ";

    $stmt = $pdo->prepare($sql_ultima_data);
    $stmt->bindParam(':usuario_id', $usuario_id);
    $stmt->execute();
    $ultima_data = $stmt->fetchColumn();
} catch (PDOException $e) {
    echo 'Erro na consulta: ' . $e->getMessage();
}

// Consulta para obter todas as solicitações na última data
try {
    $sql_solicitacao = "
        SELECT solicitacoes.*, medicamentos.medicamento
        FROM solicitacoes
        JOIN medicamentos ON solicitacoes.medicamento_id = medicamentos.id
        WHERE solicitacoes.usuario_id = :usuario_id
        AND solicitacoes.data_solicitacao = :ultima_data
        ORDER BY solicitacoes.id
    ";

    $stmt = $pdo->prepare($sql_solicitacao);
    $stmt->bindParam(':usuario_id', $usuario_id);
    $stmt->bindParam(':ultima_data', $ultima_data);
    $stmt->execute();
    $solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo 'Erro na consulta: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitações Confirmadas</title>
    <?php include 'includes/head.php'; ?>
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

    .entregue-recebido {
        display: flex;
        justify-content: space-between;
        margin-top: 20px;
    }

    .entregue,
    .recebido {
        width: 45%;
        text-align: center;
        border-style: solid;
    }

    h2,
    h3 {
        margin-top: 0;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    table,
    th,
    td {
        border: 1px solid #ddd;
    }

    th,
    td {
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
    <?php include 'includes/menu_lateral.php'; ?>

    <div class="container">
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
                <h2 style="text-transform: uppercase;">Solicitação de Medicamentos do(a) <?php echo htmlspecialchars($usuario_nome); ?></h2>
            </center>

            <?php if ($solicitacoes): ?>
            <table>
                <thead>
                    <tr>
                        <th>Medicamento/Produto</th>
                        <th>Quantidade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($solicitacoes as $solicitacao): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($solicitacao['medicamento']); ?></td>
                        <td><?php echo htmlspecialchars($solicitacao['quantidade']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p>Nenhuma solicitação encontrada.</p>
            <?php endif; ?>

            <div class="entregue-recebido">
                <div class="entregue">
                    <p>SOLICITADO POR:</p>
                    <p>________________________________</p>
                    <p>Em <?php echo date('d/m/y \à\s H:i', strtotime($solicitacao['data_solicitacao'])); ?></p>
                </div>

                <div class="recebido">
                    <p>RECEBIDO POR:</p>
                    <p>________________________________</p>
                      <p>____/____/______, às ____:____h</p>
                </div>
            </div>

            <div class="print-button">
                <button onclick="imprimirPagina()">Imprimir</button>
            </div>
        </div>
    </div>
</body>
</html>
