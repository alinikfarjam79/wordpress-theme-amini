<?php

if (! defined('ABSPATH')) {
    exit;
}

function my_theme_woocommerce_price_format() {
    return '%1$s&nbsp;%2$s';
}
add_filter('woocommerce_price_format', 'my_theme_woocommerce_price_format');

/**
 * Return product-brand taxonomy slugs supported by the theme.
 *
 * These taxonomies are plugin/WooCommerce-provided; the theme does not register
 * a second product brand system.
 *
 * @return string[]
 */
function my_theme_get_product_brand_taxonomies() {
    return ['product_brand', 'pwb-brand', 'pa_brand'];
}

add_action('wp_ajax_wc_filter_products', 'wc_filter_products');
add_action('wp_ajax_nopriv_wc_filter_products', 'wc_filter_products');

function wc_filter_products() {

    $cat = isset($_POST['cat']) ? sanitize_text_field(wp_unslash($_POST['cat'])) : 'all';

    echo wc_products_grid($cat);

    wp_die();
}

function wc_products_grid($cat = 'all') {

    $args = [
        'post_type'      => 'product',
        'posts_per_page' => 12,
        'post_status'    => 'publish',
    ];

    if ($cat !== 'all') {
        $cat_id = absint($cat);

        if (! $cat_id) {
            return '<p class="no-product">محصولی یافت نشد</p>';
        }

        $args['tax_query'] = [
            [
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => [$cat_id],
            ],
        ];
    }

    $q = new WP_Query($args);

    ob_start();

    if ($q->have_posts()) {

        while ($q->have_posts()) {
            $q->the_post();
            ?>

<div class="swiper-slide product-card">

    <a href="<?php the_permalink(); ?>" class="body-product">

        <div class="product-image">
            <?php echo woocommerce_get_product_thumbnail(); ?>
        </div>

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
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ]);

    if (is_wp_error($terms)) {
        $terms = [];
    }

    $default_cat_id = absint(get_option('default_product_cat'));

    ob_start();
    ?>

<div class="wc-tabs" id="wcTabs">

    <?php $visible_term_index = 0; ?>

    <?php foreach ($terms as $term): ?>
    <?php
        if ((int) $term->term_id === $default_cat_id || $term->slug === 'uncategorized') {
            continue;
        }

        if ($visible_term_index % 4 === 0) {
            echo '<div class="wc-tabs-row">';
        }
    ?>
    <button type="button" data-cat="<?php echo esc_attr($term->term_id); ?>" class="<?php echo $visible_term_index === 0 ? 'active ' : ''; ?>category-slider-name">
        <?php echo esc_html($term->name); ?>
    </button>
    <?php
        $visible_term_index++;

        if ($visible_term_index % 4 === 0) {
            echo '</div>';
        }
    ?>
    <?php endforeach; ?>
    <?php
        if ($visible_term_index > 0 && $visible_term_index % 4 !== 0) {
            echo '</div>';
        }
    ?>

</div>

<?php
    return ob_get_clean();
}
add_shortcode('wc_tabs', 'wc_category_tabs');
