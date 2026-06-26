<?php

add_action('after_setup_theme', function () {
    add_theme_support('wp-block-styles');
    add_theme_support('editor-styles');
    add_theme_support('title-tag');
});

function my_theme_allow_svg_upload( $mimes ) {
    $mimes['svg'] = 'image/svg+xml';
    $mimes['svgz'] = 'image/svg+xml';
    return $mimes;
}
add_filter( 'upload_mimes', 'my_theme_allow_svg_upload' );

function my_theme_fix_svg_display( $response, $attachment, $meta ) {
    if ( $response['type'] === 'image' && $response['subtype'] === 'svg+xml' && method_exists( $attachment, 'get_source' ) ) {
        $response['sizes'] = array(
            'full' => array(
                'url' => $response['url'],
            )
        );
    }
    return $response;
}
add_filter( 'wp_prepare_attachment_for_js', 'my_theme_fix_svg_display', 10, 3 );

add_filter('woocommerce_price_format', function () {
    return '%1$s&nbsp;%2$s';
});

add_action('after_setup_theme', function () {

    add_theme_support('custom-logo', [
        'flex-height' => true,
        'flex-width'  => true,
    ]);

});



add_action('admin_menu', function () {

    add_menu_page(
        'Theme Settings',
        'Theme Settings',
        'manage_options',
        'theme-settings',
        'theme_settings_page',
        'dashicons-admin-customizer',
        60
    );

});
function theme_logo_shortcode() {

    $logo = get_option('theme_logo');

    if ($logo) {
        return '<img src="' . esc_url($logo) . '" style="height:35px;width:190px;">';
    }

    return '<span>No Logo</span>';
}

add_shortcode('theme_logo', 'theme_logo_shortcode');
add_action('admin_init', function () {

    register_setting('theme_settings_group', 'theme_logo');

});
function theme_settings_page() {
?>
<div class="wrap">
    <h1>Theme Settings</h1>

    <form method="post" action="options.php">
        <?php settings_fields('theme_settings_group'); ?>

        <table class="form-table">

            <tr>
                <th>Site Logo</th>
                <td>
                    <input type="text" name="theme_logo" value="<?php echo esc_attr(get_option('theme_logo')); ?>"
                        style="width:300px;" />

                    <p>Paste image URL or use Media Library link</p>
                </td>
            </tr>

        </table>

        <?php submit_button(); ?>
    </form>
</div>
<?php
}

add_action('wp_enqueue_scripts', function () {

    wp_enqueue_style(
        'theme-style',
        get_stylesheet_uri(),
        array(),
        filemtime(get_stylesheet_directory() . '/style.css')
    );

});

function my_theme_slider_assets() {

    wp_enqueue_style(
        'swiper-css',
        get_template_directory_uri() . '/assets/swiper/swiper-bundle.min.css'
    );

    wp_enqueue_script(
        'swiper-js',
        get_template_directory_uri() . '/assets/swiper/swiper-bundle.min.js',
        array(),
        null,
        true
    );

}
add_action('wp_enqueue_scripts', 'my_theme_slider_assets');




add_action('wp_enqueue_scripts', function () {

    wp_enqueue_script(
        'theme-js',
        get_template_directory_uri() . '/assets/hero-slider.js',
        ['swiper-js'],
        null,
        true
    );

    wp_localize_script('theme-js', 'wc_ajax', [
        'url' => admin_url('admin-ajax.php')
    ]);

});

add_action('wp_ajax_wc_filter_products', 'wc_filter_products');
add_action('wp_ajax_nopriv_wc_filter_products', 'wc_filter_products');

function wc_filter_products() {

    $cat = $_POST['cat'];

    echo wc_products_grid($cat);

    wp_die();
}


function wc_products_grid($cat = 'all') {

    $args = [
        'post_type' => 'product',
        'posts_per_page' => 12,
        'post_status' => 'publish'
    ];

    if ($cat !== 'all') {
        $args['tax_query'] = [
            [
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => $cat,
            ]
        ];
    }

    $q = new WP_Query($args);

    ob_start();

    if ($q->have_posts()) {

        while ($q->have_posts()) {
            $q->the_post();
            global $product;
            ?>

<div class="swiper-slide product-card">

    <a href="<?php the_permalink(); ?>" class="body-product">

        <div class="product-image">
            <?php echo woocommerce_get_product_thumbnail(); ?>
        </div>

        <!-- wp:paragraph {,"fontSize":"md","style":{"typography":{"fontWeight":"700"}}} -->
        <p class="text-body has-md-font-size"><?php the_title(); ?></p>


    </a>

</div>

<?php
        }

    } else {
        echo '<p class="no-product">محصولی یافت نشد</p>';
    }

    wp_reset_postdata();

    return ob_get_clean();
}

function wc_category_tabs() {

    $terms = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => true,
    ]);

    ob_start();
    ?>

<div class="wc-tabs" id="wcTabs">

    <button class="active category-slider-name" data-cat="all">همه</button>

    <?php foreach ($terms as $term): ?>
    <button data-cat="<?php echo esc_attr($term->slug); ?>" class="category-slider-name">
        <?php echo esc_html($term->name); ?>
    </button>
    <?php endforeach; ?>

</div>

<?php
    return ob_get_clean();
}
add_shortcode('wc_tabs', 'wc_category_tabs');

add_theme_support('woocommerce', array(
    'thumbnail_image_width' => 300,
    'single_image_width'    => 600,
    'product_grid'          => array(
        'default_rows'    => 4,
        'min_columns'     => 2,
        'max_columns'     => 4,
    ),
));



function create_brands_cpt() {

    register_post_type('brands',
        array(
            'labels' => array(
                'name' => 'Brands',
                'singular_name' => 'Brand'
            ),
            'public' => true,
            'menu_icon' => 'dashicons-store',
            'supports' => array('title', 'thumbnail'),
        )
    );

}
add_action('init', 'create_brands_cpt');

function brand_slider_shortcode() {

 $brands = new WP_Query([
    'post_type' => 'brands',
    'posts_per_page' => -1,
    'post_status' => 'publish'
]);

    ob_start();
    ?>

<div class="swiper brandSwiper">
    <div class="swiper-wrapper">

        <?php if ($brands->have_posts()) : ?>

        <?php while ($brands->have_posts()) : $brands->the_post(); ?>

        <div class="swiper-slide brand-item">


            <?php 
             if (has_post_thumbnail()) { the_post_thumbnail('medium'); } else {
            echo '<div class="brand-empty">No Logo</div>' ; }
            ?>
        </div>

        <?php endwhile; ?>

        <?php else: ?>

        <div class="swiper-slide">
            <p style="text-align:center;width:100%">
                هیچ برندی ثبت نشده
            </p>
        </div>

        <?php endif; ?>

    </div>

    <div class="swiper-button-next brand-next"></div>
    <div class="swiper-button-prev brand-prev"></div>

</div>

<?php
    wp_reset_postdata();
    return ob_get_clean();
}

add_shortcode('brand_slider_shortcode', 'brand_slider_shortcode');




function green_product_slider_shortcode() {

    $q = new WP_Query([
        'post_type' => 'product',
        'posts_per_page' => 10,
        'post_status' => 'publish'
    ]);

    ob_start();
    ?>

<div class="swiper greenProductSwiper">
    <div class="swiper-wrapper">

        <?php while ($q->have_posts()) : $q->the_post();
                global $product;
            ?>

        <div class="swiper-slide product-card">

            <a href="<?php the_permalink(); ?>" style="text-decoration: none;">
                <div style="width: 100%; display: flex;justify-content:space-between;align-items: center">
                    <span class="discount-text">
                        <span>تخفیف ویژه</span>
                    </span>
                    <span class="amount-discount">٪۲۷-</span>
                </div>
                <?php echo woocommerce_get_product_thumbnail(); ?>

                <p class="product-title">
                    <?php the_title(); ?>
                </p>

                <div style="display: flex ">
                    <div class="price"
                        style="width: 70%; display: flex; align-items: start; justify-content: flex; height: 70px;   ">
                        <?php echo $product->get_price_html(); ?>
                    </div>
                    <div
                        style="width: 30%;height: 82px !important; text-align: center; display: flex; align-items: center;justify-content: center;">
                        <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="32" height="32" rx="16" transform="matrix(-1 0 0 1 32 0)" fill="#009E00" />
                            <path d="M16 11.3333V20.6666M20.6667 15.9999H11.3334" stroke="white" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg>

                    </div>
                </div>


            </a>

        </div>

        <?php endwhile; ?>

    </div>

    <!-- arrows -->


</div>

<?php
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('green_slider', 'green_product_slider_shortcode');


function latest_articles_shortcode() {

    $blog_page_id = get_option('page_for_posts');
    $blog_url = $blog_page_id ? get_permalink($blog_page_id) : home_url('/blog/');

    $q = new WP_Query([
        'post_type'           => 'post',
        'posts_per_page'      => 3,
        'post_status'         => 'publish',
        'ignore_sticky_posts' => true,
    ]);

    ob_start();
    ?>

<section class="latest-articles-section">

    <div class="latest-articles-header">
        <h2>مقاله‌ها و اخبار</h2>

        <a href="<?php echo esc_url($blog_url); ?>" class="latest-articles-btn">
            <span>مشاهده همه</span>
            <span>></span>
        </a>
    </div>

    <div class="latest-articles-grid">

        <?php if ($q->have_posts()) : ?>
        <?php while ($q->have_posts()) : $q->the_post(); ?>

        <article class="article-card">

            <a href="<?php the_permalink(); ?>" class="article-image">
                <?php if (has_post_thumbnail()) : ?>
                <?php the_post_thumbnail('large'); ?>
                <?php else : ?>
                <div class="article-placeholder"></div>
                <?php endif; ?>
            </a>

            <div class="article-content">
                <h3>
                    <a href="<?php the_permalink(); ?>">
                        <?php the_title(); ?>
                    </a>
                </h3>

                <p>
                    <?php echo esc_html(wp_trim_words(get_the_excerpt(), 16, '...')); ?>
                </p>

                <a href="<?php the_permalink(); ?>" class="article-read-more">
                    <span style="">بیشتر بخوانید</span>
                    <span style="max-height: fit-content !important; padding-bottom:20px;">
                        > </span>
                </a>
            </div>

        </article>

        <?php endwhile; ?>
        <?php endif; ?>

    </div>

</section>

<?php
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('latest_articles', 'latest_articles_shortcode');