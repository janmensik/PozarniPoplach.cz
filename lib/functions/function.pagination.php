<?php

/**
 * Generates pagination links for navigating through a list of items.
 *
 * @param int $on_page Number of items to display per page. Default is 20.
 * @param int $total Total number of items.
 * @param int $current_page The current page number. Default is 1.
 * @param int $max_links_to_show Maximum number of pagination links to display. Default is 7.
 * @return array|false Returns an array containing the pagination structure or false if pagination is not needed.
 */
function pagination($on_page = 20, $total = 0, $current_page = 1, $max_links_to_show = 7) {
    if ($total <= $on_page) {
        return false;
    }

    $total_pages = (int) ceil($total / $on_page);
    if ($current_page < 1) {
        $current_page = 1;
    }
    if ($current_page > $total_pages) {
        $current_page = $total_pages;
    }

    $output = [
        'previous' => ($current_page > 1) ? $current_page - 1 : null,
        'next' => ($current_page < $total_pages) ? $current_page + 1 : null,
        'active_page' => $current_page,
        'first' => ($current_page > 1) ? 1 : null,
        'last' => ($current_page < $total_pages) ? $total_pages : null,
        'total_pages' => $total_pages,
        'total_items' => $total,
        'on_page' => $on_page,
        'pages' => []
    ];

    if ($total_pages <= $max_links_to_show) {
        $output['pages'] = range(1, $total_pages);
    } else {
        $pages = [];
        $pages[] = 1; // Always show first page

        $num_adjacent = $max_links_to_show - 2; // slots left after first and last
        $start = max(2, $current_page - floor($num_adjacent / 2));
        $end = min($total_pages - 1, $start + $num_adjacent - 1);

        // Adjust start if we are at the end
        $start = max(2, $end - $num_adjacent + 1);

        if ($start > 2) {
            $pages[] = null; // '...'
        }

        for ($i = $start; $i <= $end; $i++) {
            $pages[] = $i;
        }

        if ($end < $total_pages - 1) {
            $pages[] = null; // '...'
        }

        $pages[] = $total_pages; // Always show last page

        $output['pages'] = $pages;
    }

    return $output;
}
