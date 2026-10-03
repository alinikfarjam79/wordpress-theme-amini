<?php
if (! defined('ABSPATH')) { exit; }
if (! function_exists('my_theme_render_header_logo')) {
    require_once get_theme_file_path('inc/header-blocks.php');
}
echo my_theme_render_header_logo(isset($attributes) && is_array($attributes) ? $attributes : []); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
