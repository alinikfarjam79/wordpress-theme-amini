<?php
/**
 * Front-end renderer for the Header pages menu block.
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('my_theme_render_header_pages_menu')) {
    require_once get_theme_file_path('inc/header-blocks.php');
}

echo my_theme_render_header_pages_menu(
    isset($attributes) && is_array($attributes) ? $attributes : []
); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
