<?php
if (! defined('ABSPATH')) { exit; }
if (! function_exists('my_theme_render_latest_articles_block')) { require_once get_theme_file_path('inc/latest-articles-block.php'); }
echo my_theme_render_latest_articles_block(isset($attributes) && is_array($attributes) ? $attributes : [], $block ?? null); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
