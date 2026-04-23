<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<div class="sidebar">
    <p>Bem-vindo, <?php echo isset($_SESSION['usuario']) ? $_SESSION['usuario'] : ''; ?>.</p>
    <ul>
        <li><a href="dashboard.php">INICIO</a></li>
        <?php if ($_SESSION['nivel_acesso'] == 'admin') { ?>
            <li><a href="distribuicao.php">DISTRIBUIÇÃO PARA ESTRATÉGIAS</a></li>
            <li><a href="estabelecimentos.php">ESTABELECIMENTOS</a></li>
            <li><a href="admin_estoque.php">ESTOQUE</a></li>
            <li><a href="incluir_medicamento.php">INCLUIR MEDICAMENTO</a></li>
            <li><a href="relatorio.php">RELATÓRIO DE DISTRIBUIÇÃO</a></li>
            <li><a href="relatorio_pacientes.php">RELATÓRIO DE PACIENTES</a></li>
            <li><a href="saida_medicamento.php">SAÍDA PARA PACIENTES</a></li>
            <li><a href="solicitacoes_caf.php">SOLICITAÇÕES</a></li>
            <li><a href="admin_validade.php">VENCIMENTOS</a></li>
        <?php } elseif ($_SESSION['nivel_acesso'] == 'usuario') { ?>
            <li><a href="user_estoque.php">ESTOQUE</a> </li>
            <li><a href="relatorio_solicitacoes_user.php">HISTÓRICO DE SOLICITAÇÕES</a></li>
            <li><a href="relatorio_pacientes_user.php">RELATÓRIO DE PACIENTES</a> </li>
            <li><a href="saida_medicamento_user.php">SAÍDA DE MEDICAMENTO</a></li>
            <li><a href="solicitacoes_user.php">SOLICITAÇÕES DE MEDICAMENTOS</a></li>
            <li><a href="user_validade.php">VENCIMENTOS</a></li>
        <?php } ?>
        <li><a href="logout.php">SAIR</a></li>
    </ul>
</div>
