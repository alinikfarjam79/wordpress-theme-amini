<?php
/**
 * Render the product breadcrumb section block.
 *
 * @package MyTheme
 */

if (! defined('ABSPATH')) {
    exit;
}

echo my_theme_render_product_breadcrumb_block(
    is_array($attributes ?? null) ? $attributes : [],
    $block ?? null
);
