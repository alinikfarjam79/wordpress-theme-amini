<?php
if (! defined('ABSPATH')) { exit; }
echo my_theme_render_single_product_section_block('add-to-cart', $block ?? null, '', is_array($attributes ?? null) ? $attributes : []);
