<?php
if (! defined('ABSPATH')) { exit; }
echo my_theme_render_article_meta(is_array($attributes ?? null) ? $attributes : [], $block ?? null); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
