<?php
/** Dynamic, FSE-editable WooCommerce products-by-category section. */

if (! defined('ABSPATH')) {
    exit;
}

function my_theme_category_products_terms() {
    if (! taxonomy_exists('product_cat')) {
        return [];
    }

    $args = [
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
        'orderby'    => 'count',
        'order'      => 'DESC',
    ];

    $terms = get_terms($args);
    if (is_wp_error($terms)) {
        return [];
    }

    $default_id = absint(get_option('default_product_cat'));
    $terms = array_values(array_filter($terms, static function ($term) use ($default_id) {
        return (int) $term->term_id !== $default_id && 'uncategorized' !== $term->slug;
    }));

    return array_slice($terms, 0, 7);
}

function my_theme_category_products_cards($category_id, $limit) {
    if (! class_exists('WooCommerce')) {
        return '<p class="no-product">برای نمایش محصولات، ووکامرس را فعال کنید.</p>';
    }

    $query = new WP_Query([
        'post_type'           => 'product',
        'post_status'         => 'publish',
        'posts_per_page'      => max(1, min(24, absint($limit))),
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
        'tax_query'           => [[
            'taxonomy' => 'product_cat',
            'field'    => 'term_id',
            'terms'    => [absint($category_id)],
        ]],
    ]);

    ob_start();
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            ?>
            <div class="swiper-slide product-card">
                <a href="<?php the_permalink(); ?>" class="body-product">
                    <div class="product-image"><?php echo woocommerce_get_product_thumbnail(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                    <p class="text-body has-md-font-size"><span class="product-title-text"><?php the_title(); ?></span></p>
                </a>
            </div>
            <?php
        }
    } else {
        echo '<p class="no-product">محصولی در این دسته یافت نشد.</p>';
    }
    wp_reset_postdata();
    return trim(ob_get_clean());
}

function my_theme_render_category_products($attributes = []) {
    $title           = isset($attributes['title']) ? sanitize_text_field($attributes['title']) : 'محصولات براساس دسته‌بندی';
    $products_count  = isset($attributes['productsCount']) ? max(1, min(24, absint($attributes['productsCount']))) : 12;
    $button_text     = isset($attributes['buttonText']) ? sanitize_text_field($attributes['buttonText']) : 'مشاهده همه';
    $button_url      = isset($attributes['buttonUrl']) ? esc_url($attributes['buttonUrl']) : '';
    $show_navigation = ! isset($attributes['showNavigation']) || (bool) $attributes['showNavigation'];
    $show_pagination = ! isset($attributes['showPagination']) || (bool) $attributes['showPagination'];
    $terms           = my_theme_category_products_terms();
    $active_term     = $terms ? $terms[0] : null;
    $instance_id     = wp_unique_id('category-products-');

    $color = static function ($key, $default) use ($attributes) {
        $value = isset($attributes[$key]) ? sanitize_hex_color((string) $attributes[$key]) : '';
        return $value ?: $default;
    };
    $number = static function ($key, $default, $min, $max) use ($attributes) {
        $value = isset($attributes[$key]) ? (float) $attributes[$key] : (float) $default;
        return max($min, min($max, $value));
    };
    $style_values = [
        '--category-products-heading-color:' . $color('headingColor', '#1a1e1b'),
        '--category-products-heading-size:' . $number('headingSize', 32, 14, 72) . 'px',
        '--category-products-tab-color:' . $color('tabColor', '#111111'),
        '--category-products-active-tab-color:' . $color('activeTabColor', '#111111'),
        '--category-products-active-border:' . $color('activeTabBorderColor', '#16a34a'),
        '--category-products-tab-size:' . $number('tabSize', 15, 10, 28) . 'px',
        '--category-products-card-bg:' . $color('cardBackground', '#ffffff'),
        '--category-products-card-border:' . $color('cardBorderColor', '#d9d9d9'),
        '--category-products-card-border-width:' . $number('cardBorderWidth', 0, 0, 8) . 'px',
        '--category-products-card-radius:' . $number('cardRadius', 8, 0, 60) . 'px',
        '--category-products-card-padding:' . $number('cardPadding', 0, 0, 50) . 'px',
        '--category-products-mobile-card-padding:' . $number('mobileCardPadding', 0, 0, 50) . 'px',
        '--category-products-mobile-card-width:' . $number('mobileCardWidth', 200, 180, 320) . 'px',
        '--category-products-mobile-card-height:' . $number('mobileCardHeight', 268, 240, 420) . 'px',
        '--category-products-mobile-card-radius:' . $number('mobileCardRadius', 8, 0, 60) . 'px',
        '--category-products-mobile-card-shadow:' . (! isset($attributes['mobileCardShadow']) || $attributes['mobileCardShadow'] ? '0 2px 7px rgba(0,0,0,.18)' : 'none'),
        '--category-products-mobile-card-border-width:' . $number('mobileCardBorderWidth', 1, 0, 8) . 'px',
        '--category-products-card-width:' . $number('cardWidth', 200, 140, 400) . 'px',
        '--category-products-card-height:' . $number('cardHeight', 270, 180, 520) . 'px',
        '--category-products-card-shadow:' . (! empty($attributes['cardShadow']) ? '0 8px 30px rgba(0,0,0,.12)' : 'none'),
        '--category-products-image-height:' . $number('imageHeight', 96, 60, 320) . 'px',
        '--category-products-mobile-image-height:' . $number('mobileImageHeight', 150, 60, 200) . 'px',
        '--category-products-mobile-image-container-height:' . $number('mobileImageContainerHeight', 200, 160, 260) . 'px',
        '--category-products-title-color:' . $color('productTitleColor', '#1a1e1b'),
        '--category-products-title-size:' . $number('productTitleSize', 16, 10, 30) . 'px',
        '--category-products-mobile-title-size:' . $number('mobileProductTitleSize', 16, 12, 24) . 'px',
        '--category-products-mobile-heading-size:' . $number('mobileHeadingSize', 22, 16, 36) . 'px',
        '--category-products-mobile-tab-size:' . $number('mobileTabSize', 14, 10, 24) . 'px',
        '--category-products-arrow-bg:' . $color('arrowBackground', '#16a34a'),
        '--category-products-arrow-color:' . $color('arrowColor', '#ffffff'),
        '--category-products-arrow-size:' . $number('arrowSize', 48, 28, 80) . 'px',
        '--category-products-bullet:' . $color('bulletColor', '#ffffff'),
        '--category-products-bullet-active:' . $color('bulletActiveColor', '#d2d2d2'),
        '--category-products-button-bg:' . $color('buttonBackground', '#16a34a'),
        '--category-products-button-color:' . $color('buttonColor', '#ffffff'),
        '--category-products-button-radius:' . $number('buttonRadius', 56, 0, 80) . 'px',
    ];

    if ('' === $button_url) {
        $button_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
    }

    $wrapper_attributes = get_block_wrapper_attributes([
        'class' => 'category-products-section',
        'dir'   => 'rtl',
        'style' => implode(';', $style_values),
    ]);

    ob_start(); ?>
    <div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
        <?php if ('' !== $title) : ?><h2 class="wp-block-heading has-text-align-center has-xl-font-size title-based-on-category"><?php echo esc_html($title); ?></h2><?php endif; ?>
        <?php if ($terms) : ?>
            <div class="wc-tabs" role="tablist" aria-label="<?php echo esc_attr($title ?: 'دسته‌بندی محصولات'); ?>">
                <?php foreach ($terms as $index => $term) : ?>
                    <?php if (0 === $index % 4) : ?><div class="wc-tabs-row"><?php endif; ?>
                    <button id="<?php echo esc_attr($instance_id . '-tab-' . $term->term_id); ?>" type="button" role="tab" aria-controls="<?php echo esc_attr($instance_id . '-panel'); ?>" aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>" tabindex="<?php echo 0 === $index ? '0' : '-1'; ?>" data-cat="<?php echo esc_attr($term->term_id); ?>" class="category-slider-name<?php echo 0 === $index ? ' active' : ''; ?>"><?php echo esc_html($term->name); ?></button>
                    <?php if (3 === $index % 4 || $index === count($terms) - 1) : ?></div><?php endif; ?>
                <?php endforeach; ?>
            </div>
            <div class="product-slider-wrap" data-products-count="<?php echo esc_attr($products_count); ?>" data-desktop-gap="<?php echo esc_attr($number('desktopGap', 40, 0, 100)); ?>" data-tablet-gap="<?php echo esc_attr($number('tabletGap', 32, 0, 100)); ?>" data-mobile-gap="<?php echo esc_attr($number('mobileGap', 10, 0, 100)); ?>">
                <div id="<?php echo esc_attr($instance_id . '-panel'); ?>" class="swiper productSwiper" role="tabpanel" aria-labelledby="<?php echo esc_attr($instance_id . '-tab-' . $active_term->term_id); ?>" aria-live="polite">
                    <div class="swiper-wrapper"><?php echo my_theme_category_products_cards($active_term->term_id, $products_count); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                    <?php if ($show_pagination) : ?><div class="swiper-pagination product-pagination"></div><?php endif; ?>
                </div>
                <p class="category-products-status" role="status" aria-live="polite"></p>
                <?php if ($show_navigation) : ?><button type="button" class="swiper-button-next product-next" aria-label="محصولات قبلی"></button><button type="button" class="swiper-button-prev product-prev" aria-label="محصولات بعدی"></button><?php endif; ?>
            </div>
        <?php else : ?>
            <p class="no-product">هنوز دسته‌بندی محصول قابل نمایشی وجود ندارد.</p>
        <?php endif; ?>
        <?php if ('' !== $button_text) : ?>
            <div class="wp-block-buttons category-products-section__actions"><div class="wp-block-button"><a class="wp-block-button__link has-background-color has-primary-background-color has-background wp-element-button" href="<?php echo esc_url($button_url); ?>"><?php echo esc_html($button_text); ?></a></div></div>
        <?php endif; ?>
    </div>
    <?php return trim(ob_get_clean());
}

function my_theme_category_products_ajax() {
    check_ajax_referer('my_theme_category_products', 'nonce');
    $category_id = isset($_POST['cat']) ? absint($_POST['cat']) : 0;
    $limit       = isset($_POST['limit']) ? absint($_POST['limit']) : 12;
    if (! $category_id || ! term_exists($category_id, 'product_cat')) {
        wp_send_json_error(['message' => 'دسته‌بندی نامعتبر است.'], 400);
    }
    wp_send_json_success(['html' => my_theme_category_products_cards($category_id, $limit)]);
}
add_action('wp_ajax_my_theme_category_products', 'my_theme_category_products_ajax');
add_action('wp_ajax_nopriv_my_theme_category_products', 'my_theme_category_products_ajax');

function my_theme_category_products_runtime_data() {
    wp_localize_script('my-theme-category-products-view-script', 'myThemeCategoryProducts', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('my_theme_category_products'),
        'loading' => 'در حال دریافت محصولات…',
        'error'   => 'دریافت محصولات انجام نشد. دوباره تلاش کنید.',
    ]);
}
add_action('wp_enqueue_scripts', 'my_theme_category_products_runtime_data', 30);

function my_theme_migrate_legacy_category_products() {
    if (! is_admin() || wp_doing_ajax()) {
        return;
    }
    $templates = get_posts(['post_type' => 'wp_template', 'post_status' => ['publish', 'draft'], 'posts_per_page' => -1, 'suppress_filters' => false]);
    foreach ($templates as $template) {
        $content = (string) $template->post_content;
        if (false === strpos($content, '[wc_tabs]') || false === strpos($content, 'product-slider-wrap')) {
            continue;
        }
        $replacement = preg_replace('/<!-- wp:group \{"tagName":"main".*?<!-- \/wp:group -->/s', '<!-- wp:my-theme/category-products /-->', $content, 1);
        if (! is_string($replacement) || $replacement === $content) {
            continue;
        }
        if ('' === (string) get_post_meta($template->ID, '_my_theme_category_products_backup', true)) {
            add_post_meta($template->ID, '_my_theme_category_products_backup', $content, true);
        }
        wp_update_post(['ID' => $template->ID, 'post_content' => $replacement]);
    }
}
add_action('admin_init', 'my_theme_migrate_legacy_category_products', 9);
