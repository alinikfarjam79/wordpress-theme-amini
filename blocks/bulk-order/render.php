<?php
if (! defined('ABSPATH')) { exit; }
if (! function_exists('my_theme_render_bulk_order')) { require_once get_theme_file_path('inc/bulk-order-block.php'); }
echo my_theme_render_bulk_order(
    isset($attributes) && is_array($attributes) ? $attributes : [],
    isset($content) ? (string) $content : ''
); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
