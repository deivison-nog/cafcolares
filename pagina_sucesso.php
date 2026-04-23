<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'usuario') {
    header('Location: index.php');
    exit;
}

// Verificar se há solicitações armazenadas na sessão
$solicitacoes = isset($_SESSION['solicitacoes']) ? $_SESSION['solicitacoes'] : [];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitação Concluída</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/head.php'; ?>
    <div class="container">
        <?php include 'includes/menu_lateral.php'; ?>
        <div class="main-content">
            <h2>Solicitação Finalizada com Sucesso!</h2>
            <p>A seguir estão os medicamentos solicitados:</p>

            <?php if (!empty($solicitacoes)): ?>
                <ul>
                    <?php foreach ($solicitacoes as $solicitacao): ?>
                        <li>
                            <?php echo htmlspecialchars($solicitacao['medicamento']) . ' - Quantidade: ' . htmlspecialchars($solicitacao['quantidade']); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Nenhum medicamento foi solicitado.</p>
            <?php endif; ?>

            <?php
            // Limpar as solicitações após exibi-las
            unset($_SESSION['solicitacoes']);
            ?>
        </div>
    </div>
    <?php include 'includes/foot.php'; ?>
</body>
</html>
