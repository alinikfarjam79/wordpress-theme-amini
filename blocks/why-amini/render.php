<?php
if (! defined('ABSPATH')) { exit; }
if (! function_exists('my_theme_render_why_amini')) { require_once get_theme_file_path('inc/trust-blocks.php'); }
echo my_theme_render_why_amini(isset($attributes) && is_array($attributes) ? $attributes : []); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
