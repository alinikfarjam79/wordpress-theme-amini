<?php

if (! defined('ABSPATH')) {
    exit;
}

function my_theme_enqueue_styles() {

    wp_enqueue_style(
        'theme-style',
        get_stylesheet_uri(),
        array(),
        filemtime(get_stylesheet_directory() . '/style.css')
    );

    $theme_css_files = [
        'theme-header' => 'assets/css/header.css',
        'theme-home'   => 'assets/css/home.css',
        'theme-footer' => 'assets/css/footer.css',
    ];

    foreach ($theme_css_files as $handle => $path) {
        wp_enqueue_style(
            $handle,
            get_stylesheet_directory_uri() . '/' . $path,
            ['theme-style'],
            filemtime(get_stylesheet_directory() . '/' . $path)
        );
    }

}
add_action('wp_enqueue_scripts', 'my_theme_enqueue_styles');

/**
 * Keep the desktop header navigation on one row on the front end.
 *
 * Core Navigation styles may be printed after the theme stylesheet. Printing
 * this small layout rule late in wp_head keeps the front end consistent with
 * the Site Editor without changing the header height.
 */
function my_theme_print_header_navigation_layout_fix() {
    if (is_admin()) {
        return;
    }
    ?>
    <style id="my-theme-header-navigation-layout-fix">
        @media (min-width: 861px) {
            body .wp-site-blocks .site-header .site-header__nav,
            body .wp-site-blocks .site-header .site-header__nav .wp-block-navigation,
            body .wp-site-blocks .site-header .site-header__nav .wp-block-navigation__responsive-container,
            body .wp-site-blocks .site-header .site-header__nav .wp-block-navigation__responsive-container-content {
                min-width: 0 !important;
                width: 100% !important;
            }

            body .wp-site-blocks .site-header .site-header__nav .wp-block-navigation__responsive-container-content {
                align-items: center !important;
                display: flex !important;
                flex-direction: row !important;
                flex-wrap: nowrap !important;
                justify-content: flex-start !important;
                overflow-x: auto !important;
                overflow-y: hidden !important;
            }

            body .wp-site-blocks .site-header .site-header__nav .wp-block-navigation__container,
            body .wp-site-blocks .site-header .site-header__nav .wp-block-page-list {
                align-items: center !important;
                display: flex !important;
                flex-direction: row !important;
                flex-wrap: nowrap !important;
                flex: 0 0 auto !important;
                max-width: none !important;
                min-width: 0 !important;
                overflow: visible !important;
                white-space: nowrap !important;
                width: auto !important;
            }

            body .wp-site-blocks .site-header .site-header__nav .wp-block-navigation__container::-webkit-scrollbar,
            body .wp-site-blocks .site-header .site-header__nav .wp-block-page-list::-webkit-scrollbar {
                display: none;
            }

            body .wp-site-blocks .site-header .site-header__nav .wp-block-navigation-item,
            body .wp-site-blocks .site-header .site-header__nav .wp-block-pages-list__item,
            body .wp-site-blocks .site-header .site-header__nav .wp-block-my-theme-navigation-divider {
                flex: 0 0 auto !important;
            }

            body .wp-site-blocks .site-header .site-header__nav:has(.has-custom-mega-menu),
            body .wp-site-blocks .site-header .site-header__nav:has(.has-custom-mega-menu) .wp-block-navigation,
            body .wp-site-blocks .site-header .site-header__nav:has(.has-custom-mega-menu) .wp-block-navigation__responsive-container,
            body .wp-site-blocks .site-header .site-header__nav:has(.has-custom-mega-menu) .wp-block-navigation__responsive-container-content {
                overflow: visible !important;
            }
        }
    </style>
    <?php
}
add_action('wp_head', 'my_theme_print_header_navigation_layout_fix', 100);

function my_theme_enqueue_404_styles() {
    if (! is_404()) {
        return;
    }

    $stylesheet_path = get_stylesheet_directory() . '/assets/css/404.css';

    if (! file_exists($stylesheet_path)) {
        return;
    }

    wp_enqueue_style(
        'my-theme-404',
        get_stylesheet_directory_uri() . '/assets/css/404.css',
        ['theme-style', 'theme-header', 'theme-footer'],
        filemtime($stylesheet_path)
    );
}
add_action('wp_enqueue_scripts', 'my_theme_enqueue_404_styles', 25);

/**
 * Load the Contact page stylesheet for contact slugs and the selectable FSE
 * template. The explicit template check also supports localized page slugs.
 */
function my_theme_enqueue_contact_styles() {
    if (! is_page()) {
        return;
    }

    $queried_object = get_queried_object();
    $page_slug      = $queried_object instanceof WP_Post ? $queried_object->post_name : '';
    $template_slug  = $queried_object instanceof WP_Post ? (string) get_page_template_slug($queried_object) : '';
    $contact_slugs  = ['contact', 'contact-us', 'تماس-با-ما'];

    if (
        ! in_array($page_slug, $contact_slugs, true)
        && false === strpos($template_slug, 'page-contact')
    ) {
        return;
    }

    $stylesheet_path = get_stylesheet_directory() . '/assets/css/contact.css';

    if (! file_exists($stylesheet_path)) {
        return;
    }

    wp_enqueue_style(
        'my-theme-contact',
        get_stylesheet_directory_uri() . '/assets/css/contact.css',
        ['theme-style', 'theme-header', 'theme-footer'],
        filemtime($stylesheet_path)
    );
}
add_action('wp_enqueue_scripts', 'my_theme_enqueue_contact_styles', 25);

function my_theme_enqueue_swiper_assets() {

    wp_enqueue_style(
        'swiper-css',
        get_template_directory_uri() . '/assets/swiper/swiper-bundle.min.css',
        array(),
        filemtime(get_template_directory() . '/assets/swiper/swiper-bundle.min.css')
    );

    wp_enqueue_script(
        'swiper-js',
        get_template_directory_uri() . '/assets/swiper/swiper-bundle.min.js',
        array(),
        filemtime(get_template_directory() . '/assets/swiper/swiper-bundle.min.js'),
        true
    );

}
add_action('wp_enqueue_scripts', 'my_theme_enqueue_swiper_assets');

function my_theme_enqueue_scripts() {

    wp_enqueue_script(
        'theme-product-carousel',
        get_template_directory_uri() . '/assets/js/product-carousel.js',
        ['swiper-js'],
        filemtime(get_template_directory() . '/assets/js/product-carousel.js'),
        true
    );

    wp_enqueue_script(
        'theme-js',
        get_template_directory_uri() . '/assets/hero-slider.js',
        ['swiper-js', 'theme-product-carousel'],
        filemtime(get_template_directory() . '/assets/hero-slider.js'),
        true
    );

    wp_localize_script('theme-js', 'wc_ajax', [
        'url' => admin_url('admin-ajax.php')
    ]);

    $header_mega_menu_path = get_template_directory() . '/assets/js/navigation-item-mega-menu.js';
    if (file_exists($header_mega_menu_path)) {
        wp_enqueue_script(
            'my-theme-header-mega-menu',
            get_template_directory_uri() . '/assets/js/navigation-item-mega-menu.js',
            [],
            filemtime($header_mega_menu_path),
            true
        );
    }

    $footer_accordion_path = get_template_directory() . '/assets/js/footer-accordion.js';
    if (file_exists($footer_accordion_path)) {
        wp_enqueue_script(
            'my-theme-footer-accordion',
            get_template_directory_uri() . '/assets/js/footer-accordion.js',
            [],
            filemtime($footer_accordion_path),
            true
        );
    }

    $footer_newsletter_path = get_template_directory() . '/assets/js/footer-newsletter.js';
    if (file_exists($footer_newsletter_path)) {
        wp_enqueue_script(
            'my-theme-footer-newsletter',
            get_template_directory_uri() . '/assets/js/footer-newsletter.js',
            [],
            filemtime($footer_newsletter_path),
            true
        );

        wp_localize_script('my-theme-footer-newsletter', 'myThemeNewsletter', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('my_theme_newsletter_submit'),
        ]);
    }

}
add_action('wp_enqueue_scripts', 'my_theme_enqueue_scripts');

/**
 * Load the shared WooCommerce product-carousel initializer in the Site Editor
 * canvas. The script is idempotent, so every carousel initializes independently.
 */
function my_theme_enqueue_editor_product_carousel_script() {
    return;
}
add_action('enqueue_block_assets', 'my_theme_enqueue_editor_product_carousel_script');

function my_theme_enqueue_block_editor_assets() {
    wp_enqueue_style(
        'theme-editor-swiper-css',
        get_template_directory_uri() . '/assets/swiper/swiper-bundle.min.css',
        array(),
        filemtime(get_template_directory() . '/assets/swiper/swiper-bundle.min.css')
    );

    wp_enqueue_style(
        'theme-editor-home-css',
        get_stylesheet_directory_uri() . '/assets/css/home.css',
        array('theme-editor-swiper-css'),
        filemtime(get_stylesheet_directory() . '/assets/css/home.css')
    );

    wp_enqueue_style(
        'theme-editor-header-css',
        get_stylesheet_directory_uri() . '/assets/css/header.css',
        array('theme-editor-home-css'),
        filemtime(get_stylesheet_directory() . '/assets/css/header.css')
    );

    wp_enqueue_style(
        'theme-editor-preview-css',
        get_stylesheet_directory_uri() . '/assets/css/editor.css',
        array('theme-editor-header-css'),
        filemtime(get_stylesheet_directory() . '/assets/css/editor.css')
    );

    wp_enqueue_style(
        'theme-editor-about-css',
        get_stylesheet_directory_uri() . '/assets/css/about.css',
        array('theme-editor-preview-css'),
        filemtime(get_stylesheet_directory() . '/assets/css/about.css')
    );

    wp_enqueue_style(
        'theme-editor-blog-list-css',
        get_stylesheet_directory_uri() . '/assets/css/blog-list.css',
        array('theme-editor-about-css'),
        filemtime(get_stylesheet_directory() . '/assets/css/blog-list.css')
    );

    wp_enqueue_style(
        'theme-editor-contact-css',
        get_stylesheet_directory_uri() . '/assets/css/contact.css',
        array('theme-editor-blog-list-css'),
        filemtime(get_stylesheet_directory() . '/assets/css/contact.css')
    );

    wp_enqueue_style(
        'theme-editor-contact-layout-css',
        get_stylesheet_directory_uri() . '/assets/css/contact-editor.css',
        array('theme-editor-contact-css'),
        filemtime(get_stylesheet_directory() . '/assets/css/contact-editor.css')
    );
}
add_action('enqueue_block_editor_assets', 'my_theme_enqueue_block_editor_assets');

function my_theme_enqueue_editor_canvas_assets() {
    if (! is_admin()) {
        return;
    }

    wp_enqueue_style(
        'theme-editor-canvas-swiper-css',
        get_template_directory_uri() . '/assets/swiper/swiper-bundle.min.css',
        array(),
        filemtime(get_template_directory() . '/assets/swiper/swiper-bundle.min.css')
    );

    wp_enqueue_style(
        'theme-editor-canvas-home-css',
        get_stylesheet_directory_uri() . '/assets/css/home.css',
        array('theme-editor-canvas-swiper-css'),
        filemtime(get_stylesheet_directory() . '/assets/css/home.css')
    );

    wp_enqueue_style(
        'theme-editor-canvas-header-css',
        get_stylesheet_directory_uri() . '/assets/css/header.css',
        array('theme-editor-canvas-home-css'),
        filemtime(get_stylesheet_directory() . '/assets/css/header.css')
    );

    wp_enqueue_style(
        'theme-editor-canvas-preview-css',
        get_stylesheet_directory_uri() . '/assets/css/editor.css',
        array('theme-editor-canvas-header-css'),
        filemtime(get_stylesheet_directory() . '/assets/css/editor.css')
    );

    wp_enqueue_style(
        'theme-editor-canvas-about-css',
        get_stylesheet_directory_uri() . '/assets/css/about.css',
        array('theme-editor-canvas-preview-css'),
        filemtime(get_stylesheet_directory() . '/assets/css/about.css')
    );

    wp_enqueue_style(
        'theme-editor-canvas-blog-list-css',
        get_stylesheet_directory_uri() . '/assets/css/blog-list.css',
        array('theme-editor-canvas-about-css'),
        filemtime(get_stylesheet_directory() . '/assets/css/blog-list.css')
    );

    wp_enqueue_style(
        'theme-editor-canvas-contact-css',
        get_stylesheet_directory_uri() . '/assets/css/contact.css',
        array('theme-editor-canvas-blog-list-css'),
        filemtime(get_stylesheet_directory() . '/assets/css/contact.css')
    );

    wp_enqueue_style(
        'theme-editor-canvas-contact-layout-css',
        get_stylesheet_directory_uri() . '/assets/css/contact-editor.css',
        array('theme-editor-canvas-contact-css'),
        filemtime(get_stylesheet_directory() . '/assets/css/contact-editor.css')
    );
}
add_action('enqueue_block_assets', 'my_theme_enqueue_editor_canvas_assets');
