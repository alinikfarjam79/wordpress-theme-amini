<?php
if (! defined('ABSPATH')) { exit; }
echo my_theme_render_single_product_section_block('meta', $block ?? null, isset($content) ? (string) $content : '');
