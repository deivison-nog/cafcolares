<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Conexão com o banco de dados
include 'db.php';

// Carregar todas as solicitações de medicamentos, independentemente do estabelecimento ou usuário
$stmt = $pdo->prepare('SELECT solicitacoes.*, medicamentos.medicamento, usuarios.usuario
                       FROM solicitacoes
                       JOIN medicamentos ON solicitacoes.medicamento_id = medicamentos.id
                       JOIN usuarios ON solicitacoes.usuario_id = usuarios.id
                       ORDER BY solicitacoes.data_solicitacao DESC');
$stmt->execute();
$solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Verifica se há resultados e inicializa a variável como array vazio se não houver
if ($solicitacoes === false) {
    $solicitacoes = []; // Garante que $solicitacoes seja um array vazio se nenhum resultado for encontrado
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Solicitações - Admin</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Estiliza a tabela de relatório */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        th {
            background-color: #f4f4f4;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 8px 16px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer;
        }

        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <?php include 'includes/head.php'; ?>
    <div class="container">
        <?php include 'includes/menu_lateral.php'; ?>
        <div class="main-content">
            <h2>Histórico de Solicitações de Medicamentos</h2>

            <table>
                <thead>
                    <tr>
                        <th>Usuário</th>
                        <th>Medicamento</th>
                        <th>Quantidade</th>
                        <th>Data da Solicitação</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($solicitacoes)) : ?>
                        <?php
                        $grouped_solicitacoes = [];
                        foreach ($solicitacoes as $solicitacao) {
                            $data = date('d/m/Y, \à\s H:i', strtotime($solicitacao['data_solicitacao']));
                            if (!isset($grouped_solicitacoes[$data])) {
                                $grouped_solicitacoes[$data] = [
                                    'medicamentos' => [],
                                    'quantidades' => [],
                                    'usuarios' => [],
                                    'data' => $data
                                ];
                            }
                            $grouped_solicitacoes[$data]['medicamentos'][] = $solicitacao['medicamento'];
                            $grouped_solicitacoes[$data]['quantidades'][] = $solicitacao['quantidade'];
                            $grouped_solicitacoes[$data]['usuarios'][] = $solicitacao['usuario'];
                        }
                        ?>

                        <?php foreach ($grouped_solicitacoes as $group) : ?>
                            <tr>
                                <td><?php echo strtoupper(implode(', ', array_unique($group['usuarios']))); ?></td>
                                <td>
                                    <?php
                                    $medicamentosStr = implode(', ', array_unique($group['medicamentos']));
                                    echo strtoupper(mb_substr($medicamentosStr, 0, 40)); // Limita o total a 40 caracteres
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $quantidadesStr = implode(', ', array_unique($group['quantidades']));
                                    echo strtoupper(mb_substr($quantidadesStr, 0, 6)); // Limita o total a 6 caracteres
                                    ?>
                                </td>
                                <td><?php echo strtoupper($group['data']); ?></td>
                                <td>
                                    <form method="get" action="solicitacoes_confirmadas_admin.php">
                                        <input type="hidden" name="medicamentos" value="<?php echo urlencode(mb_substr($medicamentosStr, 0, 40)); ?>">
                                        <input type="hidden" name="quantidades" value="<?php echo urlencode(mb_substr($quantidadesStr, 0, 6)); ?>">
                                        <input type="hidden" name="usuarios" value="<?php echo $_SESSION['usuarios'] = urlencode(implode(', ', array_unique($group['usuarios']))); ?>">
                                        <input type="hidden" name="data" value="<?php echo urlencode($group['data']); ?>">
                                        <button type="submit">Imprimir</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5">NENHUMA SOLICITAÇÃO ENCONTRADA.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>

        </div>
    </div>
    <?php include 'includes/foot.php'; ?>
</body>
</html>
