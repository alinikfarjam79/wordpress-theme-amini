<?php
/**
 * Render the product details section block.
 *
 * @package MyTheme
 */

if (! defined('ABSPATH')) {
    exit;
}

echo my_theme_render_single_product_section_block('details', $block ?? null);
