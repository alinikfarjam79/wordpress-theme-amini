<?php
if (! defined('ABSPATH')) { exit; }
if (! function_exists('my_theme_render_hero_slider')) { require_once get_theme_file_path('inc/hero-slider-block.php'); }
echo my_theme_render_hero_slider(isset($attributes) && is_array($attributes) ? $attributes : []); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
