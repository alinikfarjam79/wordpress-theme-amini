<?php
if (! defined('ABSPATH')) { exit; }
if (! function_exists('my_theme_render_category_products')) { require_once get_theme_file_path('inc/category-products-block.php'); }
echo my_theme_render_category_products(isset($attributes) && is_array($attributes) ? $attributes : []); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
