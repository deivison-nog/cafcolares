<?php
/**
 * Renders a Bootstrap 5 pagination nav.
 *
 * Format: « ‹ 1 2 3 … › »
 *   «  → first page
 *   ‹  → previous page
 *   1…N → numbered page links (all pages)
 *   ›  → next page
 *   »  → last page
 *
 * @param int    $paginaAtual   Current page (1-based).
 * @param int    $totalPaginas  Total number of pages.
 * @param string $extraParams   Additional query string parameters, already URL-encoded,
 *                              e.g. 'pesquisa=foo&estabelecimento=bar'.
 *                              Do NOT include a leading '&' or '?'.
 */
function renderPaginacao(int $paginaAtual, int $totalPaginas, string $extraParams = ''): string
{
    if ($totalPaginas <= 1) {
        return '';
    }

    $qs = function (int $page) use ($extraParams): string {
        $q = '?pagina=' . $page;
        if ($extraParams !== '') {
            $q .= '&' . $extraParams;
        }
        return htmlspecialchars($q, ENT_QUOTES, 'UTF-8');
    };

    $isFirst = $paginaAtual <= 1;
    $isLast  = $paginaAtual >= $totalPaginas;

    $html  = '<nav class="mt-3" aria-label="Paginação">';
    $html .= '<ul class="pagination pagination-sm justify-content-center flex-wrap">';

    // « First
    $html .= '<li class="page-item' . ($isFirst ? ' disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . $qs(1) . '" title="Primeira página">&laquo;&laquo;</a>';
    $html .= '</li>';

    // ‹ Previous
    $html .= '<li class="page-item' . ($isFirst ? ' disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . $qs($paginaAtual - 1) . '" title="Página anterior">&laquo;</a>';
    $html .= '</li>';

    // Numbered pages
    for ($i = 1; $i <= $totalPaginas; $i++) {
        $active = ($i === $paginaAtual) ? ' active' : '';
        $html .= '<li class="page-item' . $active . '">';
        $html .= '<a class="page-link" href="' . $qs($i) . '">' . $i . '</a>';
        $html .= '</li>';
    }

    // › Next
    $html .= '<li class="page-item' . ($isLast ? ' disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . $qs($paginaAtual + 1) . '" title="Próxima página">&raquo;</a>';
    $html .= '</li>';

    // » Last
    $html .= '<li class="page-item' . ($isLast ? ' disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . $qs($totalPaginas) . '" title="Última página">&raquo;&raquo;</a>';
    $html .= '</li>';

    $html .= '</ul></nav>';

    return $html;
}
