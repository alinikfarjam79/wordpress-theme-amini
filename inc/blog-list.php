<?php
/**
 * Blog list page hero.
 *
 * @package MyTheme
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Create the required Blog List page once when the theme is installed/activated.
 *
 * The block theme template hierarchy will automatically use templates/page-blog.html
 * for a published page with the "blog" slug, so this intentionally creates only
 * the WordPress page record and does not duplicate template markup in the DB.
 *
 * @return void
 */
function my_theme_ensure_blog_list_page() {
    if (wp_installing()) {
        return;
    }

    $existing_page = get_page_by_path('blog', OBJECT, 'page');

    if ($existing_page instanceof WP_Post) {
        update_option('my_theme_blog_list_page_id', (int) $existing_page->ID, false);
        return;
    }

    if ((int) get_option('my_theme_blog_list_page_id') > 0) {
        return;
    }

    $page_id = wp_insert_post(
        [
            'post_author'  => get_current_user_id() ?: 1,
            'post_content' => '',
            'post_name'    => 'blog',
            'post_status'  => 'publish',
            'post_title'   => __('مجله رنگ و ابزار', 'my-theme'),
            'post_type'    => 'page',
        ],
        true
    );

    if (! is_wp_error($page_id) && $page_id) {
        update_option('my_theme_blog_list_page_id', (int) $page_id, false);
    }
}
add_action('after_switch_theme', 'my_theme_ensure_blog_list_page');
add_action('admin_init', 'my_theme_ensure_blog_list_page');

/**
 * Return the public Blog List URL.
 *
 * @return string
 */
function my_theme_get_blog_list_url() {
    $blog_page = get_page_by_path('blog');

    if ($blog_page instanceof WP_Post) {
        return get_permalink($blog_page);
    }

    $blog_page_id = (int) get_option('page_for_posts');

    if ($blog_page_id) {
        return get_permalink($blog_page_id);
    }

    return home_url('/blog/');
}

/**
 * Determine whether the current request is the Blog List page.
 *
 * @return bool
 */
function my_theme_is_blog_list_request() {
    if (is_home()) {
        return true;
    }

    if (is_page('blog')) {
        return true;
    }

    return false;
}

/**
 * Build the Blog List article query args.
 *
 * This helper is intentionally scoped to the Blog List page and always queries
 * regular WordPress posts only. WooCommerce products must never be included in
 * Blog List search results.
 *
 * @param array<string,mixed> $overrides Optional query arg overrides.
 * @return array<string,mixed>
 */
function my_theme_get_blog_list_article_query_args($overrides = []) {
    $search = function_exists('my_theme_get_current_search_phrase') ? my_theme_get_current_search_phrase() : '';
    $category_slug = my_theme_get_blog_list_current_category_query_slug();

    $args = [
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'ignore_sticky_posts' => true,
        'orderby'             => 'date',
        'order'               => 'DESC',
    ];

    if ('' !== $search) {
        $args['s'] = $search;
    }

    if ('' !== my_theme_get_blog_list_current_category_value()) {
        if ('' !== $category_slug) {
            $args['category_name'] = $category_slug;
        } else {
            $args['post__in'] = [0];
        }
    }

    return array_merge($args, $overrides);
}

/**
 * Return the Blog List article query.
 *
 * @param array<string,mixed> $overrides Optional query arg overrides.
 * @return WP_Query
 */
function my_theme_get_blog_list_article_query($overrides = []) {
    return new WP_Query(my_theme_get_blog_list_article_query_args($overrides));
}

/**
 * Return the current Blog List pagination page.
 *
 * @return int
 */
function my_theme_get_blog_list_current_page() {
    return max(
        1,
        (int) get_query_var('paged'),
        (int) get_query_var('page'),
        isset($_GET['paged']) ? (int) $_GET['paged'] : 0 // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    );
}

/**
 * Get the current Blog List category filter value from the URL.
 *
 * @return string
 */
function my_theme_get_blog_list_current_category_value() {
    if (empty($_GET['category'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return '';
    }

    return trim(sanitize_text_field(wp_unslash((string) $_GET['category']))); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

/**
 * Resolve the current URL category value to an actual WordPress category slug.
 *
 * @return string
 */
function my_theme_get_blog_list_current_category_query_slug() {
    $category = my_theme_get_blog_list_current_category_value();

    if ('' === $category) {
        return '';
    }

    $term = get_category_by_slug(sanitize_title($category));

    if (! $term) {
        $term = get_term_by('name', $category, 'category');
    }

    if (! $term && function_exists('my_theme_get_blog_list_filter_options')) {
        foreach (my_theme_get_blog_list_filter_options() as $option) {
            if (
                'category' === ($option['type'] ?? '')
                && $category === ($option['value'] ?? '')
            ) {
                $term = get_category_by_slug(sanitize_title((string) ($option['label'] ?? '')));
                break;
            }
        }
    }

    return $term instanceof WP_Term ? (string) $term->slug : '';
}

/**
 * Get Blog List sorting/filter options.
 *
 * The site currently only has the default post category, so these requested
 * slugs become active as soon as matching article categories are created.
 *
 * @return array<int,array<string,string>>
 */
function my_theme_get_blog_list_filter_options() {
    return [
        [
            'type'  => 'sort',
            'slug'  => 'latest',
            'label' => __('جدیدترین', 'my-theme'),
        ],
        [
            'type'  => 'category',
            'slug'  => 'paint-training',
            'value' => 'اموزش رنگ سازی',
            'label' => __('آموزش رنگ سازی', 'my-theme'),
        ],
        [
            'type'  => 'category',
            'slug'  => 'color-selection',
            'value' => 'انتخاب رنگ',
            'label' => __('انتخاب رنگ', 'my-theme'),
        ],
        [
            'type'  => 'category',
            'slug'  => 'tools-equipment',
            'value' => 'ابزار و تجهیزات',
            'label' => __('ابزار و تجهیزات', 'my-theme'),
        ],
        [
            'type'  => 'category',
            'slug'  => 'maintenance-repair',
            'value' => 'نگه داری و تعمیرات',
            'label' => __('نگه داری و تعمیرات', 'my-theme'),
        ],
        [
            'type'  => 'category',
            'slug'  => 'buying-guide',
            'value' => 'راهنمای خرید',
            'label' => __('راهنمای خرید', 'my-theme'),
        ],
    ];
}

/**
 * Build a Blog List filter URL while preserving search state.
 *
 * @param array<string,string> $option Filter option.
 * @return string
 */
function my_theme_get_blog_list_filter_url($option) {
    $url = my_theme_get_blog_list_url();
    $search = function_exists('my_theme_get_current_search_phrase') ? my_theme_get_current_search_phrase() : '';

    $args = [];

    if ('' !== $search) {
        $args['search'] = $search;
    }

    if ('category' === ($option['type'] ?? '')) {
        $args['category'] = (string) ($option['value'] ?? $option['label'] ?? '');
    } else {
        $args['sort'] = 'latest';
    }

    return add_query_arg($args, $url);
}

/**
 * Return safe Blog List state query args for pagination URLs.
 *
 * @return array<string,string>
 */
function my_theme_get_blog_list_state_query_args() {
    $args = [];
    $search = function_exists('my_theme_get_current_search_phrase') ? my_theme_get_current_search_phrase() : '';
    $category = my_theme_get_blog_list_current_category_value();

    if ('' !== $search) {
        $args['search'] = $search;
    }

    if ('' !== $category) {
        $args['category'] = $category;
    }

    if (! empty($_GET['sort'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $sort = sanitize_key(wp_unslash((string) $_GET['sort'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        if ('latest' === $sort) {
            $args['sort'] = $sort;
        }
    }

    return $args;
}

/**
 * Render Blog List pagination with Product Category pagination visual classes.
 *
 * @param WP_Query $query Blog article query.
 * @return string
 */
function my_theme_render_blog_list_pagination($query) {
    if (! $query instanceof WP_Query || (int) $query->max_num_pages <= 1) {
        return '';
    }

    $current = my_theme_get_blog_list_current_page();
    $base    = add_query_arg(array_merge(my_theme_get_blog_list_state_query_args(), ['paged' => '999999999']), my_theme_get_blog_list_url());
    $base    = str_replace('999999999', '%#%', $base);

    $prev_text = '<span class="screen-reader-text">' . esc_html__('صفحه قبلی', 'my-theme') . '</span>';
    $next_text = '<span class="screen-reader-text">' . esc_html__('صفحه بعدی', 'my-theme') . '</span>';

    if (function_exists('my_theme_product_category_pagination_arrow_svg')) {
        $prev_text .= my_theme_product_category_pagination_arrow_svg('prev');
        $next_text .= my_theme_product_category_pagination_arrow_svg('next');
    }

    $links = paginate_links(
        [
            'base'      => $base,
            'format'    => '',
            'total'     => (int) $query->max_num_pages,
            'current'   => $current,
            'type'      => 'list',
            'prev_text' => $prev_text,
            'next_text' => $next_text,
        ]
    );

    if (! is_string($links) || '' === $links) {
        return '';
    }

    $links = str_replace('class="prev page-numbers"', 'class="prev page-numbers product-category-results__pagination-link product-category-results__pagination-link--prev"', $links);
    $links = str_replace('class="next page-numbers"', 'class="next page-numbers product-category-results__pagination-link product-category-results__pagination-link--next"', $links);

    return '<nav class="blog-list-pagination product-category-results__pagination" aria-label="' . esc_attr__('صفحه‌بندی مقاله‌ها', 'my-theme') . '">' . $links . '</nav>';
}

/**
 * Render the Blog List article grid and pagination.
 *
 * @param array<string,mixed> $attributes Block attributes.
 * @return string
 */
function my_theme_render_blog_list_results($attributes = []) {
    $attributes = wp_parse_args(
        (array) $attributes,
        [
            'emptyText'    => __('مقاله‌ای پیدا نشد.', 'my-theme'),
            'postsPerPage' => 6,
        ]
    );

    $paged          = my_theme_get_blog_list_current_page();
    $posts_per_page = max(1, (int) $attributes['postsPerPage']);
    $blog_query     = my_theme_get_blog_list_article_query(
        [
            'posts_per_page' => $posts_per_page,
            'paged'          => $paged,
        ]
    );

    ob_start();
    ?>
<div class="blog-list-articles">
    <?php if ($blog_query->have_posts()) : ?>
        <div class="blog-list-articles__grid">
            <?php while ($blog_query->have_posts()) : ?>
                <?php $blog_query->the_post(); ?>
                <?php
                if (function_exists('my_theme_render_article_card')) {
                    echo my_theme_render_article_card(get_the_ID()); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                }
                ?>
            <?php endwhile; ?>
        </div>

        <?php echo my_theme_render_blog_list_pagination($blog_query); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php else : ?>
        <p class="blog-list-articles__empty"><?php echo esc_html((string) $attributes['emptyText']); ?></p>
    <?php endif; ?>
</div>
<?php
    wp_reset_postdata();

    return trim(ob_get_clean());
}

/**
 * Shortcode fallback for Blog List results.
 *
 * @param array<string,mixed> $atts Shortcode attributes.
 * @return string
 */
function my_theme_blog_list_results_shortcode($atts = []) {
    return my_theme_render_blog_list_results((array) $atts);
}
add_shortcode('theme_blog_list_results', 'my_theme_blog_list_results_shortcode');

/**
 * Render the Blog List sorting toolbar and dynamic result count.
 *
 * @param array<string,string> $attributes Block attributes.
 * @return string
 */
function my_theme_render_blog_list_toolbar($attributes = []) {
    $attributes = wp_parse_args(
        (array) $attributes,
        [
            'sortingLabel' => __('مرتب سازی:', 'my-theme'),
            'countSuffix'  => __('مقاله', 'my-theme'),
        ]
    );

    $current_category = my_theme_get_blog_list_current_category_value();
    $current_sort     = empty($_GET['sort']) ? 'latest' : sanitize_key(wp_unslash((string) $_GET['sort'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $count_query      = my_theme_get_blog_list_article_query(
        [
            'fields'         => 'ids',
            'posts_per_page' => 1,
            'no_found_rows'  => false,
        ]
    );
    $count            = $count_query instanceof WP_Query ? (int) $count_query->found_posts : 0;
    $count_text       = sprintf('%1$s %2$s', number_format_i18n($count), (string) $attributes['countSuffix']);
    $options          = my_theme_get_blog_list_filter_options();

    wp_reset_postdata();

    ob_start();
    ?>
<div class="blog-list-toolbar">
    <div class="blog-list-sorting blog-list-toolbar__badges">
        <div class="blog-list-sorting__label blog-list-toolbar__sort-badge blog-list-toolbar__sort-label">
            <svg class="blog-list-sorting__icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                <path d="M21 19H3M9 12H21M15 5H21" stroke="#1A1E1B" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <span class="blog-list-sorting__label-text"><?php echo esc_html(rtrim((string) $attributes['sortingLabel'], ':：')); ?></span>
        </div>

        <div class="blog-list-sorting__options" role="list" aria-label="<?php echo esc_attr__('فیلتر مقاله‌ها', 'my-theme'); ?>">
            <?php foreach ($options as $option) : ?>
                <?php
                $is_active = 'category' === $option['type']
                    ? $current_category === ($option['value'] ?? $option['label'] ?? '')
                    : '' === $current_category && 'latest' === $current_sort;
                ?>
                <a
                    class="blog-list-sorting__option blog-list-toolbar__badge<?php echo $is_active ? ' is-active' : ''; ?>"
                    href="<?php echo esc_url(my_theme_get_blog_list_filter_url($option)); ?>"
                    role="listitem"
                    <?php echo $is_active ? 'aria-current="page"' : ''; ?>
                >
                    <?php echo esc_html($option['label']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <p class="blog-list-toolbar__count">
        <span class="blog-list-toolbar__count-label"><?php echo esc_html__('تعداد مقاله‌ها', 'my-theme'); ?></span>
        <span class="blog-list-toolbar__count-value"><?php echo esc_html($count_text); ?></span>
    </p>
</div>
<?php
    return trim(ob_get_clean());
}

/**
 * Shortcode fallback for the Blog List toolbar.
 *
 * @param array<string,string> $atts Shortcode attributes.
 * @return string
 */
function my_theme_blog_list_toolbar_shortcode($atts = []) {
    return my_theme_render_blog_list_toolbar((array) $atts);
}
add_shortcode('theme_blog_list_toolbar', 'my_theme_blog_list_toolbar_shortcode');

/**
 * Render the Blog List hero/search section.
 *
 * @return string
 */
function my_theme_render_blog_list_hero() {
    $search_value = function_exists('my_theme_get_current_search_phrase') ? my_theme_get_current_search_phrase() : '';
    $blog_url     = my_theme_get_blog_list_url();

    ob_start();
    ?>
<section class="blog-list-hero" dir="rtl" aria-labelledby="blog-list-hero-title">
    <div class="blog-list-hero__inner">
        <p class="blog-list-hero__eyebrow"><?php echo esc_html__('مقاله‌ها', 'my-theme'); ?></p>

        <h1 id="blog-list-hero-title" class="blog-list-hero__title">
            <?php echo esc_html__('مجله رنگ و ابزار', 'my-theme'); ?>
        </h1>

        <p class="blog-list-hero__description">
            <?php echo esc_html__('از انتخاب رنگ مناسب تا اجرای حرفه‌ای، نکات کاربردی و راهنمای تخصصی برای پروژه‌های شما', 'my-theme'); ?>
        </p>

        <?php
        if (function_exists('my_theme_render_combined_search_form')) {
            echo my_theme_render_combined_search_form( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                [
                    'id'          => 'blog-list-hero-search-input',
                    'extra_class' => 'site-search-form--blog-list',
                    'placeholder' => __('جستجو در بین مقاله‌ها', 'my-theme'),
                    'value'       => $search_value,
                    'width'       => '350px',
                    'height'      => '48px',
                    'action'      => $blog_url,
                    'aria_label'  => __('جستجوی مقاله‌ها', 'my-theme'),
                ]
            );
        }
        ?>
    </div>
</section>
<?php
    return trim(ob_get_clean());
}

/**
 * Shortcode fallback for the Blog List hero.
 *
 * @return string
 */
function my_theme_blog_list_hero_shortcode() {
    return my_theme_render_blog_list_hero();
}
add_shortcode('theme_blog_list_hero', 'my_theme_blog_list_hero_shortcode');

/**
 * Render only the Blog List article search form.
 *
 * @param array<string,string> $atts Shortcode attributes.
 * @return string
 */
function my_theme_blog_list_search_shortcode($atts = []) {
    $atts = shortcode_atts(
        [
            'placeholder' => __('جستجو در بین مقاله‌ها', 'my-theme'),
        ],
        (array) $atts,
        'theme_blog_list_search'
    );

    if (! function_exists('my_theme_render_combined_search_form')) {
        return '';
    }

    return my_theme_render_combined_search_form(
        [
            'id'          => 'blog-list-hero-search-input',
            'extra_class' => 'site-search-form--blog-list',
            'placeholder' => (string) $atts['placeholder'],
            'value'       => function_exists('my_theme_get_current_search_phrase') ? my_theme_get_current_search_phrase() : '',
            'width'       => '350px',
            'height'      => '48px',
            'action'      => my_theme_get_blog_list_url(),
            'aria_label'  => __('جستجوی مقاله‌ها', 'my-theme'),
        ]
    );
}
add_shortcode('theme_blog_list_search', 'my_theme_blog_list_search_shortcode');

/**
 * Register the dynamic block used by Blog List templates.
 *
 * @return void
 */
function my_theme_register_blog_list_hero_block() {
    if (! function_exists('register_block_type')) {
        return;
    }

    register_block_type(
        'my-theme/blog-list-hero',
        [
            'render_callback' => 'my_theme_blog_list_hero_shortcode',
            'api_version'     => 2,
        ]
    );

    register_block_type(
        'my-theme/blog-list-toolbar',
        [
            'render_callback' => 'my_theme_render_blog_list_toolbar',
            'api_version'     => 2,
            'attributes'      => [
                'sortingLabel' => [
                    'type'    => 'string',
                    'default' => __('مرتب سازی:', 'my-theme'),
                ],
                'countSuffix'  => [
                    'type'    => 'string',
                    'default' => __('مقاله', 'my-theme'),
                ],
            ],
        ]
    );

    register_block_type(
        'my-theme/blog-list-results',
        [
            'render_callback' => 'my_theme_render_blog_list_results',
            'api_version'     => 2,
            'attributes'      => [
                'emptyText'    => [
                    'type'    => 'string',
                    'default' => __('مقاله‌ای پیدا نشد.', 'my-theme'),
                ],
                'postsPerPage' => [
                    'type'    => 'number',
                    'default' => 6,
                ],
            ],
        ]
    );
}
add_action('init', 'my_theme_register_blog_list_hero_block');

/**
 * Enqueue Blog List styles only on the Blog List route.
 *
 * @return void
 */
function my_theme_enqueue_blog_list_styles() {
    if (! my_theme_is_blog_list_request()) {
        return;
    }

    $stylesheet_path = get_theme_file_path('assets/css/blog-list.css');

    if (! file_exists($stylesheet_path)) {
        return;
    }

    wp_enqueue_style(
        'my-theme-blog-list',
        get_theme_file_uri('assets/css/blog-list.css'),
        ['theme-style', 'theme-header', 'theme-footer'],
        filemtime($stylesheet_path)
    );
}
add_action('wp_enqueue_scripts', 'my_theme_enqueue_blog_list_styles', 25);
