<?php
/**
 * Render the product category brand filter block.
 *
 * @package MyTheme
 */

if (! defined('ABSPATH')) {
    exit;
}

echo my_theme_render_product_category_brand_filter_block(
    is_array($attributes ?? null) ? $attributes : [],
    $block ?? null
);
