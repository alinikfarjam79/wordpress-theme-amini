<?php

if (! defined('ABSPATH')) {
    exit;
}

$my_theme_includes = [
    'inc/theme-settings.php',
    'inc/header-blocks.php',
    'inc/footer-blocks.php',
    'inc/footer-newsletter.php',
    'inc/hero-slider-block.php',
    'inc/category-products-block.php',
    'inc/brand-slider-block.php',
    'inc/trust-blocks.php',
    'inc/special-offers-block.php',
    'inc/bulk-order-block.php',
    'inc/latest-articles-block.php',
    'inc/assets.php',
    'inc/woocommerce.php',
    'inc/single-product.php',
    'inc/single-article.php',
    'inc/shortcodes.php',
    'inc/search-results.php',
    'inc/blog-list.php',
    'inc/mini-cart.php',
    'inc/about.php',
    'inc/contact.php',
    'inc/product-stock-import.php',
    'inc/404.php',
];

foreach ($my_theme_includes as $my_theme_file) {
    require_once get_template_directory() . '/' . $my_theme_file;
}
