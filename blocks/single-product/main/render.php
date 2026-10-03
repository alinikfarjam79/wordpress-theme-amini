<?php
/**
 * Render the product main section block.
 *
 * @package MyTheme
 */

if (! defined('ABSPATH')) {
    exit;
}

echo my_theme_render_single_product_section_block(
    'main',
    $block ?? null,
    isset($content) ? (string) $content : ''
);
