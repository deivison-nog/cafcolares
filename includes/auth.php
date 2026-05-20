<?php
/**
 * Authentication helpers – include this at the top of every protected page.
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Require the user to be logged in and optionally enforce a specific access level.
 *
 * @param string|null $nivel  'admin', 'usuario', or null (any authenticated user)
 */
function requireAuth(?string $nivel = null): void
{
    if (!isset($_SESSION['usuario'])) {
        header('Location: index.php');
        exit;
    }
    if ($nivel !== null && ($_SESSION['nivel_acesso'] ?? '') !== $nivel) {
        header('Location: dashboard.php');
        exit;
    }
}

/**
 * Return the Bootstrap head boilerplate (DOCTYPE → <body> opening tag).
 * Used by pages that include this helper and then include head.php / menu_lateral.php.
 */
function bsHead(string $title): void
{
    echo '<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>' . htmlspecialchars($title) . ' – CAF</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>';
}

/**
 * Open the wrapper + sidebar + main-content divs.
 */
function bsBodyOpen(): void
{
    echo '<div id="wrapper" class="d-flex">';
    include __DIR__ . '/menu_lateral.php';
    echo '<div class="main-content flex-grow-1">';
}

/**
 * Close wrapper divs and output footer.
 */
function bsBodyClose(): void
{
    echo '</div></div>';  // close .main-content and #wrapper
    include __DIR__ . '/foot.php';
    echo '</body></html>';
}
