<?php
/**
 * Render the product category filter card block.
 *
 * @package MyTheme
 */

if (! defined('ABSPATH')) {
    exit;
}

echo my_theme_render_product_category_filter_card_block(
    is_array($attributes ?? null) ? $attributes : [],
    isset($content) ? (string) $content : '',
    $block ?? null
);
