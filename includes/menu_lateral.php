<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$paginaAtual = basename($_SERVER['PHP_SELF'] ?? '');
$nivelAcesso = $_SESSION['nivel_acesso'] ?? '';
$usuario = $_SESSION['usuario'] ?? 'Usuário';
$usuarioInicial = strtoupper(substr($usuario, 0, 1));

$itensComuns = [
    ['href' => 'dashboard.php', 'label' => 'Início', 'icon' => 'bi-grid-1x2-fill'],
];

$itensAdmin = [
    ['href' => 'distribuicao.php', 'label' => 'Distribuição para Estratégias', 'icon' => 'bi-truck'],
    ['href' => 'estabelecimentos.php', 'label' => 'Estabelecimentos', 'icon' => 'bi-building'],
    ['href' => 'admin_estoque.php', 'label' => 'Estoque', 'icon' => 'bi-box-seam'],
    ['href' => 'incluir_medicamento.php', 'label' => 'Incluir Medicamento', 'icon' => 'bi-plus-circle'],
    ['href' => 'relatorio.php', 'label' => 'Relatório de Distribuição', 'icon' => 'bi-bar-chart-line'],
    ['href' => 'relatorio_pacientes.php', 'label' => 'Relatório de Pacientes', 'icon' => 'bi-people'],
    ['href' => 'saida_medicamento.php', 'label' => 'Saída para Pacientes', 'icon' => 'bi-box-arrow-up-right'],
    ['href' => 'solicitacoes_caf.php', 'label' => 'Solicitações', 'icon' => 'bi-clipboard-check'],
    ['href' => 'admin_validade.php', 'label' => 'Vencimentos', 'icon' => 'bi-calendar2-week'],
];

$itensUsuario = [
    ['href' => 'user_estoque.php', 'label' => 'Estoque', 'icon' => 'bi-box-seam'],
    ['href' => 'relatorio_solicitacoes_user.php', 'label' => 'Histórico de Solicitações', 'icon' => 'bi-clock-history'],
    ['href' => 'relatorio_pacientes_user.php', 'label' => 'Relatório de Pacientes', 'icon' => 'bi-people'],
    ['href' => 'saida_medicamento_user.php', 'label' => 'Saída de Medicamento', 'icon' => 'bi-box-arrow-up-right'],
    ['href' => 'solicitacoes_user.php', 'label' => 'Solicitações de Medicamentos', 'icon' => 'bi-capsule-pill'],
    ['href' => 'user_validade.php', 'label' => 'Vencimentos', 'icon' => 'bi-calendar2-week'],
];

$itensMenu = array_merge($itensComuns, $nivelAcesso === 'admin' ? $itensAdmin : $itensUsuario);
?>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="appSidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon">
            <i class="bi bi-hospital"></i>
        </div>
        <div>
            <div class="sidebar-brand-kicker">Sistema CAF</div>
            <div class="sidebar-brand-title">Colares</div>
        </div>
    </div>

    <div class="sidebar-user-card">
        <div class="sidebar-user-avatar"><?php echo htmlspecialchars($usuarioInicial); ?></div>
        <div>
            <div class="sidebar-user-label">Bem-vindo</div>
            <div class="sidebar-user-name"><?php echo htmlspecialchars($usuario); ?></div>
            <span class="sidebar-user-badge"><?php echo htmlspecialchars(strtoupper($nivelAcesso)); ?></span>
        </div>
    </div>

    <div class="sidebar-section-label">Navegação</div>

    <ul class="sidebar-nav">
        <?php foreach ($itensMenu as $item): ?>
            <?php $ativo = $paginaAtual === $item['href']; ?>
            <li>
                <a href="<?php echo htmlspecialchars($item['href']); ?>" class="<?php echo $ativo ? 'active' : ''; ?>">
                    <i class="bi <?php echo htmlspecialchars($item['icon']); ?>"></i>
                    <span><?php echo htmlspecialchars($item['label']); ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="sidebar-section-label mt-4">Conta</div>
    <a href="logout.php" class="sidebar-logout">
        <i class="bi bi-box-arrow-right"></i>
        <span>Sair do sistema</span>
    </a>
</aside>
