<?php
/**
 * Product category content block render callback.
 *
 * @package MyTheme
 */

if (! defined('ABSPATH')) {
    exit;
}

echo my_theme_render_product_category_content_block(
    $attributes ?? [],
    $content ?? '',
    $block ?? null
); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
