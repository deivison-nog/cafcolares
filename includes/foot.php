<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<footer class="footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <strong>CAF Colares</strong>
            <span>Gestão farmacêutica com visual mais moderno e responsivo.</span>
        </div>
        <div class="footer-contact">
            <span><i class="bi bi-envelope"></i> cafcolares@outlook.com</span>
            <span><i class="bi bi-telephone"></i> (91) 98593-1710</span>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const body = document.body;
    const sidebar = document.getElementById('appSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggle = document.getElementById('sidebarToggle');

    if (!sidebar && !overlay && !toggle) {
        return;
    }

    if (!sidebar || !overlay || !toggle) {
        console.warn('Estrutura da sidebar incompleta; toggle mobile desativado nesta página.');
        return;
    }

    const closeSidebar = () => body.classList.remove('sidebar-open');
    const toggleSidebar = () => body.classList.toggle('sidebar-open');

    toggle.addEventListener('click', toggleSidebar);
    overlay.addEventListener('click', closeSidebar);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeSidebar();
        }
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth >= 992) {
            closeSidebar();
        }
    });
});
</script>
