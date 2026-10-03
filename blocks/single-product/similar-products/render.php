<?php
/**
 * Render the similar products section block.
 *
 * @package MyTheme
 */

if (! defined('ABSPATH')) {
    exit;
}

echo my_theme_render_single_product_section_block('similar-products', $block ?? null);
