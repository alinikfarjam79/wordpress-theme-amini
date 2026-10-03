<?php
/**
 * Render the product benefits section block.
 *
 * @package MyTheme
 */

if (! defined('ABSPATH')) {
    exit;
}

echo my_theme_render_single_product_section_block('benefits', $block ?? null, isset($content) ? (string) $content : '');
