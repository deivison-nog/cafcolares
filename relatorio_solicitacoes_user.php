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

// Carregar os medicamentos do usuário 'caf'
$stmt = $pdo->prepare('SELECT id, medicamento FROM medicamentos WHERE estabelecimento = ?');
$stmt->execute(['caf']);
$medicamentos = $stmt->fetchAll();

// Buscar as últimas solicitações de medicamentos do usuário
$stmt = $pdo->prepare('SELECT solicitacoes.*, medicamentos.medicamento
                       FROM solicitacoes
                       JOIN medicamentos ON solicitacoes.medicamento_id = medicamentos.id
                       WHERE solicitacoes.usuario_id = ?
                       ORDER BY solicitacoes.data_solicitacao DESC
                       LIMIT 5');
$stmt->execute([$usuario_id]);
$ultimos_pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Verifica se há resultados e inicializa a variável como array vazio se não houver
if ($ultimos_pedidos === false) {
    $ultimos_pedidos = []; // Garante que $ultimos_pedidos seja um array vazio se nenhum resultado for encontrado
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Solicitações - Usuário</title>
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
            <h2>Relatório de Solicitações de Medicamentos</h2>

            <table>
                <thead>
                    <tr>
                        <th>Medicamento</th>
                        <th>Quantidade</th>
                        <th>Data da Solicitação</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($ultimos_pedidos)) : ?>
                        <?php
                        $grouped_pedidos = [];
                        foreach ($ultimos_pedidos as $pedido) {
                            $data = date('d/m/Y, \à\s H:i', strtotime($pedido['data_solicitacao']));
                            if (!isset($grouped_pedidos[$data])) {
                                $grouped_pedidos[$data] = [
                                    'medicamentos' => [],
                                    'quantidades' => [],
                                    'data' => $data
                                ];
                            }
                            $grouped_pedidos[$data]['medicamentos'][] = $pedido['medicamento'];
                            $grouped_pedidos[$data]['quantidades'][] = $pedido['quantidade'];
                        }
                        ?>

                        <?php foreach ($grouped_pedidos as $group) : ?>
                            <tr>
                                <td><?php echo implode(', ', $group['medicamentos']); ?></td>
                                <td><?php echo implode(', ', $group['quantidades']); ?></td>
                                <td><?php echo $group['data']; ?></td>
                                <td>
                                    <form method="get" action="solicitacoes_confirmadas_user.php">
                                        <input type="hidden" name="medicamentos" value="<?php echo urlencode(implode(', ', $group['medicamentos'])); ?>">
                                        <input type="hidden" name="quantidades" value="<?php echo urlencode(implode(', ', $group['quantidades'])); ?>">
                                        <input type="hidden" name="data" value="<?php echo urlencode($group['data']); ?>">
                                        <button type="submit">Imprimir</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4">Nenhuma solicitação recente encontrada.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php include 'includes/foot.php'; ?>
</body>
</html>
