<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<header class="header">
    <div class="header-inner">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-outline-light header-toggle d-lg-none" id="sidebarToggle" type="button" aria-label="Abrir menu">
                <i class="bi bi-list fs-4"></i>
            </button>
            <div>
                <span class="header-eyebrow">Prefeitura de Colares</span>
                <h1 class="header-title">Central de Abastecimento Farmacêutico</h1>
            </div>
        </div>

        <?php if (isset($_SESSION['usuario'])): ?>
            <div class="header-session d-none d-md-flex">
                <span class="header-session-name">
                    <i class="bi bi-person-circle"></i>
                    <?php echo htmlspecialchars($_SESSION['usuario']); ?>
                </span>
                <span class="header-session-badge">
                    <?php echo htmlspecialchars(ucfirst($_SESSION['nivel_acesso'] ?? '')); ?>
                </span>
            </div>
        <?php endif; ?>
    </div>
</header>
