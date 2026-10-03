<?php
/**
 * Server render callback for the single-product content block.
 *
 * @package MyTheme
 */

if (! defined('ABSPATH')) {
    exit;
}

echo my_theme_render_single_product_content_block(
    is_array($attributes ?? null) ? $attributes : [],
    isset($content) ? (string) $content : '',
    $block ?? null
);
