<?php

if (! defined('ABSPATH')) {
    exit;
}

function create_brands_cpt() {
    register_post_type('brands',
        array(
            'labels' => array(
                'name' => 'Brands',
                'singular_name' => 'Brand',
                'add_new_item' => __('Add New Brand', 'my-theme'),
                'edit_item' => __('Edit Brand', 'my-theme'),
                'new_item' => __('New Brand', 'my-theme'),
                'view_item' => __('View Brand', 'my-theme'),
                'search_items' => __('Search Brands', 'my-theme'),
            ),
            'public' => true,
            'menu_icon' => 'dashicons-store',
            'show_in_rest' => false,
            'supports' => array('title', 'thumbnail'),
        )
    );
}
add_action('init', 'create_brands_cpt');

/**
 * Rename the Brand title placeholder.
 *
 * @param string  $title Placeholder text.
 * @param WP_Post $post  Current post object.
 * @return string
 */
function my_theme_brand_title_placeholder($title, $post) {
    if ($post instanceof WP_Post && 'brands' === $post->post_type) {
        return __('Brand title', 'my-theme');
    }

    return $title;
}
add_filter('enter_title_here', 'my_theme_brand_title_placeholder', 10, 2);

/**
 * Add a simple Description textarea for Brand posts.
 */
function my_theme_add_brand_description_meta_box() {
    add_meta_box(
        'my-theme-brand-description',
        __('Brand Description', 'my-theme'),
        'my_theme_render_brand_description_meta_box',
        'brands',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes_brands', 'my_theme_add_brand_description_meta_box');

/**
 * Render the Brand Description textarea.
 *
 * @param WP_Post $post Current Brand post.
 */
function my_theme_render_brand_description_meta_box($post) {
    wp_nonce_field('my_theme_save_brand_description', 'my_theme_brand_description_nonce');

    $description = get_post_field('post_content', $post->ID);
    ?>
    <p>
        <label for="my-theme-brand-description-field">
            <?php echo esc_html__('Description', 'my-theme'); ?>
        </label>
    </p>
    <textarea
        id="my-theme-brand-description-field"
        name="my_theme_brand_description"
        rows="8"
        style="box-sizing:border-box;width:100%;"
    ><?php echo esc_textarea($description); ?></textarea>
    <?php
}

/**
 * Save the Brand Description textarea into post_content.
 *
 * @param int     $post_id Current post ID.
 * @param WP_Post $post    Current post object.
 */
function my_theme_save_brand_description($post_id, $post) {
    if (! $post instanceof WP_Post || 'brands' !== $post->post_type) {
        return;
    }

    if (
        ! isset($_POST['my_theme_brand_description_nonce']) ||
        ! wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['my_theme_brand_description_nonce'])),
            'my_theme_save_brand_description'
        )
    ) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (! current_user_can('edit_post', $post_id)) {
        return;
    }

    $description = isset($_POST['my_theme_brand_description'])
        ? wp_kses_post(wp_unslash($_POST['my_theme_brand_description']))
        : '';

    remove_action('save_post_brands', 'my_theme_save_brand_description', 10);

    wp_update_post(
        array(
            'ID'           => $post_id,
            'post_content' => $description,
        )
    );

    add_action('save_post_brands', 'my_theme_save_brand_description', 10, 2);
}
add_action('save_post_brands', 'my_theme_save_brand_description', 10, 2);

/**
 * Return the product-brand taxonomy that should mirror Brand CPT entries.
 *
 * @return string
 */
function my_theme_get_primary_product_brand_taxonomy() {
    $taxonomies = function_exists('my_theme_get_product_brand_taxonomies')
        ? my_theme_get_product_brand_taxonomies()
        : ['product_brand', 'pwb-brand', 'pa_brand'];

    foreach ($taxonomies as $taxonomy) {
        if (taxonomy_exists($taxonomy)) {
            return $taxonomy;
        }
    }

    return '';
}

/**
 * Mirror a Brand CPT post into the WooCommerce product brand taxonomy.
 *
 * Product edit screens list taxonomy terms, not Brand CPT posts. This keeps the
 * custom Brands admin menu and the product Brand selector in sync.
 *
 * @param int     $post_id Brand post ID.
 * @param WP_Post $post    Brand post object.
 */
function my_theme_sync_brand_post_to_product_brand_term($post_id, $post) {
    if (! $post instanceof WP_Post || 'brands' !== $post->post_type) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (! current_user_can('edit_post', $post_id)) {
        return;
    }

    $taxonomy = my_theme_get_primary_product_brand_taxonomy();

    if ('' === $taxonomy) {
        return;
    }

    $brand_name = trim(wp_strip_all_tags($post->post_title));

    if ('' === $brand_name) {
        return;
    }

    $slug = sanitize_title($brand_name);
    $term = get_term_by('slug', $slug, $taxonomy);

    if (! $term instanceof WP_Term) {
        $term = get_term_by('name', $brand_name, $taxonomy);
    }

    if ($term instanceof WP_Term) {
        wp_update_term(
            $term->term_id,
            $taxonomy,
            array(
                'name' => $brand_name,
                'slug' => $slug,
            )
        );

        update_post_meta($post_id, '_product_brand_term_id', (int) $term->term_id);
        update_post_meta($post_id, '_product_brand_taxonomy', $taxonomy);

        return;
    }

    $created_term = wp_insert_term(
        $brand_name,
        $taxonomy,
        array(
            'slug' => $slug,
        )
    );

    if (is_wp_error($created_term) || empty($created_term['term_id'])) {
        return;
    }

    update_post_meta($post_id, '_product_brand_term_id', (int) $created_term['term_id']);
    update_post_meta($post_id, '_product_brand_taxonomy', $taxonomy);
}
add_action('save_post_brands', 'my_theme_sync_brand_post_to_product_brand_term', 20, 2);

function brand_slider_shortcode() {

    $brands = new WP_Query([
        'post_type'      => 'brands',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'menu_order title',
        'order'          => 'ASC',
    ]);

    ob_start();
    ?>

<div class="brand-slider-wrap" dir="rtl">
    <div class="swiper brandSwiper">
        <div class="swiper-wrapper">

            <?php if ($brands->have_posts()) : ?>

            <?php while ($brands->have_posts()) : $brands->the_post(); ?>

            <div class="swiper-slide brand-item">


                <?php 
                 if (has_post_thumbnail()) { the_post_thumbnail('medium'); } else {
                echo '<div class="brand-empty">' . esc_html__('No Logo', 'my-theme') . '</div>' ; }
                ?>
            </div>

            <?php endwhile; ?>

            <?php else: ?>

            <div class="swiper-slide">
                <p class="brand-empty-message">هیچ برندی ثبت نشده</p>
            </div>

            <?php endif; ?>

        </div>
    </div>

    <div class="swiper-button-next brand-next"></div>
    <div class="swiper-button-prev brand-prev"></div>
</div>

<?php
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('brand_slider_shortcode', 'brand_slider_shortcode');

function my_theme_green_product_format_price($price) {
    return wc_price((float) $price, [
        'thousand_separator' => ',',
        'decimal_separator'  => wc_get_price_decimal_separator(),
        'decimals'           => wc_get_price_decimals(),
    ]);
}

function my_theme_green_product_get_price_html($product) {
    if ($product->is_type('variable')) {
        $regular_price = $product->get_variation_regular_price('min', true);
        $sale_price    = $product->get_variation_sale_price('min', true);
        $current_price = $product->get_variation_price('min', true);
    } else {
        $regular_price = $product->get_regular_price();
        $sale_price    = $product->get_sale_price();
        $current_price = $product->get_price();
    }

    if ($current_price === '') {
        return $product->get_price_html();
    }

    $display_current = wc_get_price_to_display($product, ['price' => $current_price]);

    if ($product->is_on_sale() && $regular_price !== '' && $sale_price !== '' && (float) $regular_price > (float) $sale_price) {
        $display_regular = wc_get_price_to_display($product, ['price' => $regular_price]);

        return '<del>' . my_theme_green_product_format_price($display_regular) . '</del><ins>' . my_theme_green_product_format_price($display_current) . '</ins>';
    }

    return my_theme_green_product_format_price($display_current);
}

function my_theme_green_product_get_sale_percentage($product) {
    if (! $product instanceof WC_Product) {
        return 0;
    }

    $discount_percentage = 0;

    if ($product->is_type('variable')) {
        foreach ($product->get_children() as $variation_id) {
            $variation = wc_get_product($variation_id);

            if (! $variation instanceof WC_Product_Variation) {
                continue;
            }

            $regular_price = (float) $variation->get_regular_price();
            $sale_price    = (float) $variation->get_sale_price();

            if (
                ! $variation->is_on_sale()
                || 0 >= $regular_price
                || 0 > $sale_price
                || $sale_price >= $regular_price
            ) {
                continue;
            }

            $variation_discount = (int) round((($regular_price - $sale_price) / $regular_price) * 100);
            $discount_percentage = max($discount_percentage, $variation_discount);
        }

        return max(0, min(100, $discount_percentage));
    }

    $regular_price = $product->get_regular_price();
    $sale_price    = $product->get_sale_price();

    if (! $product->is_on_sale() || '' === $regular_price || '' === $sale_price) {
        return 0;
    }

    $regular_price = (float) $regular_price;
    $sale_price    = (float) $sale_price;

    if (0 >= $regular_price || $sale_price >= $regular_price) {
        return 0;
    }

    $discount_percentage = (int) round((($regular_price - $sale_price) / $regular_price) * 100);

    return max(0, min(100, $discount_percentage));
}

function my_theme_render_green_product_card($product, $args = []) {
    if (! $product instanceof WC_Product) {
        return '';
    }

    $args = wp_parse_args($args, [
        'show_promo_header'      => true,
        'show_sale_image_badge'  => false,
        'show_sale_footer_badge' => false,
        'show_stock_status'      => false,
        'extra_class'            => '',
        'show_view_button'       => false,
        'sale_label_text'        => __('تخفیف ویژه', 'my-theme'),
    ]);

    $card_classes = 'swiper-slide product-card green-product-card';
    $sale_percentage = my_theme_green_product_get_sale_percentage($product);
    $is_in_stock     = $product->is_in_stock();

    if (! $args['show_promo_header']) {
        $card_classes .= ' green-product-card--without-promo-header';
    }

    if ('' !== trim((string) $args['extra_class'])) {
        $card_classes .= ' ' . trim((string) $args['extra_class']);
    }

    ob_start();
    ?>
    <div class="<?php echo esc_attr($card_classes); ?>">
        <?php if ($args['show_sale_image_badge'] && 0 < $sale_percentage && '' !== trim((string) $args['sale_label_text'])) : ?>
            <span class="green-product-sale-label">
                <?php echo esc_html($args['sale_label_text']); ?>
            </span>
        <?php endif; ?>

        <a href="<?php echo esc_url($product->get_permalink()); ?>" class="green-product-link">
            <?php if ($args['show_promo_header']) : ?>
                <div class="green-product-badge-row">
                    <?php if (0 < $sale_percentage) : ?>
                        <span class="amount-discount" aria-label="<?php echo esc_attr(sprintf(__('%d percent discount', 'my-theme'), $sale_percentage)); ?>"><?php echo esc_html('%' . number_format_i18n($sale_percentage)); ?></span>
                    <?php endif; ?>

                    <span class="discount-text">
                        <span><?php echo esc_html__('تخفیف ویژه', 'my-theme'); ?></span>
                    </span>
                </div>
            <?php endif; ?>
            <div class="green-product-image">
                <?php echo wp_kses_post($product->get_image('woocommerce_thumbnail')); ?>
            </div>

            <p class="product-title">
                <?php echo esc_html($product->get_name()); ?>
            </p>

            <div class="green-product-footer">
                <?php if ($args['show_stock_status'] && ! $is_in_stock) : ?>
                    <div class="price green-product-price green-product-price--out-of-stock">
                        <span class="green-product-stock-status">
                        <?php echo esc_html__('ناموجود', 'my-theme'); ?>
                        </span>
                    </div>
                <?php else : ?>
                    <div class="price green-product-price">
                        <?php echo wp_kses_post(my_theme_green_product_get_price_html($product)); ?>
                    </div>
                <?php endif; ?>

                <?php if ($is_in_stock && $args['show_sale_footer_badge'] && 0 < $sale_percentage) : ?>
                    <span class="green-product-discount-badge">
                        <?php echo esc_html('٪' . number_format_i18n($sale_percentage)); ?>
                    </span>
                <?php endif; ?>
                <div class="green-product-add">
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                        <path d="M5.66665 1V10.3333M10.3333 5.66667H0.99998" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
            </div>

            <?php if ($args['show_view_button']) : ?>
                <span class="search-result-mobile-product-card__button">
                    <span class="search-result-mobile-product-card__button-text">
                        <?php echo esc_html__('مشاهده محصول', 'my-theme'); ?>
                    </span>
                </span>
            <?php endif; ?>
        </a>
    </div>
    <?php
    return trim(ob_get_clean());
}

function green_product_slider_shortcode() {

    $q = new WP_Query([
        'post_type'      => 'product',
        'posts_per_page' => 10,
        'post_status'    => 'publish',
    ]);

    ob_start();
    ?>

<div class="swiper greenProductSwiper" dir="rtl">
    <div class="swiper-wrapper">

        <?php if ($q->have_posts()) : ?>
        <?php while ($q->have_posts()) : $q->the_post();
            $product = wc_get_product(get_the_ID());

            if (! $product) {
                continue;
            }
        ?>

        <?php
        echo my_theme_render_green_product_card( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            $product,
            [
                'show_promo_header'      => false,
                'show_sale_image_badge'  => true,
                'show_sale_footer_badge' => true,
                'show_stock_status'      => true,
            ]
        );
        ?>

        <?php endwhile; ?>
        <?php else : ?>

        <div class="swiper-slide product-card green-product-card">
            <p class="green-empty-message">هیچ محصولی ثبت نشده</p>
        </div>

        <?php endif; ?>

    </div>

    <!-- arrows -->


</div>

<?php
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('green_slider', 'green_product_slider_shortcode');

/**
 * Render the shared article card used by article listing contexts.
 *
 * @param int|WP_Post|null $post Post object or ID.
 * @return string
 */
function my_theme_render_article_card($post = null) {
    $post = get_post($post);

    if (! $post instanceof WP_Post) {
        return '';
    }

    $permalink = get_permalink($post);
    $title     = get_the_title($post);
    $excerpt   = wp_trim_words(get_the_excerpt($post), 16, '...');

    ob_start();
    ?>
<article class="swiper-slide article-card">
    <a href="<?php echo esc_url($permalink); ?>" class="article-image">
        <?php if (has_post_thumbnail($post)) : ?>
            <?php echo get_the_post_thumbnail($post, 'large'); ?>
        <?php else : ?>
            <div class="article-placeholder"></div>
        <?php endif; ?>
    </a>

    <div class="article-content">
        <a class="article-content-title" href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a>

        <p><?php echo esc_html($excerpt); ?></p>

        <a href="<?php echo esc_url($permalink); ?>" class="article-read-more">
            <span><?php echo esc_html__('بیشتر بخوانید', 'my-theme'); ?></span>
            <svg width="8" height="12" viewBox="0 0 8 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                <path d="M3.34799 4.9036C2.92005 5.29944 2.92005 5.97595 3.34799 6.37179L6.73372 9.5036C7.16167 9.89944 7.16167 10.5759 6.73372 10.9718L6.69287 11.0096C6.30959 11.3641 5.71807 11.3641 5.33479 11.0096L0.320966 6.3718C-0.106978 5.97595 -0.106979 5.29944 0.320965 4.9036L5.33479 0.26581C5.71807 -0.0887244 6.30959 -0.0887254 6.69287 0.265809L6.73372 0.303595C7.16167 0.699443 7.16167 1.37595 6.73372 1.77179L3.34799 4.9036Z" fill="#009E00" />
            </svg>
        </a>
    </div>
</article>
<?php
    return trim(ob_get_clean());
}

function my_theme_render_latest_articles_section($query_args = [], $section_args = []) {

    $blog_url = function_exists('my_theme_get_blog_list_url')
        ? my_theme_get_blog_list_url()
        : home_url('/blog/');

    $query_args = wp_parse_args(
        $query_args,
        [
            'post_type'           => 'post',
            'posts_per_page'      => 3,
            'post_status'         => 'publish',
            'ignore_sticky_posts' => true,
        ]
    );

    $section_args = wp_parse_args(
        $section_args,
        [
            'title'         => 'مقاله‌ها و اخبار',
            'view_all_url'  => $blog_url,
            'show_view_all' => true,
            'heading_id'    => '',
            'empty_text'    => 'مقاله‌ای پیدا نشد.',
        ]
    );

    $q = new WP_Query($query_args);

    ob_start();
    ?>

<section class="latest-articles-section">

    <div class="latest-articles-header">
        <h2 <?php echo '' !== $section_args['heading_id'] ? 'id="' . esc_attr($section_args['heading_id']) . '"' : ''; ?>>
            <?php echo esc_html($section_args['title']); ?>
        </h2>

        <?php if ($section_args['show_view_all']) : ?>
        <a href="<?php echo esc_url($section_args['view_all_url']); ?>" class="latest-articles-btn">
            <span><span class="latest-articles-extra"><?php echo esc_html__('مشاهده ', 'my-theme'); ?></span><?php echo esc_html__('همه', 'my-theme'); ?></span>
            <svg width="10" height="16" viewBox="0 0 10 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M4.49445 6.91232C4.05141 7.30972 4.05141 8.00376 4.49445 8.40115L9.67244 13.0457C10.1155 13.4431 10.1155 14.1371 9.67244 14.5345L9.089 15.0578C8.70908 15.3986 8.13349 15.3986 7.75357 15.0578L0.332288 8.40115C-0.110756 8.00376 -0.110756 7.30972 0.332287 6.91232L7.75357 0.255662C8.13349 -0.0851187 8.70908 -0.0851196 9.089 0.255661L9.67244 0.77899C10.1155 1.17639 10.1155 1.87042 9.67244 2.26782L4.49445 6.91232Z"
                    fill="white" />
            </svg>
        </a>
        <?php endif; ?>
    </div>

    <div class="swiper latestArticlesSwiper">
        <div class="swiper-wrapper latest-articles-grid">

            <?php if ($q->have_posts()) : ?>
            <?php while ($q->have_posts()) : $q->the_post(); ?>

            <article class="swiper-slide article-card">

                <a href="<?php the_permalink(); ?>" class="article-image">
                    <?php if (has_post_thumbnail()) : ?>
                    <?php the_post_thumbnail('large'); ?>
                    <?php else : ?>
                    <div class="article-placeholder"></div>
                    <?php endif; ?>
                </a>

                <div class="article-content">
                    <a class="article-content-title" href="<?php the_permalink(); ?>"><?php echo esc_html(get_the_title()); ?></a>

                    <p>
                        <?php echo esc_html(wp_trim_words(get_the_excerpt(), 16, '...')); ?>
                    </p>

                    <a href="<?php the_permalink(); ?>" class="article-read-more">
                        <span><?php echo esc_html__('بیشتر بخوانید', 'my-theme'); ?></span>
                        <svg width="8" height="12" viewBox="0 0 8 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M3.34799 4.9036C2.92005 5.29944 2.92005 5.97595 3.34799 6.37179L6.73372 9.5036C7.16167 9.89944 7.16167 10.5759 6.73372 10.9718L6.69287 11.0096C6.30959 11.3641 5.71807 11.3641 5.33479 11.0096L0.320966 6.3718C-0.106978 5.97595 -0.106979 5.29944 0.320965 4.9036L5.33479 0.26581C5.71807 -0.0887244 6.30959 -0.0887254 6.69287 0.265809L6.73372 0.303595C7.16167 0.699443 7.16167 1.37595 6.73372 1.77179L3.34799 4.9036Z"
                                fill="#009E00" />
                        </svg>
                    </a>
                </div>

            </article>

            <?php endwhile; ?>
            <?php else : ?>
                <p class="latest-articles-empty"><?php echo esc_html($section_args['empty_text']); ?></p>
            <?php endif; ?>

        </div>

        <div class="swiper-pagination latest-articles-dots latest-articles-pagination"></div>
    </div>

</section>

<?php
    wp_reset_postdata();
    return ob_get_clean();
}

function latest_articles_shortcode() {
    return my_theme_render_latest_articles_section();
}
add_shortcode('latest_articles', 'latest_articles_shortcode');
