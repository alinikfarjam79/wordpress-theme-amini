<?php
/**
 * Combined product/article search-results page.
 *
 * @package MyTheme
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Return the canonical combined-search path.
 *
 * @return string
 */
function my_theme_search_results_path() {
    return 'search-results';
}

/**
 * Keep the functional search-results page out of automatic page menus.
 *
 * The Header Navigation block falls back to a Page List when no custom menu
 * has been selected. The search endpoint must stay published so searches keep
 * working, but it should not be presented as one of the site's six menu pages.
 *
 * @param int[] $excluded_page_ids Existing excluded page IDs.
 * @return int[]
 */
function my_theme_exclude_search_results_page_from_page_lists($excluded_page_ids) {
    $excluded_page_ids = array_map('absint', (array) $excluded_page_ids);
    $search_page       = get_page_by_path(my_theme_search_results_path(), OBJECT, 'page');

    if ($search_page instanceof WP_Post) {
        $excluded_page_ids[] = absint($search_page->ID);
    }

    return array_values(array_unique(array_filter($excluded_page_ids)));
}
add_filter('wp_list_pages_excludes', 'my_theme_exclude_search_results_page_from_page_lists');

/**
 * Determine whether the current request is the custom combined search page.
 *
 * @return bool
 */
function my_theme_is_combined_search_results_request() {
    if (function_exists('is_page') && is_page(my_theme_search_results_path())) {
        return true;
    }

    $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
    $path        = trim((string) wp_parse_url($request_uri, PHP_URL_PATH), '/');
    $home_path   = trim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/');

    if ('' !== $home_path && 0 === strpos($path, $home_path . '/')) {
        $path = trim(substr($path, strlen($home_path)), '/');
    }

    return my_theme_search_results_path() === $path;
}

/**
 * Read and normalize the current search phrase.
 *
 * The canonical query parameter for the new search flow is `search`; `s` and
 * the older archive-only `product_search` parameter are accepted as fallbacks.
 *
 * @return string
 */
function my_theme_get_current_search_phrase() {
    $keys = ['search', 's', 'product_search'];

    foreach ($keys as $key) {
        if (! isset($_GET[$key])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            continue;
        }

        $value = sanitize_text_field(wp_unslash($_GET[$key])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $value = trim($value);

        if ('' !== $value) {
            return $value;
        }
    }

    return '';
}

/**
 * Build a URL with the canonical search query.
 *
 * @param string $base_url Base URL.
 * @param string $search   Search phrase.
 * @return string
 */
function my_theme_get_search_url($base_url, $search) {
    $search = trim((string) $search);

    if ('' === $search) {
        return $base_url;
    }

    return add_query_arg(
        [
            'search' => $search,
        ],
        $base_url
    );
}

/**
 * Render the shared combined-search form used by the header/homepage.
 *
 * @param array $args Optional form arguments.
 * @return string
 */
function my_theme_render_combined_search_form($args = []) {
    $args = wp_parse_args(
        $args,
        [
            'id'          => 'homepage-search-input',
            'extra_class' => '',
            'placeholder' => __('جستجو', 'my-theme'),
            'value'       => my_theme_get_current_search_phrase(),
            'width'       => '370px',
            'height'      => '40px',
            'action'      => home_url('/search-results/'),
            'aria_label'  => __('Ø¬Ø³ØªØ¬Ùˆ', 'my-theme'),
        ]
    );

    $search       = trim((string) $args['value']);
    $input_id     = sanitize_html_class($args['id']);
    $form_classes = trim('site-header__search homepage-search-form ' . sanitize_html_class($args['extra_class']));
    $placeholder  = (string) $args['placeholder'];
    $width        = preg_match('/^[0-9.]+(px|rem|em|%)$/', (string) $args['width']) ? (string) $args['width'] : '370px';
    $height       = preg_match('/^[0-9.]+(px|rem|em|%)$/', (string) $args['height']) ? (string) $args['height'] : '40px';
    $action       = esc_url_raw((string) $args['action']);
    $aria_label   = (string) $args['aria_label'];

    ob_start();
    ?>
<form class="<?php echo esc_attr($form_classes); ?>" role="search" method="get" action="<?php echo esc_url($action); ?>"
    style="width:<?php echo esc_attr($width); ?>;display:flex;align-items:center;gap:10px;border:1px solid #e0e0e0;border-radius:24px;padding:0 16px;height:<?php echo esc_attr($height); ?>;background:#fff;">
    <label class="screen-reader-text" for="<?php echo esc_attr($input_id); ?>"><?php echo esc_html__('جستجو', 'my-theme'); ?></label>
    <input id="<?php echo esc_attr($input_id); ?>" class="homepage-search-form__input" type="search" name="search" placeholder="<?php echo esc_attr($placeholder); ?>" autocomplete="off" enterkeyhint="search" value="<?php echo esc_attr($search); ?>">
    <button class="site-header__search-submit homepage-search-form__submit" type="submit" aria-label="<?php echo esc_attr__('جستجو', 'my-theme'); ?>">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
            <path d="M9.16667 15.8333C12.8486 15.8333 15.8333 12.8486 15.8333 9.16667C15.8333 5.48477 12.8486 2.5 9.16667 2.5C5.48477 2.5 2.5 5.48477 2.5 9.16667C2.5 12.8486 5.48477 15.8333 9.16667 15.8333Z" stroke="#8A8A8A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M17.5 17.5L13.875 13.875" stroke="#1A1E1B" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </button>
</form>
<?php
    return trim(ob_get_clean());
}

/**
 * Shortcode wrapper for the combined-search form.
 *
 * @return string
 */
function my_theme_combined_search_form_shortcode() {
    return my_theme_render_combined_search_form();
}
add_shortcode('theme_combined_search_form', 'my_theme_combined_search_form_shortcode');

/**
 * Shortcode wrapper for the combined product/article search results body.
 *
 * This allows the results to render inside a normal block theme template
 * instead of through a standalone root PHP document.
 *
 * @return string
 */
function my_theme_combined_search_results_shortcode() {
    return my_theme_render_combined_search_results_page();
}
add_shortcode('theme_combined_search_results', 'my_theme_combined_search_results_shortcode');

/**
 * Register dynamic blocks for shared search-related renderers.
 *
 * Dynamic blocks avoid raw shortcode boxes in the Site Editor while reusing the
 * same server-side markup that powers the frontend shortcodes.
 */
function my_theme_register_combined_search_results_block() {
    register_block_type(
        'my-theme/combined-search-results',
        [
            'render_callback' => 'my_theme_combined_search_results_shortcode',
            'api_version'     => 2,
        ]
    );
}
add_action('init', 'my_theme_register_combined_search_results_block');

/**
 * Replace the legacy header search shortcode block in saved FSE header parts.
 *
 * Some sites have a database-backed Header template part. In the Site Editor,
 * the core Shortcode block displays an editable shortcode box instead of the
 * rendered header search form. This migration swaps only that shortcode block
 * for the equivalent dynamic block and keeps a one-time DB backup in post meta.
 *
 * @return void
 */
function my_theme_migrate_header_search_shortcode_to_block() {
    if (! is_admin() || wp_doing_ajax()) {
        return;
    }

    $header_parts = get_posts(
        [
            'name'             => 'header',
            'post_type'        => 'wp_template_part',
            'post_status'      => ['publish', 'draft'],
            'posts_per_page'   => -1,
            'suppress_filters' => false,
        ]
    );

    if (empty($header_parts)) {
        return;
    }

    foreach ($header_parts as $header_part) {
        if (! $header_part instanceof WP_Post || false === strpos((string) $header_part->post_content, '[theme_combined_search_form]')) {
            continue;
        }

        $updated_content = preg_replace(
            '/<!--\s+wp:shortcode\s+-->\s*\[theme_combined_search_form\]\s*<!--\s+\/wp:shortcode\s+-->/',
            '<!-- wp:my-theme/header-search /-->',
            (string) $header_part->post_content
        );

        if (! is_string($updated_content) || $updated_content === $header_part->post_content) {
            continue;
        }

        if ('' === (string) get_post_meta($header_part->ID, '_my_theme_header_shortcode_backup', true)) {
            add_post_meta($header_part->ID, '_my_theme_header_shortcode_backup', $header_part->post_content, true);
        }

        wp_update_post(
            [
                'ID'           => $header_part->ID,
                'post_content' => $updated_content,
            ]
        );
    }
}
add_action('admin_init', 'my_theme_migrate_header_search_shortcode_to_block');

/**
 * Return WooCommerce taxonomy query clauses that keep hidden products out of
 * public search previews.
 *
 * @return array
 */
function my_theme_search_get_product_visibility_tax_query() {
    if (! function_exists('wc_get_product_visibility_term_ids')) {
        return [];
    }

    $visibility_terms = wc_get_product_visibility_term_ids();
    $exclude_terms    = array_filter(
        [
            $visibility_terms['exclude-from-catalog'] ?? 0,
            $visibility_terms['exclude-from-search'] ?? 0,
        ]
    );

    if (empty($exclude_terms)) {
        return [];
    }

    return [
        [
            'taxonomy' => 'product_visibility',
            'field'    => 'term_taxonomy_id',
            'terms'    => array_map('absint', $exclude_terms),
            'operator' => 'NOT IN',
        ],
    ];
}

/**
 * Query matching products for the combined search preview.
 *
 * @param string $search Search phrase.
 * @return WP_Query
 */
function my_theme_search_get_products_query($search) {
    $args = [
        'post_type'              => 'product',
        'post_status'            => 'publish',
        'posts_per_page'         => 5,
        's'                      => $search,
        'orderby'                => 'date',
        'order'                  => 'DESC',
        'ignore_sticky_posts'    => true,
        'no_found_rows'          => false,
        'update_post_meta_cache' => true,
        'update_post_term_cache' => true,
    ];

    $visibility_tax_query = my_theme_search_get_product_visibility_tax_query();

    if (! empty($visibility_tax_query)) {
        $args['tax_query'] = $visibility_tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
    }

    return new WP_Query($args);
}

/**
 * Query matching articles for the combined search preview.
 *
 * @param string $search Search phrase.
 * @return WP_Query
 */
function my_theme_search_get_articles_query($search) {
    return new WP_Query(
        [
            'post_type'           => 'post',
            'post_status'         => 'publish',
            'posts_per_page'      => 3,
            's'                   => $search,
            'orderby'             => 'date',
            'order'               => 'DESC',
            'ignore_sticky_posts' => true,
            'no_found_rows'       => false,
        ]
    );
}

/**
 * Return the product listing URL for the current search phrase.
 *
 * @param string $search Search phrase.
 * @return string
 */
function my_theme_get_product_search_results_url($search) {
    $shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/products/');

    if (! $shop_url) {
        $shop_url = home_url('/products/');
    }

    return my_theme_get_search_url($shop_url, $search);
}

/**
 * Return the article listing URL for the current search phrase.
 *
 * @param string $search Search phrase.
 * @return string
 */
function my_theme_get_article_search_results_url($search) {
    $blog_page_id = (int) get_option('page_for_posts');
    $blog_url     = $blog_page_id ? get_permalink($blog_page_id) : home_url('/blog/');

    return my_theme_get_search_url($blog_url, $search);
}

/**
 * Return the shared white arrow SVG for search-results view-all links.
 *
 * @return string
 */
function my_theme_search_results_view_all_arrow_svg() {
    return <<<'SVG'
<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
  <path d="M9.65771 9.27835C9.24738 9.67196 9.24738 10.328 9.65771 10.7217L13.7477 14.645C14.158 15.0386 14.158 15.6947 13.7477 16.0883L13.4896 16.3359C13.1027 16.7071 12.4919 16.7071 12.105 16.3359L6.2523 10.7217C5.84197 10.328 5.84198 9.67196 6.2523 9.27835L12.105 3.66405C12.4919 3.29292 13.1027 3.29292 13.4896 3.66405L13.7477 3.91168C14.158 4.30529 14.158 4.96138 13.7477 5.35499L9.65771 9.27835Z" fill="white"/>
</svg>
SVG;
}

/**
 * Render a reusable search-results section header.
 *
 * @param string $title         Section title.
 * @param string $url           View-all URL.
 * @param bool   $show_viewall  Whether to render the view-all link.
 * @param string $heading_id    Optional heading ID.
 * @param string $viewall_label Optional view-all label.
 * @param string $viewall_class Optional extra view-all class.
 * @return string
 */
function my_theme_render_search_results_section_header($title, $url, $show_viewall, $heading_id = '', $viewall_label = '', $viewall_class = '') {
    $viewall_label = '' !== $viewall_label ? $viewall_label : __('مشاهده همه', 'my-theme');
    $viewall_class = trim('search-results-view-all ' . $viewall_class);

    ob_start();
    ?>
<div class="search-results-section__header">
    <h2 class="search-results-section__title" <?php echo '' !== $heading_id ? 'id="' . esc_attr($heading_id) . '"' : ''; ?>>
        <?php echo esc_html($title); ?>
    </h2>

    <?php if ($show_viewall) : ?>
        <a class="<?php echo esc_attr($viewall_class); ?>" href="<?php echo esc_url($url); ?>"><span><?php echo esc_html($viewall_label); ?></span><?php echo my_theme_search_results_view_all_arrow_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
    <?php endif; ?>
</div>
<?php
    return trim(ob_get_clean());
}

/**
 * Render the combined search zero-results state.
 *
 * @param string $search Search phrase.
 * @return string
 */
function my_theme_render_combined_search_empty_state($search) {
    $search          = trim((string) $search);
    $has_search      = '' !== $search;
    $quoted_search   = $has_search ? sprintf(_x('"%s"', 'search query in quotes', 'my-theme'), $search) : '';
    $guillemet_query = $has_search ? sprintf('«%s»', $search) : '';

    ob_start();
    ?>
<section class="search-results-empty-state" aria-labelledby="search-results-empty-title">
    <div class="search-results-empty-state__content">
        <h1 id="search-results-empty-title" class="search-results-empty-state__heading">
            <span class="search-results-empty-state__heading-label">
                <?php echo esc_html__('نتایج سرچ برای', 'my-theme'); ?>
            </span>

            <?php if ($has_search) : ?>
                <span class="search-results-empty-state__query">
                    <?php echo esc_html($quoted_search); ?>
                </span>
            <?php endif; ?>
        </h1>

        <p class="search-results-empty-state__message">
            <?php if ($has_search) : ?>
                <?php echo esc_html__('برای', 'my-theme'); ?>
                <span class="search-results-empty-state__message-query">
                    <?php echo esc_html($guillemet_query); ?>
                </span>
                <?php echo esc_html__('نتیجه‌ای پیدا نشد', 'my-theme'); ?>
            <?php else : ?>
                <?php echo esc_html__('نتیجه‌ای پیدا نشد', 'my-theme'); ?>
            <?php endif; ?>
        </p>

        <p class="search-results-empty-state__description">
            <?php echo esc_html__('برای پیدا کردن محصول یا مقاله مورد نظرتان، عبارت دیگری را جستجو کنید یا دسته‌بندی محصولات را بررسی کنید', 'my-theme'); ?>
        </p>

        <div class="search-results-empty-state__actions">
            <div class="search-results-empty-state__search">
                <?php
                echo my_theme_render_combined_search_form( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    [
                        'id'          => 'search-results-empty-search-input',
                        'extra_class' => 'search-results-empty-state__search-form',
                        'value'       => $search,
                    ]
                );
                ?>
            </div>

            <a class="search-results-view-all search-results-view-all--empty-products" href="<?php echo esc_url(my_theme_get_product_search_results_url('')); ?>">
                <span><?php echo esc_html__('مشاهده همه محصولات', 'my-theme'); ?></span>
                <?php echo my_theme_search_results_view_all_arrow_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </a>
        </div>
    </div>
</section>
<?php
    return trim(ob_get_clean());
}

/**
 * Format a WooCommerce price as a localized number without the currency label.
 *
 * @param float|string $price Price value.
 * @return string
 */
function my_theme_search_results_format_price_number($price) {
    $decimals = function_exists('wc_get_price_decimals') ? wc_get_price_decimals() : 0;
    $price    = (float) $price;

    return number_format_i18n($price, $decimals);
}

/**
 * Return pricing data for the Search Results product-card variant.
 *
 * @param WC_Product $product Product object.
 * @return array
 */
function my_theme_search_results_get_product_pricing($product) {
    $pricing = [
        'current'      => '',
        'regular'      => '',
        'discount'     => 0,
        'has_price'    => false,
        'has_discount' => false,
    ];

    if (! $product instanceof WC_Product) {
        return $pricing;
    }

    if ($product->is_type('variable')) {
        $regular_price = $product->get_variation_regular_price('min', true);
        $sale_price    = $product->get_variation_sale_price('min', true);
        $current_price = $product->get_variation_price('min', true);
    } else {
        $regular_price = $product->get_regular_price();
        $sale_price    = $product->get_sale_price();
        $current_price = $product->get_price();
    }

    if ('' === $current_price) {
        return $pricing;
    }

    $display_current = wc_get_price_to_display($product, ['price' => (float) $current_price]);

    $pricing['current']   = my_theme_search_results_format_price_number($display_current);
    $pricing['has_price'] = true;

    if (! $product->is_on_sale() || '' === $regular_price || '' === $sale_price) {
        return $pricing;
    }

    $regular_price = (float) $regular_price;
    $sale_price    = (float) $sale_price;

    if (0 >= $regular_price || $sale_price >= $regular_price) {
        return $pricing;
    }

    $display_regular = wc_get_price_to_display($product, ['price' => $regular_price]);

    $pricing['regular']      = my_theme_search_results_format_price_number($display_regular);
    $pricing['discount']     = (int) round((($regular_price - $sale_price) / $regular_price) * 100);
    $pricing['has_discount'] = 0 < $pricing['discount'];

    return $pricing;
}

/**
 * Render a Search Results-specific product-card variant.
 *
 * @param WC_Product $product Product object.
 * @return string
 */
function my_theme_render_search_result_product_card($product) {
    if (! $product instanceof WC_Product) {
        return '';
    }

    $permalink     = $product->get_permalink();
    $product_name  = $product->get_name();
    $is_in_stock   = $product->is_in_stock();
    $pricing       = my_theme_search_results_get_product_pricing($product);
    $show_discount = $is_in_stock && ! empty($pricing['has_discount']);
    $show_price    = $is_in_stock && ! empty($pricing['has_price']);

    ob_start();
    ?>
<article class="search-result-product-card" dir="rtl">
    <div class="search-result-product-card__media">
        <?php if ($show_discount) : ?>
            <span class="search-result-product-card__special-badge" aria-hidden="true"><span class="search-result-product-card__special-badge-text"><?php echo esc_html__('تخفیف ویژه', 'my-theme'); ?></span></span>
        <?php endif; ?>

        <a class="search-result-product-card__image-link" href="<?php echo esc_url($permalink); ?>" aria-label="<?php echo esc_attr($product_name); ?>">
            <?php echo wp_kses_post($product->get_image('woocommerce_thumbnail')); ?>
        </a>
    </div>

    <h3 class="search-result-product-card__title">
        <a href="<?php echo esc_url($permalink); ?>">
            <?php echo esc_html($product_name); ?>
        </a>
    </h3>

    <div class="search-result-product-card__pricing">
        <?php if ($show_price) : ?>
            <?php if ($show_discount) : ?>
                <div class="search-result-product-card__old-price-row">
                    <span class="search-result-product-card__discount" aria-label="<?php echo esc_attr(sprintf(__('٪%s تخفیف', 'my-theme'), number_format_i18n((int) $pricing['discount']))); ?>"><span class="search-result-product-card__discount-text"><?php echo esc_html('٪' . number_format_i18n((int) $pricing['discount'])); ?></span></span>

                    <del class="search-result-product-card__old-price">
                        <?php echo esc_html($pricing['regular']); ?>
                    </del>
                </div>
            <?php endif; ?>

            <div class="search-result-product-card__current-price-row">
                <span class="search-result-product-card__currency">
                    <?php echo esc_html__('تومان', 'my-theme'); ?>
                </span>
                <span class="search-result-product-card__current-price">
                    <?php echo esc_html($pricing['current']); ?>
                </span>
            </div>
        <?php else : ?>
            <span class="search-result-product-card__stock-status">
                <?php echo esc_html__('ناموجود', 'my-theme'); ?>
            </span>
        <?php endif; ?>
    </div>

    <div class="search-result-product-card__actions">
        <a class="search-result-product-card__button" href="<?php echo esc_url($permalink); ?>"><span class="search-result-product-card__button-text"><?php echo esc_html__('مشاهده محصول', 'my-theme'); ?></span></a>
    </div>
</article>
<?php
    return trim(ob_get_clean());
}

/**
 * Render the product preview section.
 *
 * @param WP_Query $query      Product query.
 * @param string   $search     Search phrase.
 * @param int      $total      Total product matches.
 * @return string
 */
function my_theme_render_search_results_products_section($query, $search, $total) {
    ob_start();
    ?>
<section class="search-results-section search-results-products" aria-labelledby="search-results-products-title">
    <?php
    echo my_theme_render_search_results_section_header( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        __('محصولات', 'my-theme'),
        my_theme_get_product_search_results_url($search),
        0 < $total,
        'search-results-products-title',
        __('مشاهده همه', 'my-theme'),
        'search-results-view-all--products search-results-view-all--products-header'
    );
    ?>

    <?php if ($query instanceof WP_Query && $query->have_posts()) : ?>
        <div class="search-results-products__grid search-results-products__grid--desktop" dir="rtl">
            <?php
            foreach ($query->posts as $post) {
                $product_id = $post instanceof WP_Post ? (int) $post->ID : absint($post);
                $product    = function_exists('wc_get_product') ? wc_get_product($product_id) : null;

                if (! $product instanceof WC_Product) {
                    continue;
                }

                echo my_theme_render_search_result_product_card($product); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
            ?>
        </div>

        <?php if (function_exists('my_theme_render_green_product_card')) : ?>
            <div class="search-results-products__mobile product-category-archive" dir="rtl">
                <div class="product-category-results__grid greenProductSwiper">
                    <?php
                    foreach ($query->posts as $post) {
                        $product_id = $post instanceof WP_Post ? (int) $post->ID : absint($post);
                        $product    = function_exists('wc_get_product') ? wc_get_product($product_id) : null;

                        if (! $product instanceof WC_Product) {
                            continue;
                        }

                        echo my_theme_render_green_product_card( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            $product,
                            [
                                'show_promo_header'      => false,
                                'show_sale_image_badge'  => true,
                                'show_sale_footer_badge' => true,
                                'show_stock_status'      => true,
                                'extra_class'            => 'search-result-mobile-product-card',
                                'show_view_button'       => true,
                            ]
                        );
                    }
                    ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (0 < $total) : ?>
            <div class="search-results-products__view-all">
                <a class="search-results-view-all search-results-view-all--products search-results-view-all--products-mobile" href="<?php echo esc_url(my_theme_get_product_search_results_url($search)); ?>">
                    <span><?php echo esc_html__('مشاهده همه محصولات مرتبط', 'my-theme'); ?></span>
                    <?php echo my_theme_search_results_view_all_arrow_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </a>
            </div>
        <?php endif; ?>
    <?php else : ?>
        <p class="search-results-empty">
            <?php echo esc_html__('محصولی مطابق جستجوی شما پیدا نشد.', 'my-theme'); ?>
        </p>
    <?php endif; ?>
</section>
<?php
    return trim(ob_get_clean());
}

/**
 * Render the combined search-results page body.
 *
 * @return string
 */
function my_theme_render_combined_search_results_page() {
    $search         = my_theme_get_current_search_phrase();
    $products_query = '' !== $search ? my_theme_search_get_products_query($search) : new WP_Query();
    $articles_query = '' !== $search ? my_theme_search_get_articles_query($search) : new WP_Query();
    $product_total  = $products_query instanceof WP_Query ? (int) $products_query->found_posts : 0;
    $article_total  = $articles_query instanceof WP_Query ? (int) $articles_query->found_posts : 0;
    $has_results    = 0 < ($product_total + $article_total);

    ob_start();
    ?>
<main class="search-results-page" dir="rtl">
    <div class="search-results-page__inner">
        <?php if (! $has_results) : ?>
            <?php echo my_theme_render_combined_search_empty_state($search); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <?php else : ?>
        <header class="search-results-page__header">
            <h1 class="search-results-page__heading">
                <span class="search-results-page__heading-label">
                    <?php echo esc_html__('نتایج سرچ برای', 'my-theme'); ?>
                </span>
                <span class="search-results-page__heading-query">
                    <?php echo esc_html(sprintf(_x('"%s"', 'search query in quotes', 'my-theme'), $search)); ?>
                </span>
            </h1>

            <?php if ('' !== $search) : ?>
                <p class="search-results-page__count">
                    <?php
                    echo esc_html(
                        sprintf(
                            /* translators: 1: product count, 2: article count. */
                            __('%1$s محصول و %2$s مقاله مرتبط پیدا شد', 'my-theme'),
                            number_format_i18n($product_total),
                            number_format_i18n($article_total)
                        )
                    );
                    ?>
                </p>
            <?php endif; ?>
        </header>

        <?php if ('' === $search) : ?>
            <p class="search-results-empty search-results-empty--page">
                <?php echo esc_html__('عبارت جستجو را وارد کنید تا نتایج محصولات و مقالات نمایش داده شود.', 'my-theme'); ?>
            </p>
        <?php else : ?>
            <?php if (! $has_results) : ?>
                <p class="search-results-empty search-results-empty--page">
                    <?php
                    echo esc_html(
                        sprintf(
                            /* translators: %s: search query. */
                            __('نتیجه‌ای برای "%s" پیدا نشد.', 'my-theme'),
                            $search
                        )
                    );
                    ?>
                </p>
            <?php endif; ?>

            <?php if (0 < $product_total) : ?>
                <?php echo my_theme_render_search_results_products_section($products_query, $search, $product_total); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php endif; ?>

            <?php if (0 < $article_total) : ?>
            <section class="search-results-section search-results-articles" aria-labelledby="search-results-articles-title">
                <?php
                if (function_exists('my_theme_render_latest_articles_section')) {
                    echo my_theme_render_latest_articles_section( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        [
                            's'                   => $search,
                            'posts_per_page'      => 3,
                            'post_type'           => 'post',
                            'post_status'         => 'publish',
                            'ignore_sticky_posts' => true,
                            'orderby'             => 'date',
                            'order'               => 'DESC',
                            'no_found_rows'       => false,
                        ],
                        [
                            'title'        => __('مقالات', 'my-theme'),
                            'view_all_url' => my_theme_get_article_search_results_url($search),
                            'show_view_all'=> true,
                            'heading_id'   => 'search-results-articles-title',
                            'empty_text'   => __('مقاله‌ای مطابق جستجوی شما پیدا نشد.', 'my-theme'),
                        ]
                    );
                }
                ?>

                <?php if (0 < $article_total) : ?>
                    <div class="search-results-articles__view-all">
                        <a class="search-results-view-all search-results-view-all--articles" href="<?php echo esc_url(my_theme_get_article_search_results_url($search)); ?>">
                            <span><?php echo esc_html__('مشاهده همه ی مقالات', 'my-theme'); ?></span>
                            <?php echo my_theme_search_results_view_all_arrow_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </a>
                    </div>
                <?php endif; ?>
            </section>
            <?php endif; ?>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
<?php
    wp_reset_postdata();

    return trim(ob_get_clean());
}

/**
 * Enqueue the combined search-results stylesheet only for the custom route.
 *
 * @return void
 */
function my_theme_enqueue_search_results_style() {
    if (! my_theme_is_combined_search_results_request()) {
        return;
    }

    $stylesheet_path = get_theme_file_path('assets/css/search-results.css');

    if (! file_exists($stylesheet_path)) {
        return;
    }

    if (function_exists('my_theme_register_product_archive_style')) {
        my_theme_register_product_archive_style();
        wp_enqueue_style('my-theme-product-archive');
    }

    wp_enqueue_style(
        'my-theme-search-results',
        get_theme_file_uri('assets/css/search-results.css'),
        ['theme-style', 'theme-header', 'theme-home', 'my-theme-product-archive'],
        filemtime($stylesheet_path)
    );
}
add_action('wp_enqueue_scripts', 'my_theme_enqueue_search_results_style', 25);

/**
 * Add a scoped body class for the combined search page.
 *
 * @param array $classes Body classes.
 * @return array
 */
function my_theme_add_combined_search_results_body_class($classes) {
    if (my_theme_is_combined_search_results_request()) {
        $classes[] = 'combined-search-results';
    }

    return $classes;
}
add_filter('body_class', 'my_theme_add_combined_search_results_body_class');

/**
 * Allow the combined search-results page to render the theme template even when
 * WooCommerce Coming Soon mode is active.
 *
 * @param bool $exclude Whether to exclude the current request.
 * @return bool
 */
function my_theme_exclude_combined_search_from_coming_soon($exclude) {
    if (my_theme_is_combined_search_results_request()) {
        return true;
    }

    return $exclude;
}
add_filter('woocommerce_coming_soon_exclude', 'my_theme_exclude_combined_search_from_coming_soon');

/**
 * Apply the canonical `search` parameter to the product archive main query.
 *
 * @param WP_Query $query Query object.
 * @return void
 */
function my_theme_product_archive_apply_canonical_search($query) {
    if (
        is_admin()
        || ! $query instanceof WP_Query
        || ! $query->is_main_query()
        || ! function_exists('my_theme_is_product_archive_context')
        || ! my_theme_is_product_archive_context()
    ) {
        return;
    }

    $search = my_theme_get_current_search_phrase();

    if ('' === $search) {
        return;
    }

    $query->set('s', $search);
}
add_action('pre_get_posts', 'my_theme_product_archive_apply_canonical_search', 24);

/**
 * Apply the canonical `search` parameter to the blog listing page.
 *
 * @param WP_Query $query Query object.
 * @return void
 */
function my_theme_blog_listing_apply_canonical_search($query) {
    if (
        is_admin()
        || ! $query instanceof WP_Query
        || ! $query->is_main_query()
        || ! $query->is_home()
    ) {
        return;
    }

    $search = my_theme_get_current_search_phrase();

    if ('' === $search) {
        return;
    }

    $query->set('s', $search);
    $query->set('post_type', 'post');
    $query->set('post_status', 'publish');
}
add_action('pre_get_posts', 'my_theme_blog_listing_apply_canonical_search', 24);
