<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Register the shared single-product stylesheet handle.
 */
function my_theme_register_single_product_style() {
    $stylesheet_path = get_theme_file_path('assets/css/single-product.css');

    if (! file_exists($stylesheet_path) || wp_style_is('my-theme-single-product', 'registered')) {
        return;
    }

    wp_register_style(
        'my-theme-single-product',
        get_theme_file_uri('assets/css/single-product.css'),
        [],
        filemtime($stylesheet_path)
    );
}

/**
 * Register the WooCommerce product archive stylesheet handle.
 *
 * @return void
 */
function my_theme_register_product_archive_style() {
    $stylesheet_path = get_theme_file_path('assets/css/product-archive.css');

    if (! file_exists($stylesheet_path) || wp_style_is('my-theme-product-archive', 'registered')) {
        return;
    }

    wp_register_style(
        'my-theme-product-archive',
        get_theme_file_uri('assets/css/product-archive.css'),
        [],
        filemtime($stylesheet_path)
    );
}

/**
 * Determine whether the current frontend request is a WooCommerce product archive.
 *
 * @return bool
 */
function my_theme_is_product_archive_context() {
    if (! function_exists('is_shop')) {
        return false;
    }

    return is_shop()
        || is_post_type_archive('product')
        || (function_exists('is_product_taxonomy') && is_product_taxonomy());
}

/**
 * Load single-product CSS only on WooCommerce single product frontend requests.
 *
 * @return void
 */
function my_theme_enqueue_single_product_style() {
    if (is_admin() || ! function_exists('is_product') || ! is_product()) {
        return;
    }

    my_theme_register_single_product_style();
    wp_enqueue_style('my-theme-single-product');
}
add_action('wp_enqueue_scripts', 'my_theme_enqueue_single_product_style', 20);

/**
 * Load product archive CSS only on WooCommerce archive frontend requests.
 *
 * @return void
 */
function my_theme_enqueue_product_archive_style() {
    if (is_admin() || ! my_theme_is_product_archive_context()) {
        return;
    }

    my_theme_register_product_archive_style();
    wp_enqueue_style('my-theme-product-archive');
}
add_action('wp_enqueue_scripts', 'my_theme_enqueue_product_archive_style', 20);

/**
 * Load product archive interactions only on WooCommerce archive frontend requests.
 *
 * @return void
 */
function my_theme_enqueue_product_archive_script() {
    if (is_admin() || ! my_theme_is_product_archive_context()) {
        return;
    }

    $script_path = get_theme_file_path('assets/js/product-archive.js');

    if (! file_exists($script_path)) {
        return;
    }

    wp_enqueue_script(
        'my-theme-product-archive-script',
        get_theme_file_uri('assets/js/product-archive.js'),
        ['my-theme-product-category-filter-card'],
        filemtime($script_path),
        true
    );

    wp_localize_script(
        'my-theme-product-archive-script',
        'myThemeProductArchive',
        [
            'i18n' => [
                'readMore' => __('بیشتر', 'my-theme'),
                'readLess' => __('کمتر', 'my-theme'),
            ],
        ]
    );
}
add_action('wp_enqueue_scripts', 'my_theme_enqueue_product_archive_script', 30);

/**
 * Register all custom Gutenberg blocks from nested block directories.
 *
 * @return void
 */
function my_theme_register_custom_blocks() {
    my_theme_register_single_product_style();
    my_theme_register_product_archive_style();

    $blocks_path = get_theme_file_path('blocks');

    if (! is_dir($blocks_path)) {
        return;
    }

    $directory = new RecursiveDirectoryIterator(
        $blocks_path,
        FilesystemIterator::SKIP_DOTS
    );
    $iterator = new RecursiveIteratorIterator($directory);
    $registered_block_names = [];

    foreach ($iterator as $file) {
        if ('block.json' !== $file->getFilename()) {
            continue;
        }

        $metadata = json_decode((string) file_get_contents($file->getPathname()), true);

        if (! is_array($metadata) || empty($metadata['name'])) {
            continue;
        }

        if (isset($registered_block_names[$metadata['name']])) {
            continue;
        }

        register_block_type($file->getPath());
        $registered_block_names[$metadata['name']] = true;
    }
}
add_action('init', 'my_theme_register_custom_blocks', 5);

/**
 * Add a shared design attribute contract to every custom single-product block.
 * Empty defaults deliberately preserve the existing frontend design.
 */
function my_theme_single_product_block_design_attributes($args, $block_type) {
    if ('theme/single-product-content' !== $block_type && ! str_starts_with($block_type, 'theme/product-')) {
        return $args;
    }

    $container_blocks = ['theme/single-product-content','theme/product-main','theme/product-information','theme/product-overview','theme/product-actions','theme/product-meta','theme/product-purchase','theme/product-purchase-info','theme/product-purchase-actions','theme/product-benefits'];
    $is_container = in_array($block_type, $container_blocks, true);
    $common = [
        'designTextColor'       => ['type' => 'string', 'default' => ''],
        'designBackgroundColor' => ['type' => 'string', 'default' => ''],
        'designFontSize'        => ['type' => 'number'],
        'designFontWeight'      => ['type' => 'string', 'default' => ''],
        'designLineHeight'      => ['type' => 'number'],
        'designMarginTop'       => ['type' => 'number'],
        'designMarginBottom'    => ['type' => 'number'],
        'designPaddingTop'      => ['type' => 'number'],
        'designPaddingBottom'   => ['type' => 'number'],
        'designPaddingInline'   => ['type' => 'number'],
        'designGap'             => ['type' => 'number'],
        'designBorderColor'     => ['type' => 'string', 'default' => ''],
        'designBorderWidth'     => ['type' => 'number'],
        'designBorderRadius'    => ['type' => 'number'],
        'designWidth'           => ['type' => 'number'],
        'designMaxWidth'        => ['type' => 'number'],
    ];
    if ($is_container) {
        unset($common['designTextColor'], $common['designFontSize'], $common['designFontWeight'], $common['designLineHeight']);
    }

    $args['attributes'] = array_merge($common, $args['attributes'] ?? []);
    $content_attributes = [
        'theme/product-attributes' => ['featuresButtonLabel' => ['type'=>'string','default'=>'مشاهده همه ویژگی‌ها']],
        'theme/product-wishlist' => ['wishlistLabel'=>['type'=>'string','default'=>'افزودن به علاقمندی']],
        'theme/product-share' => ['shareLabel'=>['type'=>'string','default'=>'اشتراک گذاری:']],
        'theme/product-category-meta' => ['categoryLabel'=>['type'=>'string','default'=>'دسته‌بندی:']],
        'theme/product-brand-meta' => ['brandLabel'=>['type'=>'string','default'=>'برند:']],
        'theme/product-price' => ['currencyLabel'=>['type'=>'string','default'=>'تومان']],
        'theme/product-benefits' => [
            'showBenefit1'=>['type'=>'boolean','default'=>true],'showBenefit2'=>['type'=>'boolean','default'=>true],
            'showBenefit3'=>['type'=>'boolean','default'=>true],'showBenefit4'=>['type'=>'boolean','default'=>true],
            'benefit1Title'=>['type'=>'string','default'=>'ارسال سریع'],'benefit1Description'=>['type'=>'string','default'=>'با هماهنگی قبلی'],
            'benefit1ImageUrl'=>['type'=>'string','default'=>''],'benefit1ImageAlt'=>['type'=>'string','default'=>''],
            'benefit2Title'=>['type'=>'string','default'=>'ضمانت مرجوعی'],'benefit2Description'=>['type'=>'string','default'=>'تا ۷ روز کاری'],
            'benefit2ImageUrl'=>['type'=>'string','default'=>''],'benefit2ImageAlt'=>['type'=>'string','default'=>''],
            'benefit3Title'=>['type'=>'string','default'=>'تضمین اصالت'],'benefit3Description'=>['type'=>'string','default'=>'از بهترین برندها'],
            'benefit3ImageUrl'=>['type'=>'string','default'=>''],'benefit3ImageAlt'=>['type'=>'string','default'=>''],
            'benefit4Title'=>['type'=>'string','default'=>'مشاوره تخصصی'],'benefit4Description'=>['type'=>'string','default'=>'قبل و بعد از خرید'],
            'benefit4ImageUrl'=>['type'=>'string','default'=>''],'benefit4ImageAlt'=>['type'=>'string','default'=>''],
        ],
        'theme/product-details' => [
            'descriptionLabel'=>['type'=>'string','default'=>'توضیحات'],'specificationsLabel'=>['type'=>'string','default'=>'مشخصات'],
            'reviewsLabel'=>['type'=>'string','default'=>'دیدگاه‌ها'],'reviewsMobileLabel'=>['type'=>'string','default'=>'امتیاز و دیدگاه مشتری‌ها'],
            'brandLabel'=>['type'=>'string','default'=>'درباره برند'],'tabsAriaLabel'=>['type'=>'string','default'=>'اطلاعات محصول'],
            'moreLabel'=>['type'=>'string','default'=>'بیشتر'],'viewAllSpecificationsLabel'=>['type'=>'string','default'=>'مشاهده همه مشخصات'],
        ],
        'theme/product-similar-products' => ['sectionTitle'=>['type'=>'string','default'=>'محصولات مشابه'],'previousLabel'=>['type'=>'string','default'=>'محصولات قبلی'],'nextLabel'=>['type'=>'string','default'=>'محصولات بعدی']],
        'theme/product-related-products' => ['sectionTitle'=>['type'=>'string','default'=>'محصولات مرتبط'],'previousLabel'=>['type'=>'string','default'=>'محصولات قبلی'],'nextLabel'=>['type'=>'string','default'=>'محصولات بعدی']],
    ];
    if (isset($content_attributes[$block_type])) { $args['attributes'] = array_merge($content_attributes[$block_type], $args['attributes']); }
    return $args;
}
add_filter('register_block_type_args', 'my_theme_single_product_block_design_attributes', 10, 2);

/** Load the shared Inspector controls for all product-template blocks. */
function my_theme_enqueue_single_product_block_controls() {
    $path = get_theme_file_path('assets/js/single-product-block-controls.js');
    if (! file_exists($path)) { return; }
    wp_enqueue_script(
        'my-theme-single-product-block-controls',
        get_theme_file_uri('assets/js/single-product-block-controls.js'),
        ['wp-blocks', 'wp-compose', 'wp-element', 'wp-hooks', 'wp-block-editor', 'wp-components', 'wp-data'],
        filemtime($path),
        true
    );
}
add_action('enqueue_block_editor_assets', 'my_theme_enqueue_single_product_block_controls', 0);

/**
 * Upgrade database-saved single-product templates from the old self-closing
 * section blocks to real nested InnerBlocks. Without this migration PHP can
 * render the visual fallback, but Gutenberg has no selectable child blocks.
 */
function my_theme_migrate_single_product_inner_blocks() {
    $migration_version = '2';
    if ($migration_version === (string) get_option('my_theme_single_product_inner_blocks_version', '')) { return; }

    $templates = get_posts([
        'post_type' => 'wp_template', 'post_status' => ['publish','draft'],
        'posts_per_page' => -1,
    ]);

    $information = '<!-- wp:theme/product-information --><!-- wp:theme/product-overview --><!-- wp:theme/product-title /--><!-- wp:theme/product-rating /--><!-- wp:theme/product-attributes /--><!-- /wp:theme/product-overview --><!-- wp:theme/product-actions --><!-- wp:theme/product-wishlist /--><!-- wp:theme/product-share /--><!-- /wp:theme/product-actions --><!-- wp:theme/product-meta --><!-- wp:theme/product-category-meta /--><!-- wp:theme/product-brand-meta /--><!-- /wp:theme/product-meta --><!-- /wp:theme/product-information -->';
    $purchase = '<!-- wp:theme/product-purchase --><!-- wp:theme/product-purchase-info --><!-- wp:theme/product-guarantee /--><!-- wp:theme/product-weight /--><!-- /wp:theme/product-purchase-info --><!-- wp:theme/product-price /--><!-- wp:theme/product-purchase-actions --><!-- wp:theme/product-add-to-cart /--><!-- wp:theme/product-bulk-order /--><!-- /wp:theme/product-purchase-actions --><!-- wp:theme/product-shipping /--><!-- /wp:theme/product-purchase -->';
    $main = '<!-- wp:theme/product-main --><!-- wp:theme/product-gallery /-->' . $information . $purchase . '<!-- /wp:theme/product-main -->';
    $benefits = '<!-- wp:theme/product-benefits --><!-- wp:theme/product-benefit-item {"icon":"delivery","title":"ارسال سریع","description":"با هماهنگی قبلی"} /--><!-- wp:theme/product-benefit-item {"icon":"return","title":"ضمانت مرجوعی","description":"تا ۷ روز کاری"} /--><!-- wp:theme/product-benefit-item {"icon":"authenticity","title":"تضمین اصالت","description":"از بهترین برندها"} /--><!-- wp:theme/product-benefit-item {"icon":"consultation","title":"مشاوره تخصصی","description":"قبل و بعد از خرید"} /--><!-- /wp:theme/product-benefits -->';

    foreach ($templates as $template) {
        if (! $template instanceof WP_Post || false === strpos($template->post_content, 'wp:theme/single-product-content')) { continue; }
        $content = $template->post_content;
        $updated = preg_replace('/<!--\s+wp:theme\/product-main\s+\/-->/', $main, $content);
        $updated = preg_replace('/<!--\s+wp:theme\/product-information\s+\/-->/', $information, $updated);
        $updated = preg_replace('/<!--\s+wp:theme\/product-purchase\s+\/-->/', $purchase, $updated);
        $updated = preg_replace('/<!--\s+wp:theme\/product-overview\s+\/-->/', '<!-- wp:theme/product-overview --><!-- wp:theme/product-title /--><!-- wp:theme/product-rating /--><!-- wp:theme/product-attributes /--><!-- /wp:theme/product-overview -->', $updated);
        $updated = preg_replace('/<!--\s+wp:theme\/product-actions\s+\/-->/', '<!-- wp:theme/product-actions --><!-- wp:theme/product-wishlist /--><!-- wp:theme/product-share /--><!-- /wp:theme/product-actions -->', $updated);
        $updated = preg_replace('/<!--\s+wp:theme\/product-meta\s+\/-->/', '<!-- wp:theme/product-meta --><!-- wp:theme/product-category-meta /--><!-- wp:theme/product-brand-meta /--><!-- /wp:theme/product-meta -->', $updated);
        $updated = preg_replace('/<!--\s+wp:theme\/product-purchase-info\s+\/-->/', '<!-- wp:theme/product-purchase-info --><!-- wp:theme/product-guarantee /--><!-- wp:theme/product-weight /--><!-- /wp:theme/product-purchase-info -->', $updated);
        $updated = preg_replace('/<!--\s+wp:theme\/product-purchase-actions\s+\/-->/', '<!-- wp:theme/product-purchase-actions --><!-- wp:theme/product-add-to-cart /--><!-- wp:theme/product-bulk-order /--><!-- /wp:theme/product-purchase-actions -->', $updated);
        $updated = preg_replace('/<!--\s+wp:theme\/product-benefits\s+\/-->/', $benefits, $updated);
        if (! is_string($updated) || $updated === $content) { continue; }
        if ('' === (string) get_post_meta($template->ID, '_my_theme_single_product_before_inner_blocks', true)) {
            add_post_meta($template->ID, '_my_theme_single_product_before_inner_blocks', $content, true);
        }
        wp_update_post(['ID' => $template->ID, 'post_content' => wp_slash($updated)]);
    }
    update_option('my_theme_single_product_inner_blocks_version', $migration_version, false);
}
add_action('admin_init', 'my_theme_migrate_single_product_inner_blocks', 4);

/** Apply shared design attributes to the first element rendered by a block. */
function my_theme_apply_single_product_block_design($block_content, $block) {
    $name = $block['blockName'] ?? '';
    if ('theme/single-product-content' !== $name && ! str_starts_with($name, 'theme/product-')) { return $block_content; }
    if ('' === trim($block_content)) { return $block_content; }

    $attrs = $block['attrs'] ?? [];
    $container_blocks = ['theme/single-product-content','theme/product-main','theme/product-information','theme/product-overview','theme/product-actions','theme/product-meta','theme/product-purchase','theme/product-purchase-info','theme/product-purchase-actions','theme/product-benefits'];
    $is_container = in_array($name, $container_blocks, true);
    if ($is_container) {
        unset($attrs['designTextColor'], $attrs['designFontSize'], $attrs['designFontWeight'], $attrs['designLineHeight']);
    }
    $styles = [];
    $classes = [];
    $colors = ['designTextColor' => '--product-block-text-color', 'designBackgroundColor' => 'background-color', 'designBorderColor' => 'border-color'];
    foreach ($colors as $key => $property) {
        if (! empty($attrs[$key])) {
            $styles[] = $property . ':' . sanitize_text_field($attrs[$key]);
            if ('designTextColor' === $key) { $classes[] = 'has-product-design-text-color'; }
        }
    }
    $numbers = [
        'designFontSize' => ['font-size', 8, 120, 'px'], 'designLineHeight' => ['line-height', .5, 4, ''],
        'designMarginTop' => ['margin-top', 0, 300, 'px'], 'designMarginBottom' => ['margin-bottom', 0, 300, 'px'],
        'designPaddingTop' => ['padding-top', 0, 300, 'px'], 'designPaddingBottom' => ['padding-bottom', 0, 300, 'px'],
        'designPaddingInline' => ['padding-inline', 0, 300, 'px'], 'designGap' => ['gap', 0, 200, 'px'],
        'designBorderWidth' => ['border-width', 0, 20, 'px'], 'designBorderRadius' => ['border-radius', 0, 200, 'px'],
        'designWidth' => ['width', 1, 2000, 'px'], 'designMaxWidth' => ['max-width', 1, 2000, 'px'],
    ];
    foreach ($numbers as $key => $setting) {
        if (isset($attrs[$key]) && is_numeric($attrs[$key])) {
            $styles[] = $setting[0] . ':' . max($setting[1], min($setting[2], (float) $attrs[$key])) . $setting[3];
        }
    }
    if (isset($attrs['designFontSize']) && is_numeric($attrs['designFontSize'])) { $styles[] = '--product-block-font-size:' . (float) $attrs['designFontSize'] . 'px'; $classes[] = 'has-product-design-font-size'; }
    if (isset($attrs['designLineHeight']) && is_numeric($attrs['designLineHeight'])) { $styles[] = '--product-block-line-height:' . (float) $attrs['designLineHeight']; $classes[] = 'has-product-design-line-height'; }
    if (! empty($attrs['designFontWeight'])) { $styles[] = '--product-block-font-weight:' . sanitize_text_field($attrs['designFontWeight']); $classes[] = 'has-product-design-font-weight'; }
    if (! empty($attrs['designBorderWidth']) && 0 < (float) $attrs['designBorderWidth']) { $styles[] = 'border-style:solid'; }
    if (empty($styles)) { return $block_content; }

    $style = implode(';', $styles) . ';';
    if (class_exists('WP_HTML_Tag_Processor')) {
        $processor = new WP_HTML_Tag_Processor($block_content);
        if ($processor->next_tag()) {
            $existing = (string) $processor->get_attribute('style');
            $processor->set_attribute('style', $existing . $style);
            foreach ($classes as $class_name) { $processor->add_class($class_name); }
            return $processor->get_updated_html();
        }
    }
    return preg_replace('/<([a-z][a-z0-9-]*)(\s|>)/i', '<$1 class="' . esc_attr(implode(' ', $classes)) . '" style="' . esc_attr($style) . '"$2', $block_content, 1) ?: $block_content;
}
add_filter('render_block', 'my_theme_apply_single_product_block_design', 50, 2);

/**
 * Load the lightweight product gallery controller on product pages and in the
 * block editor canvas. The script exits immediately when no gallery exists.
 */
function my_theme_enqueue_single_product_gallery_script() {
    if (! is_product()) {
        return;
    }

    $script_path = get_stylesheet_directory() . '/assets/js/single-product-gallery.js';

    wp_enqueue_script(
        'my-theme-single-product-gallery',
        get_stylesheet_directory_uri() . '/assets/js/single-product-gallery.js',
        [],
        filemtime($script_path),
        true
    );
}
add_action('enqueue_block_assets', 'my_theme_enqueue_single_product_gallery_script');

/**
 * Load single-product interactions on product pages and in the block editor.
 */
function my_theme_enqueue_single_product_script() {
    if (! is_product()) {
        return;
    }

    $script_path = get_stylesheet_directory() . '/assets/js/single-product.js';
    $is_editor_preview = is_admin() || (defined('REST_REQUEST') && REST_REQUEST);

    wp_enqueue_script(
        'my-theme-single-product',
        get_stylesheet_directory_uri() . '/assets/js/single-product.js',
        [],
        filemtime($script_path),
        true
    );

    wp_localize_script(
        'my-theme-single-product',
        'myThemeSingleProduct',
        [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('my_theme_review_vote'),
            'isEditor'=> $is_editor_preview,
            'i18n'    => [
                'voteError'   => __('ثبت رأی انجام نشد. لطفاً دوباره تلاش کنید.', 'my-theme'),
                'voteSuccess' => __('رأی شما ثبت شد.', 'my-theme'),
            ],
        ]
    );
}
add_action('enqueue_block_assets', 'my_theme_enqueue_single_product_script');

/**
 * Enqueue the product-category filter card behavior.
 *
 * @return void
 */
function my_theme_enqueue_product_category_filter_card_asset() {
    $script_path = get_theme_file_path('assets/js/product-category-filter-card.js');

    if (! file_exists($script_path)) {
        return;
    }

    wp_enqueue_script(
        'my-theme-product-category-filter-card',
        get_theme_file_uri('assets/js/product-category-filter-card.js'),
        [],
        filemtime($script_path),
        true
    );
}

/**
 * Load the product-category filter card behavior only on frontend category
 * archives.
 *
 * @return void
 */
function my_theme_enqueue_product_category_filter_card_script() {
    if (is_admin() || ! my_theme_is_product_archive_context()) {
        return;
    }

    my_theme_enqueue_product_category_filter_card_asset();
}
add_action('wp_enqueue_scripts', 'my_theme_enqueue_product_category_filter_card_script');

/**
 * Load the product-category filter card behavior in the Site Editor preview.
 *
 * @return void
 */
function my_theme_enqueue_product_category_filter_card_editor_script() {
    my_theme_enqueue_product_category_filter_card_asset();
}
add_action('enqueue_block_editor_assets', 'my_theme_enqueue_product_category_filter_card_editor_script');

/**
 * Customize the single-product breadcrumb labels and separator markup.
 *
 * @param array $defaults WooCommerce breadcrumb display defaults.
 * @return array
 */
function my_theme_single_product_breadcrumb_defaults($defaults) {
    if (! is_product() && ! my_theme_single_product_is_editor_preview()) {
        return $defaults;
    }

    $defaults['home']      = _x('خانه', 'breadcrumb', 'my-theme');
    $defaults['before']    = '<span class="single-product-breadcrumb__item">';
    $defaults['after']     = '</span>';
    $defaults['delimiter'] = '<span class="single-product-breadcrumb__separator" aria-hidden="true">/</span>';

    return $defaults;
}
add_filter('woocommerce_breadcrumb_defaults', 'my_theme_single_product_breadcrumb_defaults');

/**
 * Detect Site Editor / REST block preview rendering without suppressing output.
 *
 * @return bool
 */
function my_theme_single_product_is_editor_preview() {
    return is_admin() || (defined('REST_REQUEST') && REST_REQUEST);
}

/**
 * Return the WooCommerce product represented by the current single-product render.
 *
 * @param WP_Block|null $block_instance Current block instance.
 * @return WC_Product|null
 */
function my_theme_single_product_get_current_product($block_instance = null) {
    if (! function_exists('wc_get_product')) {
        return null;
    }

    $product_id = 0;

    if ($block_instance instanceof WP_Block && ! empty($block_instance->context['postId'])) {
        $product_id = absint($block_instance->context['postId']);
    }

    if (! $product_id && is_singular('product')) {
        $product_id = absint(get_queried_object_id());
    }

    if (! $product_id) {
        $product_id = absint(get_the_ID());
    }

    if ($product_id && 'product' === get_post_type($product_id)) {
        $product = wc_get_product($product_id);

        if ($product instanceof WC_Product) {
            return $product;
        }
    }

    global $product;

    if ($product instanceof WC_Product) {
        return $product;
    }

    if (my_theme_single_product_is_editor_preview()) {
        $preview_product_ids = get_posts(
            [
                'post_type'              => 'product',
                'post_status'            => 'publish',
                'posts_per_page'         => 1,
                'fields'                 => 'ids',
                'orderby'                => 'date',
                'order'                  => 'DESC',
                'no_found_rows'          => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            ]
        );

        if (! empty($preview_product_ids[0])) {
            $preview_product = wc_get_product(absint($preview_product_ids[0]));

            if ($preview_product instanceof WC_Product) {
                return $preview_product;
            }
        }
    }

    return null;
}

/**
 * Return a product category term for archive/editor breadcrumb rendering.
 *
 * @return WP_Term|null
 */
function my_theme_get_product_category_breadcrumb_term() {
    $queried_object = get_queried_object();

    if ($queried_object instanceof WP_Term && 'product_cat' === $queried_object->taxonomy) {
        return $queried_object;
    }

    if (my_theme_single_product_is_editor_preview()) {
        $preview_terms = get_terms(
            [
                'taxonomy'   => 'product_cat',
                'hide_empty' => true,
                'number'     => 1,
                'orderby'    => 'name',
                'order'      => 'ASC',
            ]
        );

        if (! is_wp_error($preview_terms) && ! empty($preview_terms[0]) && $preview_terms[0] instanceof WP_Term) {
            return $preview_terms[0];
        }
    }

    return null;
}

/**
 * Convert breadcrumb block attributes into safe inline custom properties.
 *
 * @param array $attributes Block attributes.
 * @return string
 */
function my_theme_get_product_breadcrumb_style_attribute($attributes) {
    $styles = [];

    if (! empty($attributes['fontSize'])) {
        $font_size = is_numeric($attributes['fontSize']) ? $attributes['fontSize'] . 'px' : $attributes['fontSize'];
        $styles[] = '--product-breadcrumb-font-size:' . esc_attr($font_size);
    }

    if (! empty($attributes['fontWeight'])) {
        $styles[] = '--product-breadcrumb-font-weight:' . esc_attr($attributes['fontWeight']);
    }

    if (! empty($attributes['inactiveColor'])) {
        $styles[] = '--product-breadcrumb-inactive-color:' . esc_attr($attributes['inactiveColor']);
    }

    if (! empty($attributes['activeColor'])) {
        $styles[] = '--product-breadcrumb-active-color:' . esc_attr($attributes['activeColor']);
    }

    $numeric_styles = [
        'marginTop' => ['margin-top', 0, 160], 'marginBottom' => ['margin-bottom', 0, 160],
        'paddingTop' => ['padding-top', 0, 120], 'paddingBottom' => ['padding-bottom', 0, 120],
        'paddingInline' => ['padding-inline', 0, 160], 'borderWidth' => ['border-width', 0, 12],
        'borderRadius' => ['border-radius', 0, 80], 'separatorGap' => ['--product-breadcrumb-separator-gap', 0, 48],
    ];
    foreach ($numeric_styles as $key => $setting) {
        if (isset($attributes[$key])) {
            $styles[] = $setting[0] . ':' . max($setting[1], min($setting[2], (float) $attributes[$key])) . 'px';
        }
    }
    if (! empty($attributes['backgroundColor'])) { $styles[] = 'background-color:' . esc_attr($attributes['backgroundColor']); }
    if (! empty($attributes['borderColor'])) { $styles[] = 'border-color:' . esc_attr($attributes['borderColor']); }
    if (! empty($attributes['borderWidth'])) { $styles[] = 'border-style:solid'; }

    return empty($styles) ? '' : implode(';', $styles) . ';';
}

/**
 * Render the shared WooCommerce breadcrumb block.
 *
 * @param array         $attributes Block attributes.
 * @param WP_Block|null $block      Block instance.
 * @return string
 */
function my_theme_render_product_breadcrumb_block($attributes = [], $block = null) {
    $product = my_theme_single_product_get_current_product($block);
    $term = $product instanceof WC_Product ? null : my_theme_get_product_category_breadcrumb_term();

    if (! $product instanceof WC_Product && ! $term instanceof WP_Term) {
        if (my_theme_single_product_is_editor_preview()) {
            return '<nav class="single-product-breadcrumb" aria-label="' . esc_attr__('Breadcrumb', 'my-theme') . '"><ol class="woocommerce-breadcrumb"><li class="single-product-breadcrumb__item" aria-current="page">' . esc_html__('دسته‌بندی محصولی برای پیش‌نمایش پیدا نشد.', 'my-theme') . '</li></ol></nav>';
        }

        return '';
    }

    $crumbs = [];
    if ($product instanceof WC_Product && class_exists('WC_Breadcrumb')) {
        $breadcrumb = new WC_Breadcrumb();
        $crumbs = $breadcrumb->generate();
    } else {
        $shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : '';
        $crumbs = [[__('خانه', 'my-theme'), home_url('/')], [__('محصولات', 'my-theme'), $shop_url], [$term->name, '']];
    }
    if (false === ($attributes['showHome'] ?? true)) { array_shift($crumbs); }
    if (false === ($attributes['showCurrent'] ?? true)) { array_pop($crumbs); }
    if (! empty($crumbs) && ($attributes['showHome'] ?? true)) { $crumbs[0][0] = sanitize_text_field($attributes['homeLabel'] ?? __('خانه', 'my-theme')); }
    $separator = isset($attributes['separator']) && '' !== (string) $attributes['separator'] ? (string) $attributes['separator'] : '/';
    $style     = my_theme_get_product_breadcrumb_style_attribute($attributes);
    $nav_attrs = get_block_wrapper_attributes(
        [
            'class' => 'single-product-breadcrumb',
            'style' => $style,
        ]
    );

    ob_start();
    ?>
<nav <?php echo $nav_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    aria-label="<?php echo esc_attr__('Breadcrumb', 'my-theme'); ?>">
    <ol class="woocommerce-breadcrumb">
        <?php foreach ($crumbs as $index => $crumb) : ?>
        <?php if (0 < $index) : ?><li class="single-product-breadcrumb__separator" aria-hidden="true"><?php echo esc_html($separator); ?></li><?php endif; ?>
        <li class="single-product-breadcrumb__item" <?php echo $index === array_key_last($crumbs) ? 'aria-current="page"' : ''; ?>>
            <?php if (! empty($crumb[1]) && $index !== array_key_last($crumbs)) : ?><a href="<?php echo esc_url($crumb[1]); ?>"><?php echo esc_html($crumb[0]); ?></a><?php else : ?><span><?php echo esc_html($crumb[0]); ?></span><?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ol>
</nav>
<?php
    return trim(ob_get_clean());
}

/**
 * Return the category filter search icon SVG.
 *
 * @return string
 */
function my_theme_product_category_filter_search_svg() {
    return <<<'SVG'
<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <ellipse cx="6.81082" cy="6.64628" rx="6.81082" ry="6.64628" transform="matrix(-1 0 0 1 17.875 2.125)" stroke="#717171" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M6.38184 13.7207L2.12508 17.8746" stroke="#717171" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
SVG;
}

/**
 * Return the category item arrow SVG.
 *
 * @return string
 */
function my_theme_product_category_filter_item_arrow_svg() {
    return <<<'SVG'
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <path d="M15 18L9 12L15 6" stroke="#1A1E1B" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
SVG;
}

/**
 * Return the category accordion arrow SVG.
 *
 * @return string
 */
function my_theme_product_category_filter_toggle_arrow_svg() {
    return <<<'SVG'
<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <path d="M4 6L8 10L12 6" stroke="#1A1E1B" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
SVG;
}

/**
 * Return the active filter remove icon SVG.
 *
 * @param string $stroke Icon stroke color.
 * @return string
 */
function my_theme_product_active_filter_remove_svg($stroke = '#FFFFFF') {
    $stroke = 'green' === $stroke ? 'rgba(0, 158, 0, 1)' : '#FFFFFF';

    return sprintf(
        '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M3 3L11 11" stroke="%1$s" stroke-width="1.4" stroke-linecap="round"/><path d="M11 3L3 11" stroke="%1$s" stroke-width="1.4" stroke-linecap="round"/></svg>',
        esc_attr($stroke)
    );
}

/**
 * Return WooCommerce product categories for the filter card.
 *
 * @return WP_Term[]
 */
function my_theme_get_product_category_filter_terms() {
    $terms = get_terms(
        [
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ]
    );

    if (is_wp_error($terms) || ! is_array($terms)) {
        return [];
    }

    return array_values(
        array_filter(
            $terms,
            static function ($term) {
                return $term instanceof WP_Term && 'product_cat' === $term->taxonomy;
            }
        )
    );
}

/**
 * Detect the WooCommerce global color attribute taxonomy.
 *
 * @return string
 */
function my_theme_get_product_color_taxonomy() {
    static $detected_taxonomy = null;

    if (null !== $detected_taxonomy) {
        return $detected_taxonomy;
    }

    $detected_taxonomy = '';
    $preferred_names   = ['pa_color', 'pa_colour', 'pa_colors', 'pa_rang'];

    foreach ($preferred_names as $taxonomy) {
        if (taxonomy_exists($taxonomy)) {
            $detected_taxonomy = $taxonomy;
            return $detected_taxonomy;
        }
    }

    if (function_exists('wc_get_attribute_taxonomies') && function_exists('wc_attribute_taxonomy_name')) {
        $attribute_taxonomies = wc_get_attribute_taxonomies();
        $needles              = ['color', 'colour', 'colors', 'rang', 'رنگ'];

        foreach ($attribute_taxonomies as $attribute_taxonomy) {
            $attribute_name  = isset($attribute_taxonomy->attribute_name) ? (string) $attribute_taxonomy->attribute_name : '';
            $attribute_label = isset($attribute_taxonomy->attribute_label) ? (string) $attribute_taxonomy->attribute_label : '';
            $search_value    = strtolower($attribute_name . ' ' . $attribute_label);

            foreach ($needles as $needle) {
                if (false !== strpos($search_value, strtolower($needle))) {
                    $taxonomy = wc_attribute_taxonomy_name($attribute_name);

                    if (taxonomy_exists($taxonomy)) {
                        $detected_taxonomy = $taxonomy;
                        return $detected_taxonomy;
                    }
                }
            }
        }
    }

    return $detected_taxonomy;
}

/**
 * Sanitize a CSS color value from trusted term metadata.
 *
 * @param string $value Raw color value.
 * @return string
 */
function my_theme_sanitize_product_filter_color($value) {
    $value = trim((string) $value);

    if (preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value)) {
        return strtolower($value);
    }

    if (preg_match('/^rgba?\(\s*(?:\d{1,3}\s*,\s*){2}\d{1,3}(?:\s*,\s*(?:0|1|0?\.\d+))?\s*\)$/', $value)) {
        return $value;
    }

    return '';
}

/**
 * Return the private taxonomy used to index product color filter values.
 *
 * @return string
 */
function my_theme_get_product_color_filter_taxonomy() {
    return 'theme_filter_color';
}

/**
 * Return the stable URL query argument used by the color filter.
 *
 * @return string
 */
function my_theme_get_product_color_filter_query_arg_name() {
    return 'filter_color';
}

/**
 * Register the private product color filter index taxonomy.
 *
 * @return void
 */
function my_theme_register_product_color_filter_taxonomy() {
    register_taxonomy(
        my_theme_get_product_color_filter_taxonomy(),
        ['product'],
        [
            'public'             => false,
            'show_ui'            => false,
            'show_in_rest'       => false,
            'query_var'          => false,
            'rewrite'            => false,
            'hierarchical'       => false,
            'show_admin_column'  => false,
            'show_in_nav_menus'  => false,
            'show_tagcloud'      => false,
            'labels'             => [
                'name'          => __('Product filter colors', 'my-theme'),
                'singular_name' => __('Product filter color', 'my-theme'),
            ],
        ]
    );
}
add_action('init', 'my_theme_register_product_color_filter_taxonomy', 0);

/**
 * Normalize Persian/Arabic text variants for exact color comparisons.
 *
 * @param string $value Raw value.
 * @return string
 */
function my_theme_normalize_product_filter_color_name($value) {
    $value = trim(wp_strip_all_tags((string) $value));
    $value = str_replace(["\xc2\xa0", '‌'], [' ', ' '], $value);
    $value = str_replace(['ي', 'ك', 'ى', 'ۀ', 'ة'], ['ی', 'ک', 'ی', 'ه', 'ه'], $value);
    $value = preg_replace('/\s+/u', ' ', $value);

    if (function_exists('mb_strtolower')) {
        return mb_strtolower(trim((string) $value), 'UTF-8');
    }

    return strtolower(trim((string) $value));
}

/**
 * Split and normalize WooCommerce custom attribute option values.
 *
 * @param array<int,mixed> $options Raw attribute options.
 * @return array<string,string> Normalized value => visible value.
 */
function my_theme_normalize_custom_product_attribute_values($options) {
    $values = [];

    foreach ($options as $option) {
        $parts = preg_split('/[|,،]+/u', (string) $option);

        if (! is_array($parts)) {
            continue;
        }

        foreach ($parts as $part) {
            $visible = trim(wp_strip_all_tags((string) $part));

            if ('' === $visible) {
                continue;
            }

            $normalized = my_theme_normalize_product_filter_color_name($visible);

            if ('' === $normalized || isset($values[$normalized])) {
                continue;
            }

            $values[$normalized] = $visible;
        }
    }

    return $values;
}

/**
 * Resolve a CSS color value from a normalized or visible color name.
 *
 * @param string $color_name Color name or CSS color.
 * @return string
 */
function my_theme_resolve_product_filter_color_from_name($color_name) {
    $sanitized_color = my_theme_sanitize_product_filter_color($color_name);

    if ('' !== $sanitized_color) {
        return $sanitized_color;
    }

    $normalized = my_theme_normalize_product_filter_color_name($color_name);
    $color_map  = [
        'قرمز'     => '#ff0000',
        'آبی'      => '#0000ff',
        'ابی'      => '#0000ff',
        'سبز'      => '#008000',
        'زرد'      => '#ffff00',
        'نارنجی'   => '#ffa500',
        'بنفش'     => '#800080',
        'صورتی'    => '#ffc0cb',
        'مشکی'     => '#000000',
        'سیاه'     => '#000000',
        'سفید'     => '#ffffff',
        'خاکستری'  => '#808080',
        'طوسی'     => '#808080',
        'قهوه ای'  => '#a52a2a',
        'قهوه‌ای'  => '#a52a2a',
        'کرم'      => '#f5f0dc',
        'طلایی'    => '#d4af37',
        'نقره ای'  => '#c0c0c0',
        'نقره‌ای'  => '#c0c0c0',
        'red'      => '#ff0000',
        'blue'     => '#0000ff',
        'green'    => '#008000',
        'yellow'   => '#ffff00',
        'orange'   => '#ffa500',
        'purple'   => '#800080',
        'pink'     => '#ffc0cb',
        'black'    => '#000000',
        'white'    => '#ffffff',
        'gray'     => '#808080',
        'grey'     => '#808080',
        'brown'    => '#a52a2a',
    ];

    if (isset($color_map[$normalized])) {
        return $color_map[$normalized];
    }

    my_theme_debug_product_category_color_filter(sprintf('unresolved_color=%s', $color_name));

    return '';
}

/**
 * Resolve a simple CSS swatch color from a color-like text value.
 *
 * This is used only as a display fallback for custom product attributes that are
 * not backed by a WooCommerce global attribute taxonomy.
 *
 * @param string $value Raw color label/slug.
 * @return string
 */
function my_theme_get_product_color_fallback_value($value) {
    $value = trim((string) $value);

    if ('' === $value) {
        return '';
    }

    $sanitized_color = my_theme_sanitize_product_filter_color($value);

    if ('' !== $sanitized_color) {
        return $sanitized_color;
    }

    $fallbacks = [
        'red'       => '#ff0000',
        'blue'      => '#0000ff',
        'green'     => '#008000',
        'yellow'    => '#ffff00',
        'orange'    => '#ffa500',
        'purple'    => '#800080',
        'pink'      => '#ffc0cb',
        'black'     => '#000000',
        'white'     => '#ffffff',
        'gray'      => '#808080',
        'grey'      => '#808080',
        'brown'     => '#a52a2a',
        'قرمز'      => '#ff0000',
        'آبی'       => '#0000ff',
        'سبز'       => '#008000',
        'زرد'       => '#ffff00',
        'نارنجی'    => '#ffa500',
        'بنفش'      => '#800080',
        'صورتی'     => '#ffc0cb',
        'مشکی'      => '#000000',
        'سیاه'      => '#000000',
        'سفید'      => '#ffffff',
        'خاکستری'   => '#808080',
        'طوسی'      => '#808080',
        'قهوه‌ای'   => '#a52a2a',
        'قهوه-ای'   => '#a52a2a',
    ];

    $candidates = array_filter(
        array_unique(
            [
                sanitize_title($value),
                strtolower($value),
                $value,
            ]
        )
    );

    foreach ($candidates as $candidate) {
        if (isset($fallbacks[$candidate])) {
            return $fallbacks[$candidate];
        }
    }

    return '';
}

/**
 * Resolve a color value for a WooCommerce color attribute term.
 *
 * @param WP_Term $term Color term.
 * @return string
 */
function my_theme_get_product_color_value($term) {
    if (! $term instanceof WP_Term) {
        return '';
    }

    $meta_keys = [
        'color',
        'colour',
        'hex',
        'color_hex',
        'product_attribute_color',
        'swatch_color',
        'pa_color',
        'wvs_color',
        'woo_variation_swatches',
    ];

    foreach ($meta_keys as $meta_key) {
        $raw_value = get_term_meta($term->term_id, $meta_key, true);

        if (is_array($raw_value)) {
            $raw_value = $raw_value['color'] ?? $raw_value['value'] ?? '';
        }

        $color = my_theme_sanitize_product_filter_color((string) $raw_value);

        if ('' !== $color) {
            return $color;
        }
    }

    foreach ([$term->slug, $term->name] as $fallback_value) {
        $color = my_theme_resolve_product_filter_color_from_name($fallback_value);

        if ('' !== $color) {
            return $color;
        }
    }

    $fallbacks = [
        'red'       => '#ff0000',
        'blue'      => '#0000ff',
        'green'     => '#008000',
        'yellow'    => '#ffff00',
        'orange'    => '#ffa500',
        'purple'    => '#800080',
        'pink'      => '#ffc0cb',
        'black'     => '#000000',
        'white'     => '#ffffff',
        'gray'      => '#808080',
        'grey'      => '#808080',
        'brown'     => '#a52a2a',
        'قرمز'      => '#ff0000',
        'آبی'       => '#0000ff',
        'سبز'       => '#008000',
        'زرد'       => '#ffff00',
        'نارنجی'    => '#ffa500',
        'بنفش'      => '#800080',
        'صورتی'     => '#ffc0cb',
        'مشکی'      => '#000000',
        'سیاه'      => '#000000',
        'سفید'      => '#ffffff',
        'خاکستری'   => '#808080',
        'طوسی'      => '#808080',
        'قهوه‌ای'   => '#a52a2a',
        'قهوه-ای'   => '#a52a2a',
    ];
    $candidates = array_filter(
        array_unique(
            [
                sanitize_title($term->slug),
                sanitize_title($term->name),
                $term->slug,
                $term->name,
            ]
        )
    );

    foreach ($candidates as $candidate) {
        if (isset($fallbacks[$candidate])) {
            return $fallbacks[$candidate];
        }
    }

    return '';
}

/**
 * Return the WooCommerce layered-nav query parameter for a color taxonomy.
 *
 * @param string $taxonomy Attribute taxonomy.
 * @return string
 */
function my_theme_get_product_color_filter_query_arg($taxonomy) {
    return my_theme_get_product_color_filter_query_arg_name();
}

/**
 * Return a validated selected color term from the current URL.
 *
 * @param string $taxonomy Color taxonomy.
 * @return WP_Term|null
 */
function my_theme_get_selected_product_color_term($taxonomy) {
    $filter_taxonomy = my_theme_get_product_color_filter_taxonomy();

    if (! taxonomy_exists($filter_taxonomy)) {
        return null;
    }

    $query_arg = my_theme_get_product_color_filter_query_arg_name();

    if (empty($_GET[$query_arg])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return null;
    }

    $selected_slug = sanitize_title(wp_unslash($_GET[$query_arg])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $term          = get_term_by('slug', $selected_slug, $filter_taxonomy);

    return $term instanceof WP_Term ? $term : null;
}

/**
 * Return a version number used by product-category color filter cache keys.
 *
 * @return string
 */
function my_theme_get_product_category_color_cache_version() {
    $version = get_option('my_theme_product_category_color_cache_version', '1');

    return is_scalar($version) ? (string) $version : '1';
}

/**
 * Bump product-category color filter cache version.
 *
 * @return void
 */
function my_theme_bump_product_category_color_cache_version() {
    update_option('my_theme_product_category_color_cache_version', (string) time(), false);
}
add_action('save_post_product', 'my_theme_bump_product_category_color_cache_version');
add_action('deleted_post', 'my_theme_bump_product_category_color_cache_version');

/**
 * Bump color filter cache when relevant object terms change.
 *
 * @param int    $object_id Object ID.
 * @param array  $terms     Term IDs/slugs.
 * @param array  $tt_ids    Term taxonomy IDs.
 * @param string $taxonomy  Taxonomy.
 * @return void
 */
function my_theme_maybe_bump_product_category_color_cache_for_object_terms($object_id, $terms, $tt_ids, $taxonomy) {
    $color_taxonomy = my_theme_get_product_color_taxonomy();
    $filter_taxonomy = my_theme_get_product_color_filter_taxonomy();

    if ('product_cat' === $taxonomy || $filter_taxonomy === $taxonomy || ('' !== $color_taxonomy && $taxonomy === $color_taxonomy)) {
        my_theme_bump_product_category_color_cache_version();
    }
}
add_action('set_object_terms', 'my_theme_maybe_bump_product_category_color_cache_for_object_terms', 10, 4);

/**
 * Bump color filter cache when relevant terms change.
 *
 * @param int    $term_id Term ID.
 * @param int    $tt_id   Term taxonomy ID.
 * @param string $taxonomy Taxonomy.
 * @return void
 */
function my_theme_maybe_bump_product_category_color_cache_for_term_change($term_id, $tt_id, $taxonomy) {
    $color_taxonomy = my_theme_get_product_color_taxonomy();
    $filter_taxonomy = my_theme_get_product_color_filter_taxonomy();

    if ('product_cat' === $taxonomy || $filter_taxonomy === $taxonomy || ('' !== $color_taxonomy && $taxonomy === $color_taxonomy)) {
        my_theme_bump_product_category_color_cache_version();
    }
}
add_action('created_term', 'my_theme_maybe_bump_product_category_color_cache_for_term_change', 10, 3);
add_action('edited_term', 'my_theme_maybe_bump_product_category_color_cache_for_term_change', 10, 3);
add_action('delete_term', 'my_theme_maybe_bump_product_category_color_cache_for_term_change', 10, 3);

/**
 * Log color-filter diagnostics when WP_DEBUG is enabled.
 *
 * @param string $message Debug message.
 * @return void
 */
function my_theme_debug_product_category_color_filter($message) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[theme product color filter] ' . $message); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
    }
}

/**
 * Return product IDs belonging to a product category.
 *
 * @param int $category_term_id Product category term ID.
 * @return array<int,int>
 */
function my_theme_get_product_ids_for_product_category_color_filter($category_term_id) {
    $category_term_id = absint($category_term_id);

    if (0 === $category_term_id) {
        return [];
    }

    $tax_query = [
        [
            'taxonomy'         => 'product_cat',
            'field'            => 'term_id',
            'terms'            => [$category_term_id],
            'include_children' => true,
            'operator'         => 'IN',
        ],
    ];

    if (function_exists('wc_get_product_visibility_term_ids')) {
        $visibility_terms = wc_get_product_visibility_term_ids();

        if (! empty($visibility_terms['exclude-from-catalog'])) {
            $tax_query[] = [
                'taxonomy' => 'product_visibility',
                'field'    => 'term_taxonomy_id',
                'terms'    => [$visibility_terms['exclude-from-catalog']],
                'operator' => 'NOT IN',
            ];
        }
    }

    $product_ids = get_posts(
        [
            'post_type'              => 'product',
            'post_status'            => 'publish',
            'fields'                 => 'ids',
            'posts_per_page'         => -1,
            'no_found_rows'          => true,
            'tax_query'              => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
            'update_post_meta_cache' => true,
            'update_post_term_cache' => false,
        ]
    );

    return array_values(array_map('absint', is_array($product_ids) ? $product_ids : []));
}

/**
 * Determine whether a product attribute name/label represents color.
 *
 * @param string $attribute_name  Attribute name.
 * @param string $attribute_label Attribute label.
 * @param string $color_taxonomy  Detected global color taxonomy.
 * @return bool
 */
function my_theme_is_product_color_attribute_name($attribute_name, $attribute_label = '', $color_taxonomy = '') {
    $attribute_name  = (string) $attribute_name;
    $attribute_label = (string) $attribute_label;

    if ('' !== $color_taxonomy && $attribute_name === $color_taxonomy) {
        return true;
    }

    $attribute_slug = '';

    if ('' !== $color_taxonomy && function_exists('wc_attribute_taxonomy_slug')) {
        $attribute_slug = wc_attribute_taxonomy_slug($color_taxonomy);
    } elseif ('' !== $color_taxonomy) {
        $attribute_slug = preg_replace('/^pa_/', '', $color_taxonomy);
    }

    $haystack = strtolower($attribute_name . ' ' . $attribute_label);
    $needles  = array_filter(['color', 'colour', 'colors', 'rang', 'رنگ', $attribute_slug]);

    foreach ($needles as $needle) {
        if ('' !== $needle && false !== strpos($haystack, strtolower($needle))) {
            return true;
        }
    }

    return false;
}

/**
 * Extract custom text color values from a WooCommerce product.
 *
 * @param WC_Product $product Product object.
 * @return array<string,string> Normalized value => visible value.
 */
function my_theme_get_product_custom_color_values($product) {
    if (! $product instanceof WC_Product || ! is_callable([$product, 'get_attributes'])) {
        return [];
    }

    $values         = [];
    $color_taxonomy = my_theme_get_product_color_taxonomy();

    foreach ($product->get_attributes() as $attribute) {
        if (! $attribute instanceof WC_Product_Attribute || $attribute->is_taxonomy()) {
            continue;
        }

        $attribute_name = function_exists('wc_clean')
            ? wc_clean(wp_strip_all_tags((string) $attribute->get_name()))
            : sanitize_text_field(wp_strip_all_tags((string) $attribute->get_name()));

        my_theme_debug_product_category_color_filter(
            sprintf(
                'product=%d attribute=%s taxonomy=false options=%s',
                (int) $product->get_id(),
                $attribute_name,
                wp_json_encode($attribute->get_options())
            )
        );

        if (! my_theme_is_product_color_attribute_name($attribute_name, $attribute_name, $color_taxonomy)) {
            continue;
        }

        $values = array_replace($values, my_theme_normalize_custom_product_attribute_values($attribute->get_options()));
    }

    return $values;
}

/**
 * Extract all product color names used by the internal filter index.
 *
 * @param WC_Product $product Product object.
 * @return array<string,string> Normalized value => visible value.
 */
function my_theme_get_product_filter_color_values($product) {
    if (! $product instanceof WC_Product) {
        return [];
    }

    $values         = my_theme_get_product_custom_color_values($product);
    $color_taxonomy = my_theme_get_product_color_taxonomy();

    if ('' !== $color_taxonomy && taxonomy_exists($color_taxonomy)) {
        $terms = wp_get_object_terms((int) $product->get_id(), $color_taxonomy, ['fields' => 'all']);

        if (! is_wp_error($terms) && is_array($terms)) {
            foreach ($terms as $term) {
                if (! $term instanceof WP_Term) {
                    continue;
                }

                $normalized = my_theme_normalize_product_filter_color_name($term->name);

                if ('' !== $normalized && ! isset($values[$normalized])) {
                    $values[$normalized] = $term->name;
                }
            }
        }
    }

    return $values;
}

/**
 * Ensure a private filter color term exists and return its slug.
 *
 * @param string $visible_name Visible color name.
 * @return string
 */
function my_theme_ensure_product_color_filter_term($visible_name) {
    $taxonomy   = my_theme_get_product_color_filter_taxonomy();
    $normalized = my_theme_normalize_product_filter_color_name($visible_name);
    $slug       = sanitize_title($normalized);

    if ('' === $slug || ! taxonomy_exists($taxonomy)) {
        return '';
    }

    $existing = get_term_by('slug', $slug, $taxonomy);

    if ($existing instanceof WP_Term) {
        return $existing->slug;
    }

    $created = wp_insert_term($visible_name, $taxonomy, ['slug' => $slug]);

    if (is_wp_error($created)) {
        $existing = get_term_by('slug', $slug, $taxonomy);

        return $existing instanceof WP_Term ? $existing->slug : '';
    }

    return $slug;
}

/**
 * Sync the private filter color taxonomy and meta index for a product.
 *
 * @param int $product_id Product ID.
 * @return void
 */
function my_theme_sync_product_color_filter_terms($product_id) {
    static $syncing = [];

    $product_id = absint($product_id);

    if (0 === $product_id || isset($syncing[$product_id])) {
        return;
    }

    if (wp_is_post_autosave($product_id) || wp_is_post_revision($product_id) || 'product' !== get_post_type($product_id)) {
        return;
    }

    if (! function_exists('wc_get_product')) {
        return;
    }

    $product = wc_get_product($product_id);

    if (! $product instanceof WC_Product) {
        return;
    }

    $syncing[$product_id] = true;
    $values               = my_theme_get_product_filter_color_values($product);
    $term_slugs           = [];
    $meta_values          = [];

    foreach ($values as $normalized => $visible_name) {
        if ('' === my_theme_resolve_product_filter_color_from_name($visible_name)) {
            continue;
        }

        $term_slug = my_theme_ensure_product_color_filter_term($visible_name);

        if ('' === $term_slug) {
            continue;
        }

        $term_slugs[]  = $term_slug;
        $meta_values[] = $normalized;
    }

    wp_set_object_terms($product_id, array_values(array_unique($term_slugs)), my_theme_get_product_color_filter_taxonomy(), false);
    update_post_meta($product_id, '_theme_filter_colors', array_values(array_unique($meta_values)));
    my_theme_bump_product_category_color_cache_version();

    my_theme_debug_product_category_color_filter(
        sprintf(
            'synced_product=%d normalized_colors=%s private_terms=%s',
            $product_id,
            wp_json_encode(array_values(array_unique($meta_values))),
            wp_json_encode(array_values(array_unique($term_slugs)))
        )
    );

    unset($syncing[$product_id]);
}

/**
 * Sync product color filter terms after product saves.
 *
 * @param int $product_id Product ID.
 * @return void
 */
function my_theme_sync_product_color_filter_terms_on_save($product_id) {
    my_theme_sync_product_color_filter_terms($product_id);
}
add_action('save_post_product', 'my_theme_sync_product_color_filter_terms_on_save', 30);
add_action('woocommerce_update_product', 'my_theme_sync_product_color_filter_terms_on_save', 30);

/**
 * Run a small versioned backfill batch for existing products.
 *
 * @return void
 */
function my_theme_backfill_product_color_filter_terms() {
    if (! is_admin() && ! wp_doing_cron()) {
        return;
    }

    if (is_admin() && ! current_user_can('manage_woocommerce') && ! current_user_can('manage_options')) {
        return;
    }

    $target_version = 2;
    $version        = absint(get_option('theme_color_filter_index_version', 0));

    if ($version >= $target_version || ! function_exists('wc_get_product')) {
        return;
    }

    $offset      = absint(get_option('theme_color_filter_index_offset', 0));
    $product_ids = get_posts(
        [
            'post_type'              => 'product',
            'post_status'            => 'publish',
            'fields'                 => 'ids',
            'posts_per_page'         => 50,
            'offset'                 => $offset,
            'orderby'                => 'ID',
            'order'                  => 'ASC',
            'no_found_rows'          => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => false,
        ]
    );

    if (empty($product_ids)) {
        update_option('theme_color_filter_index_version', $target_version, false);
        delete_option('theme_color_filter_index_offset');
        my_theme_bump_product_category_color_cache_version();
        return;
    }

    foreach ($product_ids as $product_id) {
        my_theme_sync_product_color_filter_terms((int) $product_id);
    }

    update_option('theme_color_filter_index_offset', $offset + count($product_ids), false);
}
add_action('init', 'my_theme_backfill_product_color_filter_terms', 30);

/**
 * Add a normalized color item to a color list.
 *
 * @param array<string,array<string,mixed>> $colors      Color list keyed by unique source.
 * @param array<string,bool>                $seen_values Seen normalized CSS color values.
 * @param array<string,mixed>               $color_data  Color data.
 * @return void
 */
function my_theme_add_product_category_color_item(&$colors, &$seen_values, $color_data) {
    $color = isset($color_data['color']) ? my_theme_sanitize_product_filter_color((string) $color_data['color']) : '';

    if ('' === $color) {
        return;
    }

    $normalized_color = strtolower($color);

    if (isset($seen_values[$normalized_color])) {
        return;
    }

    $seen_values[$normalized_color] = true;

    $colors[] = array_merge(
        [
            'term'       => null,
            'term_id'    => 0,
            'name'       => '',
            'slug'       => '',
            'color'      => $color,
            'filterable' => false,
            'source'     => 'custom',
        ],
        $color_data,
        [
            'color' => $color,
        ]
    );
}

/**
 * Read display-only custom color values from product attributes.
 *
 * @param array<int,int> $product_ids     Product IDs.
 * @param string         $color_taxonomy  Detected global color taxonomy.
 * @param array          $colors          Color list.
 * @param array          $seen_values     Seen normalized CSS values.
 * @return int
 */
function my_theme_add_custom_product_attribute_colors($product_ids, $color_taxonomy, &$colors, &$seen_values) {
    if (! function_exists('wc_get_product')) {
        return 0;
    }

    $fallback_count = 0;

    foreach ($product_ids as $product_id) {
        $product = wc_get_product($product_id);

        if (! $product || ! is_callable([$product, 'get_attributes'])) {
            continue;
        }

        foreach ($product->get_attributes() as $attribute) {
            if (! $attribute instanceof WC_Product_Attribute || $attribute->is_taxonomy()) {
                continue;
            }

            if (! my_theme_is_product_color_attribute_name($attribute->get_name(), $attribute->get_name(), $color_taxonomy)) {
                continue;
            }

            foreach ($attribute->get_options() as $option) {
                $name  = trim(wp_strip_all_tags((string) $option));
                $color = my_theme_resolve_product_filter_color_from_name($name);

                if ('' === $name || '' === $color) {
                    continue;
                }

                $before_count = count($colors);

                my_theme_add_product_category_color_item(
                    $colors,
                    $seen_values,
                    [
                        'term_id'    => 0,
                        'name'       => $name,
                        'slug'       => sanitize_title($name),
                        'color'      => $color,
                        'filterable' => false,
                        'source'     => 'custom_attribute',
                    ]
                );

                if (count($colors) > $before_count) {
                    ++$fallback_count;
                }
            }
        }
    }

    return $fallback_count;
}

/**
 * Read variation color terms for products whose parent term assignments are incomplete.
 *
 * @param array<int,int> $product_ids     Product IDs.
 * @param string         $color_taxonomy  Detected global color taxonomy.
 * @param array          $colors          Color list.
 * @param array          $seen_values     Seen normalized CSS values.
 * @return int
 */
function my_theme_add_variation_product_attribute_colors($product_ids, $color_taxonomy, &$colors, &$seen_values) {
    if ('' === $color_taxonomy || ! taxonomy_exists($color_taxonomy) || ! function_exists('wc_get_product')) {
        return 0;
    }

    $fallback_count = 0;

    foreach ($product_ids as $product_id) {
        $product = wc_get_product($product_id);

        if (! $product || ! $product->is_type('variable') || ! is_callable([$product, 'get_children'])) {
            continue;
        }

        foreach ($product->get_children() as $variation_id) {
            $variation = wc_get_product($variation_id);

            if (! $variation || ! is_callable([$variation, 'get_attributes'])) {
                continue;
            }

            $attributes = $variation->get_attributes();
            $slug       = isset($attributes[$color_taxonomy]) ? (string) $attributes[$color_taxonomy] : '';

            if ('' === $slug) {
                $attribute_key = 'attribute_' . $color_taxonomy;
                $slug          = (string) get_post_meta($variation_id, $attribute_key, true);
            }

            if ('' === $slug) {
                continue;
            }

            $term = get_term_by('slug', sanitize_title($slug), $color_taxonomy);

            if (! $term instanceof WP_Term) {
                continue;
            }

            $color = my_theme_get_product_color_value($term);

            if ('' === $color) {
                continue;
            }

            $before_count = count($colors);

            my_theme_add_product_category_color_item(
                $colors,
                $seen_values,
                [
                    'term'       => $term,
                    'term_id'    => (int) $term->term_id,
                    'name'       => $term->name,
                    'slug'       => $term->slug,
                    'color'      => $color,
                    'filterable' => true,
                    'source'     => 'variation_attribute',
                ]
            );

            if (count($colors) > $before_count) {
                ++$fallback_count;
            }
        }
    }

    return $fallback_count;
}

/**
 * Return available color terms used by products in a product category.
 *
 * Primary data source is the detected WooCommerce global color attribute
 * taxonomy assigned to products in the current product_cat archive. Custom
 * attributes and variation attributes are used only as a display fallback.
 *
 * @param int    $category_term_id Product category term ID.
 * @param string $color_taxonomy   Color taxonomy.
 * @return array<int,array{term:?WP_Term,term_id:int,name:string,slug:string,color:string,filterable:bool,source:string}>
 */
function my_theme_get_available_color_terms_for_product_category($category_term_id, $color_taxonomy) {
    $category_term_id = absint($category_term_id);
    $color_taxonomy   = sanitize_key($color_taxonomy);
    $filter_taxonomy  = my_theme_get_product_color_filter_taxonomy();

    if (0 === $category_term_id || ! taxonomy_exists($filter_taxonomy)) {
        return [];
    }

    $cache_key = implode(
        '_',
        [
            'category_colors_v3',
            my_theme_get_product_category_color_cache_version(),
            $category_term_id,
            '' !== $color_taxonomy ? $color_taxonomy : 'custom',
            determine_locale(),
        ]
    );
    $cached    = wp_cache_get($cache_key, 'my_theme');

    if (is_array($cached)) {
        return $cached;
    }

    $product_ids = my_theme_get_product_ids_for_product_category_color_filter($category_term_id);
    $colors      = [];
    $seen_values = [];

    if (empty($product_ids)) {
        wp_cache_set($cache_key, [], 'my_theme', 10 * MINUTE_IN_SECONDS);
        my_theme_debug_product_category_color_filter(
            sprintf(
                'category=%d taxonomy=%s products=0 global_terms=0 custom_fallbacks=0 variation_fallbacks=0',
                $category_term_id,
                $color_taxonomy
            )
        );
        return [];
    }

    foreach ($product_ids as $product_id) {
        $indexed_colors = get_post_meta($product_id, '_theme_filter_colors', true);

        if (empty($indexed_colors)) {
            my_theme_sync_product_color_filter_terms($product_id);
        }
    }

    $private_terms = wp_get_object_terms($product_ids, $filter_taxonomy, ['orderby' => 'name', 'order' => 'ASC']);

    if (! is_wp_error($private_terms) && is_array($private_terms)) {
        foreach ($private_terms as $term) {
            if (! $term instanceof WP_Term) {
                continue;
            }

            $color = my_theme_resolve_product_filter_color_from_name($term->name);

            if ('' === $color) {
                continue;
            }

            my_theme_add_product_category_color_item(
                $colors,
                $seen_values,
                [
                    'term'       => $term,
                    'term_id'    => (int) $term->term_id,
                    'name'       => $term->name,
                    'slug'       => $term->slug,
                    'color'      => $color,
                    'filterable' => true,
                    'source'     => 'private_index',
                ]
            );
        }
    }

    $terms = '' !== $color_taxonomy && taxonomy_exists($color_taxonomy)
        ? wp_get_object_terms($product_ids, $color_taxonomy, ['orderby' => 'name', 'order' => 'ASC'])
        : [];

    if (! is_wp_error($terms) && is_array($terms)) {
        foreach ($terms as $term) {
            if (! $term instanceof WP_Term) {
                continue;
            }

            $color = my_theme_get_product_color_value($term);

            if ('' === $color) {
                continue;
            }

            my_theme_add_product_category_color_item(
                $colors,
                $seen_values,
                [
                    'term'       => $term,
                    'term_id'    => (int) $term->term_id,
                    'name'       => $term->name,
                    'slug'       => $term->slug,
                    'color'      => $color,
                    'filterable' => true,
                    'source'     => 'global_taxonomy',
                ]
            );
        }
    }

    $global_term_count       = count($colors);
    $custom_fallback_count   = my_theme_add_custom_product_attribute_colors($product_ids, $color_taxonomy, $colors, $seen_values);
    $variation_fallback_count = my_theme_add_variation_product_attribute_colors($product_ids, $color_taxonomy, $colors, $seen_values);

    wp_cache_set($cache_key, $colors, 'my_theme', 10 * MINUTE_IN_SECONDS);

    my_theme_debug_product_category_color_filter(
        sprintf(
            'category=%d taxonomy=%s products=%d global_terms=%d custom_fallbacks=%d variation_fallbacks=%d total_colors=%d',
            $category_term_id,
            $color_taxonomy,
            count($product_ids),
            $global_term_count,
            $custom_fallback_count,
            $variation_fallback_count,
            count($colors)
        )
    );

    return $colors;
}

/**
 * Return available color terms used by products in the current category.
 *
 * @param WP_Term|null $category Current product category.
 * @param string       $taxonomy Color taxonomy.
 * @return array<int,array{term:?WP_Term,term_id:int,name:string,slug:string,color:string,filterable:bool,source:string}>
 */
function my_theme_get_available_product_category_colors($category, $taxonomy) {
    if (! $category instanceof WP_Term) {
        return [];
    }

    return my_theme_get_available_color_terms_for_product_category((int) $category->term_id, $taxonomy);
}

/**
 * Build a color filter URL preserving the current archive and valid query args.
 *
 * @param WP_Term $term          Color term.
 * @param string  $taxonomy      Color taxonomy.
 * @param bool    $is_selected   Whether the term is currently selected.
 * @return string
 */
function my_theme_get_product_color_filter_url($term, $taxonomy, $is_selected) {
    $query_arg      = my_theme_get_product_color_filter_query_arg_name();
    $attribute_slug = '' !== $taxonomy && function_exists('wc_attribute_taxonomy_slug') ? wc_attribute_taxonomy_slug($taxonomy) : preg_replace('/^pa_/', '', (string) $taxonomy);
    $remove_args    = array_filter(['paged', 'product-page', $query_arg, 'filter_' . sanitize_key($attribute_slug), 'query_type_' . sanitize_key($attribute_slug)]);
    $base_url       = remove_query_arg($remove_args, get_pagenum_link(1));

    if ($is_selected) {
        return $base_url;
    }

    return add_query_arg(
        [
            $query_arg => $term->slug,
        ],
        $base_url
    );
}

/**
 * Render the product category color filter block.
 *
 * @param array         $attributes Block attributes.
 * @param WP_Block|null $block      Block instance.
 * @return string
 */
function my_theme_render_product_category_color_filter_block($attributes = [], $block = null) {
    $label         = isset($attributes['label']) && '' !== trim((string) $attributes['label']) ? (string) $attributes['label'] : __('رنگ', 'my-theme');
    $taxonomy      = my_theme_get_product_color_taxonomy();
    $category      = my_theme_get_product_category_breadcrumb_term();
    $selected_term = my_theme_get_selected_product_color_term($taxonomy);
    $colors        = my_theme_get_available_product_category_colors($category, $taxonomy);
    $panel_id      = wp_unique_id('product-color-filter-panel-');
    $is_open       = my_theme_single_product_is_editor_preview() || $selected_term instanceof WP_Term;
    $wrapper_attrs = get_block_wrapper_attributes(
        [
            'class'                 => 'product-category-color-filter product-filter-accordion',
            'data-filter-accordion' => '',
        ]
    );

    ob_start();
    ?>
<div <?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <button type="button" class="product-category-filter-card__toggle"
        aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>"
        aria-controls="<?php echo esc_attr($panel_id); ?>" data-filter-accordion-toggle>
        <span class="product-category-filter-card__toggle-label">
            <?php echo esc_html($label); ?>
        </span>
        <span class="product-category-filter-card__toggle-icon" aria-hidden="true">
            <?php echo my_theme_product_category_filter_toggle_arrow_svg(); ?>
        </span>
    </button>

    <div id="<?php echo esc_attr($panel_id); ?>" class="product-category-color-filter__panel"
        data-filter-accordion-panel <?php echo $is_open ? '' : 'hidden'; ?>>
        <?php if (empty($colors)) : ?>
            <p class="product-category-color-filter__empty">
                <?php echo esc_html__('رنگی برای این دسته‌بندی یافت نشد', 'my-theme'); ?>
            </p>
        <?php else : ?>
            <div class="product-category-color-filter__options">
                <?php foreach ($colors as $color_data) : ?>
                    <?php
                    $term          = $color_data['term'] ?? null;
                    $color         = isset($color_data['color']) ? (string) $color_data['color'] : '';
                    $color_name    = isset($color_data['name']) ? (string) $color_data['name'] : '';
                    $is_filterable = ! empty($color_data['filterable']) && $term instanceof WP_Term;
                    $is_selected   = $is_filterable && $selected_term instanceof WP_Term && (int) $selected_term->term_id === (int) $term->term_id;
                    $filter_url    = $is_filterable ? my_theme_get_product_color_filter_url($term, $taxonomy, $is_selected) : '';
                    $aria_label    = $is_filterable
                        ? (
                            $is_selected
                                ? sprintf(__('حذف فیلتر رنگ %s', 'my-theme'), $color_name)
                                : sprintf(__('فیلتر بر اساس رنگ %s', 'my-theme'), $color_name)
                        )
                        : sprintf(__('رنگ %s', 'my-theme'), $color_name);
                    ?>
                    <?php if ($is_filterable) : ?>
                        <a class="product-category-color-filter__option"
                            href="<?php echo esc_url(my_theme_single_product_is_editor_preview() ? '#' : $filter_url); ?>"
                            aria-label="<?php echo esc_attr($aria_label); ?>"
                            <?php echo $is_selected ? 'aria-current="true"' : ''; ?>>
                            <span class="product-category-color-filter__selection-ring">
                                <span class="product-category-color-filter__swatch"
                                    style="--product-filter-color: <?php echo esc_attr($color); ?>"></span>
                            </span>
                            <span class="screen-reader-text">
                                <?php echo esc_html(sprintf(__('رنگ %s', 'my-theme'), $color_name)); ?>
                            </span>
                        </a>
                    <?php else : ?>
                        <span class="product-category-color-filter__option"
                            role="img"
                            aria-label="<?php echo esc_attr($aria_label); ?>">
                            <span class="product-category-color-filter__selection-ring">
                                <span class="product-category-color-filter__swatch"
                                    style="--product-filter-color: <?php echo esc_attr($color); ?>"></span>
                            </span>
                            <span class="screen-reader-text">
                                <?php echo esc_html(sprintf(__('رنگ %s', 'my-theme'), $color_name)); ?>
                            </span>
                        </span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
    return trim(ob_get_clean());
}

/**
 * Add the selected product color to WooCommerce archive product queries.
 *
 * @param array $tax_query Existing WooCommerce tax query.
 * @return array
 */
function my_theme_filter_product_category_query_by_color($tax_query) {
    if (is_admin() || ! function_exists('is_product_category') || ! is_product_category()) {
        return $tax_query;
    }

    $taxonomy      = my_theme_get_product_color_filter_taxonomy();
    $selected_term = my_theme_get_selected_product_color_term($taxonomy);

    if (! taxonomy_exists($taxonomy) || ! $selected_term instanceof WP_Term) {
        return $tax_query;
    }

    $tax_query[] = [
        'taxonomy' => $taxonomy,
        'field'    => 'slug',
        'terms'    => [$selected_term->slug],
        'operator' => 'IN',
    ];

    return $tax_query;
}
add_filter('woocommerce_product_query_tax_query', 'my_theme_filter_product_category_query_by_color');

/**
 * Return the private taxonomy used to index custom product brand attributes.
 *
 * @return string
 */
function my_theme_get_product_brand_filter_taxonomy() {
    return 'theme_filter_brand';
}

/**
 * Register the private product brand filter index taxonomy.
 *
 * @return void
 */
function my_theme_register_product_brand_filter_taxonomy() {
    register_taxonomy(
        my_theme_get_product_brand_filter_taxonomy(),
        ['product'],
        [
            'public'             => false,
            'show_ui'            => false,
            'show_in_rest'       => false,
            'query_var'          => false,
            'rewrite'            => false,
            'hierarchical'       => false,
            'show_admin_column'  => false,
            'show_in_nav_menus'  => false,
            'show_tagcloud'      => false,
            'labels'             => [
                'name'          => __('Product filter brands', 'my-theme'),
                'singular_name' => __('Product filter brand', 'my-theme'),
            ],
        ]
    );
}
add_action('init', 'my_theme_register_product_brand_filter_taxonomy', 0);

/**
 * Return the first registered product-brand taxonomy supported by the project.
 *
 * @return string
 */
function my_theme_get_product_category_brand_taxonomy() {
    foreach (my_theme_get_product_brand_taxonomies() as $taxonomy) {
        if (taxonomy_exists($taxonomy)) {
            return $taxonomy;
        }
    }

    return '';
}

/**
 * Return the URL query argument for selected brands.
 *
 * @return string
 */
function my_theme_get_product_brand_filter_query_arg() {
    return 'filter_brand';
}

/**
 * Normalize a brand value for exact comparisons and slugs.
 *
 * @param string $value Raw brand value.
 * @return string
 */
function my_theme_normalize_product_filter_brand_name($value) {
    return my_theme_normalize_product_filter_color_name($value);
}

/**
 * Split WooCommerce custom brand attribute option values.
 *
 * @param array<int,mixed> $options Raw attribute options.
 * @return array<string,string> Normalized value => visible value.
 */
function my_theme_normalize_custom_product_brand_values($options) {
    return my_theme_normalize_custom_product_attribute_values($options);
}

/**
 * Determine whether an attribute represents brand.
 *
 * @param string $attribute_name  Attribute name.
 * @param string $attribute_label Attribute label.
 * @return bool
 */
function my_theme_is_product_brand_attribute_name($attribute_name, $attribute_label = '') {
    $haystack = strtolower((string) $attribute_name . ' ' . (string) $attribute_label);
    $needles  = ['brand', 'brands', 'برند'];

    foreach ($needles as $needle) {
        if ('' !== $needle && false !== strpos($haystack, strtolower($needle))) {
            return true;
        }
    }

    return false;
}

/**
 * Extract custom text brand values from a WooCommerce product.
 *
 * @param WC_Product $product Product object.
 * @return array<string,string> Normalized value => visible value.
 */
function my_theme_get_product_custom_brand_values($product) {
    if (! $product instanceof WC_Product || ! is_callable([$product, 'get_attributes'])) {
        return [];
    }

    $values = [];

    foreach ($product->get_attributes() as $attribute) {
        if (! $attribute instanceof WC_Product_Attribute || $attribute->is_taxonomy()) {
            continue;
        }

        $attribute_name = function_exists('wc_clean')
            ? wc_clean(wp_strip_all_tags((string) $attribute->get_name()))
            : sanitize_text_field(wp_strip_all_tags((string) $attribute->get_name()));

        if (! my_theme_is_product_brand_attribute_name($attribute_name, $attribute_name)) {
            continue;
        }

        $values = array_replace($values, my_theme_normalize_custom_product_brand_values($attribute->get_options()));
    }

    return $values;
}

/**
 * Extract all product brand values for the private filter index.
 *
 * @param WC_Product $product Product object.
 * @return array<string,string> Normalized value => visible value.
 */
function my_theme_get_product_filter_brand_values($product) {
    if (! $product instanceof WC_Product) {
        return [];
    }

    $values = my_theme_get_product_custom_brand_values($product);

    foreach (my_theme_get_product_brand_taxonomies() as $taxonomy) {
        if (! taxonomy_exists($taxonomy)) {
            continue;
        }

        $terms = wp_get_object_terms((int) $product->get_id(), $taxonomy, ['fields' => 'all']);

        if (is_wp_error($terms) || ! is_array($terms)) {
            continue;
        }

        foreach ($terms as $term) {
            if (! $term instanceof WP_Term) {
                continue;
            }

            $normalized = my_theme_normalize_product_filter_brand_name($term->name);

            if ('' !== $normalized && ! isset($values[$normalized])) {
                $values[$normalized] = $term->name;
            }
        }
    }

    return $values;
}

/**
 * Ensure a private filter brand term exists and return its slug.
 *
 * @param string $visible_name Visible brand name.
 * @return string
 */
function my_theme_ensure_product_brand_filter_term($visible_name) {
    $taxonomy   = my_theme_get_product_brand_filter_taxonomy();
    $normalized = my_theme_normalize_product_filter_brand_name($visible_name);
    $slug       = sanitize_title($normalized);

    if ('' === $slug || ! taxonomy_exists($taxonomy)) {
        return '';
    }

    $existing = get_term_by('slug', $slug, $taxonomy);

    if ($existing instanceof WP_Term) {
        return $existing->slug;
    }

    $created = wp_insert_term($visible_name, $taxonomy, ['slug' => $slug]);

    if (is_wp_error($created)) {
        $existing = get_term_by('slug', $slug, $taxonomy);
        return $existing instanceof WP_Term ? $existing->slug : '';
    }

    return $slug;
}

/**
 * Sync private brand filter terms for a product.
 *
 * @param int $product_id Product ID.
 * @return void
 */
function my_theme_sync_product_brand_filter_terms($product_id) {
    static $syncing = [];

    $product_id = absint($product_id);

    if (0 === $product_id || isset($syncing[$product_id])) {
        return;
    }

    if (wp_is_post_autosave($product_id) || wp_is_post_revision($product_id) || 'product' !== get_post_type($product_id)) {
        return;
    }

    if (! function_exists('wc_get_product')) {
        return;
    }

    $product = wc_get_product($product_id);

    if (! $product instanceof WC_Product) {
        return;
    }

    $syncing[$product_id] = true;
    $values               = my_theme_get_product_filter_brand_values($product);
    $term_slugs           = [];
    $meta_values          = [];

    foreach ($values as $normalized => $visible_name) {
        $term_slug = my_theme_ensure_product_brand_filter_term($visible_name);

        if ('' === $term_slug) {
            continue;
        }

        $term_slugs[]  = $term_slug;
        $meta_values[] = $normalized;
    }

    wp_set_object_terms($product_id, array_values(array_unique($term_slugs)), my_theme_get_product_brand_filter_taxonomy(), false);
    update_post_meta($product_id, '_theme_filter_brands', array_values(array_unique($meta_values)));
    my_theme_bump_product_category_color_cache_version();

    unset($syncing[$product_id]);
}

/**
 * Sync product brand filter terms after product saves.
 *
 * @param int $product_id Product ID.
 * @return void
 */
function my_theme_sync_product_brand_filter_terms_on_save($product_id) {
    my_theme_sync_product_brand_filter_terms($product_id);
}
add_action('save_post_product', 'my_theme_sync_product_brand_filter_terms_on_save', 30);
add_action('woocommerce_update_product', 'my_theme_sync_product_brand_filter_terms_on_save', 30);

/**
 * Run a small versioned brand-index backfill batch.
 *
 * @return void
 */
function my_theme_backfill_product_brand_filter_terms() {
    if (! is_admin() && ! wp_doing_cron()) {
        return;
    }

    if (is_admin() && ! current_user_can('manage_woocommerce') && ! current_user_can('manage_options')) {
        return;
    }

    $target_version = 1;
    $version        = absint(get_option('theme_brand_filter_index_version', 0));

    if ($version >= $target_version || ! function_exists('wc_get_product')) {
        return;
    }

    $offset      = absint(get_option('theme_brand_filter_index_offset', 0));
    $product_ids = get_posts(
        [
            'post_type'              => 'product',
            'post_status'            => 'publish',
            'fields'                 => 'ids',
            'posts_per_page'         => 50,
            'offset'                 => $offset,
            'orderby'                => 'ID',
            'order'                  => 'ASC',
            'no_found_rows'          => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => false,
        ]
    );

    if (empty($product_ids)) {
        update_option('theme_brand_filter_index_version', $target_version, false);
        delete_option('theme_brand_filter_index_offset');
        my_theme_bump_product_category_color_cache_version();
        return;
    }

    foreach ($product_ids as $product_id) {
        my_theme_sync_product_brand_filter_terms((int) $product_id);
    }

    update_option('theme_brand_filter_index_offset', $offset + count($product_ids), false);
}
add_action('init', 'my_theme_backfill_product_brand_filter_terms', 30);

/**
 * Return selected, validated brand slugs from the URL.
 *
 * @return array<int,string>
 */
function my_theme_get_selected_product_brand_slugs() {
    $query_arg = my_theme_get_product_brand_filter_query_arg();

    if (empty($_GET[$query_arg])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return [];
    }

    $raw_value = wp_unslash($_GET[$query_arg]); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $slugs     = array_filter(array_unique(array_map('sanitize_title', explode(',', (string) $raw_value))));
    $valid     = [];

    foreach ($slugs as $slug) {
        foreach (array_merge(my_theme_get_product_brand_taxonomies(), [my_theme_get_product_brand_filter_taxonomy()]) as $taxonomy) {
            if (taxonomy_exists($taxonomy) && get_term_by('slug', $slug, $taxonomy) instanceof WP_Term) {
                $valid[] = $slug;
                break;
            }
        }
    }

    return array_values(array_unique($valid));
}

/**
 * Build a brand filter URL for toggling one brand slug.
 *
 * @param string $slug           Brand slug.
 * @param bool   $is_selected    Whether selected.
 * @param array  $selected_slugs Current selected slugs.
 * @return string
 */
function my_theme_get_product_brand_filter_url($slug, $is_selected, $selected_slugs) {
    $query_arg = my_theme_get_product_brand_filter_query_arg();
    $base_url  = remove_query_arg(['paged', 'product-page', $query_arg], get_pagenum_link(1));
    $next      = array_values(array_diff($selected_slugs, [$slug]));

    if (! $is_selected) {
        $next[] = $slug;
    }

    $next = array_values(array_unique(array_filter(array_map('sanitize_title', $next))));

    if (empty($next)) {
        return $base_url;
    }

    return add_query_arg($query_arg, implode(',', $next), $base_url);
}

/**
 * Return available brands used by products in a product category.
 *
 * @param int $category_id Product category ID.
 * @return array<int,array{id:int|null,name:string,slug:string,taxonomy:string,source:string}>
 */
function my_theme_get_available_brands_for_product_category($category_id) {
    $category_id = absint($category_id);

    if (0 === $category_id) {
        return [];
    }

    $product_ids = my_theme_get_product_ids_for_product_category_color_filter($category_id);
    $brands      = [];

    if (empty($product_ids)) {
        return [];
    }

    foreach ($product_ids as $product_id) {
        if (empty(get_post_meta($product_id, '_theme_filter_brands', true))) {
            my_theme_sync_product_brand_filter_terms($product_id);
        }
    }

    $taxonomies = array_merge(my_theme_get_product_brand_taxonomies(), [my_theme_get_product_brand_filter_taxonomy()]);

    foreach ($taxonomies as $taxonomy) {
        if (! taxonomy_exists($taxonomy)) {
            continue;
        }

        $terms = wp_get_object_terms($product_ids, $taxonomy, ['orderby' => 'name', 'order' => 'ASC']);

        if (is_wp_error($terms) || ! is_array($terms)) {
            continue;
        }

        foreach ($terms as $term) {
            if (! $term instanceof WP_Term) {
                continue;
            }

            $normalized = my_theme_normalize_product_filter_brand_name($term->name);

            if ('' === $normalized || isset($brands[$normalized])) {
                continue;
            }

            $brands[$normalized] = [
                'id'       => (int) $term->term_id,
                'name'     => $term->name,
                'slug'     => $term->slug,
                'taxonomy' => $taxonomy,
                'source'   => my_theme_get_product_brand_filter_taxonomy() === $taxonomy ? 'private_index' : 'brand_taxonomy',
            ];
        }
    }

    uasort(
        $brands,
        static function ($a, $b) {
            return strnatcasecmp($a['name'], $b['name']);
        }
    );

    return array_values($brands);
}

/**
 * Render the product category brand filter block.
 *
 * @param array         $attributes Block attributes.
 * @param WP_Block|null $block      Block instance.
 * @return string
 */
function my_theme_render_product_category_brand_filter_block($attributes = [], $block = null) {
    $label            = isset($attributes['label']) && '' !== trim((string) $attributes['label']) ? (string) $attributes['label'] : __('برند', 'my-theme');
    $placeholder      = isset($attributes['placeholder']) && '' !== trim((string) $attributes['placeholder']) ? (string) $attributes['placeholder'] : __('جستجو برند', 'my-theme');
    $category         = my_theme_get_product_category_breadcrumb_term();
    $selected_slugs   = my_theme_get_selected_product_brand_slugs();
    $brands           = $category instanceof WP_Term ? my_theme_get_available_brands_for_product_category((int) $category->term_id) : [];
    $panel_id         = wp_unique_id('product-brand-filter-panel-');
    $is_open          = my_theme_single_product_is_editor_preview() || ! empty($selected_slugs);
    $wrapper_attrs    = get_block_wrapper_attributes(
        [
            'class'                 => 'product-brand-filter product-filter-accordion',
            'data-filter-accordion' => '',
        ]
    );
    $has_brand_source = '' !== my_theme_get_product_category_brand_taxonomy() || taxonomy_exists(my_theme_get_product_brand_filter_taxonomy());

    ob_start();
    ?>
<div <?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <button type="button" class="product-category-filter-card__toggle"
        aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>"
        aria-controls="<?php echo esc_attr($panel_id); ?>" data-filter-accordion-toggle>
        <span class="product-category-filter-card__toggle-label">
            <?php echo esc_html($label); ?>
        </span>
        <span class="product-category-filter-card__toggle-icon" aria-hidden="true">
            <?php echo my_theme_product_category_filter_toggle_arrow_svg(); ?>
        </span>
    </button>

    <div id="<?php echo esc_attr($panel_id); ?>" class="product-brand-filter__panel"
        data-filter-accordion-panel <?php echo $is_open ? '' : 'hidden'; ?>>
        <?php $brand_search_empty_id = wp_unique_id('product-brand-filter-empty-'); ?>
        <div class="product-brand-filter__search">
            <input type="search" class="product-brand-filter__search-input"
                placeholder="<?php echo esc_attr($placeholder); ?>"
                aria-label="<?php echo esc_attr($placeholder); ?>"
                aria-describedby="<?php echo esc_attr($brand_search_empty_id); ?>"
                autocomplete="off"
                data-brand-filter-search
                data-filter-option-search
                data-filter-target="brand">
            <span class="product-brand-filter__search-icon" aria-hidden="true">
                <?php echo my_theme_product_category_filter_search_svg(); ?>
            </span>
        </div>

        <div class="product-brand-filter__results" data-brand-filter-results>
            <?php if (empty($brands)) : ?>
                <p id="<?php echo esc_attr($brand_search_empty_id); ?>" class="product-brand-filter__empty">
                    <?php echo esc_html($has_brand_source ? __('برندی برای این دسته‌بندی یافت نشد', 'my-theme') : __('برندی برای این دسته‌بندی یافت نشد', 'my-theme')); ?>
                </p>
            <?php else : ?>
                <?php foreach ($brands as $brand) : ?>
                    <?php
                    $is_selected = in_array($brand['slug'], $selected_slugs, true);
                    ?>
                    <label class="product-brand-filter__item"
                        data-filter-option
                        data-search-text="<?php echo esc_attr($brand['name']); ?>"
                        data-brand-filter-item
                        data-brand-name="<?php echo esc_attr($brand['name']); ?>">
                        <input type="checkbox" class="product-brand-filter__input"
                            value="<?php echo esc_attr($brand['slug']); ?>"
                            <?php checked($is_selected); ?>>
                        <span class="product-brand-filter__checkbox" aria-hidden="true">
                            <svg class="product-brand-filter__check-icon" width="12" height="9"
                                viewBox="0 0 12 9" fill="none" xmlns="http://www.w3.org/2000/svg"
                                aria-hidden="true" focusable="false">
                                <path d="M11.2167 0.550781L3.88338 7.88411L0.550049 4.55078"
                                    stroke="white" stroke-width="1.1" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </span>
                        <span class="product-brand-filter__name"><?php echo esc_html($brand['name']); ?></span>
                    </label>
                <?php endforeach; ?>
                <p id="<?php echo esc_attr($brand_search_empty_id); ?>"
                    class="product-brand-filter__empty product-brand-filter__empty--search"
                    data-brand-filter-empty data-filter-search-empty hidden>
                    <?php echo esc_html__('برندی پیدا نشد', 'my-theme'); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
    return trim(ob_get_clean());
}

/**
 * Add selected brands to WooCommerce archive product queries.
 *
 * @param array $tax_query Existing WooCommerce tax query.
 * @return array
 */
function my_theme_filter_product_category_query_by_brand($tax_query) {
    if (is_admin() || ! function_exists('is_product_category') || ! is_product_category()) {
        return $tax_query;
    }

    $selected_slugs = my_theme_get_selected_product_brand_slugs();

    if (empty($selected_slugs)) {
        return $tax_query;
    }

    $brand_clauses = [];

    foreach (array_merge(my_theme_get_product_brand_taxonomies(), [my_theme_get_product_brand_filter_taxonomy()]) as $taxonomy) {
        if (! taxonomy_exists($taxonomy)) {
            continue;
        }

        $valid_slugs = [];

        foreach ($selected_slugs as $slug) {
            if (get_term_by('slug', $slug, $taxonomy) instanceof WP_Term) {
                $valid_slugs[] = $slug;
            }
        }

        if (! empty($valid_slugs)) {
            $brand_clauses[] = [
                'taxonomy' => $taxonomy,
                'field'    => 'slug',
                'terms'    => array_values(array_unique($valid_slugs)),
                'operator' => 'IN',
            ];
        }
    }

    if (empty($brand_clauses)) {
        return $tax_query;
    }

    if (1 === count($brand_clauses)) {
        $tax_query[] = $brand_clauses[0];
        return $tax_query;
    }

    $tax_query[] = array_merge(['relation' => 'OR'], $brand_clauses);

    return $tax_query;
}
add_filter('woocommerce_product_query_tax_query', 'my_theme_filter_product_category_query_by_brand');

/**
 * Return selected price values from the URL.
 *
 * @return array{min:float|null,max:float|null}
 */
function my_theme_get_selected_product_price_range() {
    $selected = [
        'min' => null,
        'max' => null,
    ];

    foreach (['min_price' => 'min', 'max_price' => 'max'] as $query_arg => $key) {
        if (! isset($_GET[$query_arg])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            continue;
        }

        $raw_value = preg_replace('/[^\d.]/', '', (string) wp_unslash($_GET[$query_arg])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        if ('' !== $raw_value && is_numeric($raw_value)) {
            $selected[$key] = max(0, (float) $raw_value);
        }
    }

    return $selected;
}

/**
 * Return whether the sale-products filter is active.
 *
 * @return bool
 */
function my_theme_is_product_sale_filter_active() {
    if (! isset($_GET['on_sale'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return false;
    }

    return '1' === sanitize_text_field(wp_unslash($_GET['on_sale'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

/**
 * Return whether the in-stock products filter is active.
 *
 * @return bool
 */
function my_theme_is_product_in_stock_filter_active() {
    if (! isset($_GET['in_stock'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return false;
    }

    return '1' === sanitize_text_field(wp_unslash($_GET['in_stock'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

/**
 * Return effective category price bounds.
 *
 * @param int $category_id Product category ID.
 * @return array{min:float,max:float}|array{}
 */
function my_theme_get_category_price_bounds($category_id) {
    $category_id = absint($category_id);

    if (0 === $category_id || ! function_exists('wc_get_product')) {
        return [];
    }

    $cache_key = implode(
        '_',
        [
            'category_price_bounds_v1',
            my_theme_get_product_category_color_cache_version(),
            $category_id,
        ]
    );
    $cached    = wp_cache_get($cache_key, 'my_theme');

    if (is_array($cached)) {
        return $cached;
    }

    $product_ids = my_theme_get_product_ids_for_product_category_color_filter($category_id);
    $prices      = [];

    foreach ($product_ids as $product_id) {
        $product = wc_get_product($product_id);

        if (! $product instanceof WC_Product) {
            continue;
        }

        if ($product->is_type('variable')) {
            $min_price = $product->get_variation_price('min', true);
            $max_price = $product->get_variation_price('max', true);

            foreach ([$min_price, $max_price] as $price) {
                if ('' !== $price && is_numeric($price)) {
                    $prices[] = (float) wc_get_price_to_display($product, ['price' => (float) $price]);
                }
            }

            continue;
        }

        $price = $product->get_price();

        if ('' !== $price && is_numeric($price)) {
            $prices[] = (float) wc_get_price_to_display($product, ['price' => (float) $price]);
        }
    }

    if (empty($prices)) {
        wp_cache_set($cache_key, [], 'my_theme', 10 * MINUTE_IN_SECONDS);
        return [];
    }

    $bounds = [
        'min' => floor(min($prices)),
        'max' => ceil(max($prices)),
    ];

    if ($bounds['min'] > $bounds['max']) {
        $bounds = [
            'min' => $bounds['max'],
            'max' => $bounds['min'],
        ];
    }

    wp_cache_set($cache_key, $bounds, 'my_theme', 10 * MINUTE_IN_SECONDS);

    return $bounds;
}

/**
 * Format a price filter number without currency.
 *
 * @param float|int $price Price.
 * @return string
 */
function my_theme_format_product_price_filter_value($price) {
    return number_format_i18n((float) $price, 0);
}

/**
 * Return a URL used by JS after a price range changes.
 *
 * @return string
 */
function my_theme_get_product_price_filter_base_url() {
    return remove_query_arg(['paged', 'product-page', 'min_price', 'max_price'], get_pagenum_link(1));
}

/**
 * Render the product category price filter block.
 *
 * @param array         $attributes Block attributes.
 * @param WP_Block|null $block      Block instance.
 * @return string
 */
function my_theme_render_product_category_price_filter_block($attributes = [], $block = null) {
    $label      = isset($attributes['label']) && '' !== trim((string) $attributes['label']) ? (string) $attributes['label'] : __('محدوده قیمت', 'my-theme');
    $min_label  = isset($attributes['minLabel']) && '' !== trim((string) $attributes['minLabel']) ? (string) $attributes['minLabel'] : __('محدوده قیمت از', 'my-theme');
    $max_label  = isset($attributes['maxLabel']) && '' !== trim((string) $attributes['maxLabel']) ? (string) $attributes['maxLabel'] : __('محدوده قیمت تا', 'my-theme');
    $category   = my_theme_get_product_category_breadcrumb_term();
    $bounds     = $category instanceof WP_Term ? my_theme_get_category_price_bounds((int) $category->term_id) : [];
    $selected   = my_theme_get_selected_product_price_range();
    $panel_id   = wp_unique_id('product-price-filter-panel-');
    $is_active  = null !== $selected['min'] || null !== $selected['max'];
    $is_open    = my_theme_single_product_is_editor_preview() || $is_active;
    $attrs      = get_block_wrapper_attributes(
        [
            'class'                 => 'product-price-filter product-filter-accordion',
            'data-filter-accordion' => '',
        ]
    );

    if (! empty($bounds)) {
        $absolute_min = (float) $bounds['min'];
        $absolute_max = (float) $bounds['max'];
        $current_min  = null !== $selected['min'] ? max($absolute_min, min((float) $selected['min'], $absolute_max)) : $absolute_min;
        $current_max  = null !== $selected['max'] ? max($absolute_min, min((float) $selected['max'], $absolute_max)) : $absolute_max;

        if ($current_min > $current_max) {
            [$current_min, $current_max] = [$current_max, $current_min];
        }
    }

    ob_start();
    ?>
<div <?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php if (! empty($bounds)) : ?>
        data-price-filter-root
        data-price-min="<?php echo esc_attr((string) $absolute_min); ?>"
        data-price-max="<?php echo esc_attr((string) $absolute_max); ?>"
    <?php endif; ?>>
    <button type="button" class="product-category-filter-card__toggle"
        aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>"
        aria-controls="<?php echo esc_attr($panel_id); ?>" data-filter-accordion-toggle>
        <span class="product-category-filter-card__toggle-label"><?php echo esc_html($label); ?></span>
        <span class="product-category-filter-card__toggle-icon" aria-hidden="true">
            <?php echo my_theme_product_category_filter_toggle_arrow_svg(); ?>
        </span>
    </button>

    <div id="<?php echo esc_attr($panel_id); ?>" class="product-price-filter__panel"
        data-filter-accordion-panel <?php echo $is_open ? '' : 'hidden'; ?>>
        <?php if (empty($bounds)) : ?>
            <p class="product-price-filter__empty">
                <?php echo esc_html__('محدوده قیمتی برای این دسته‌بندی یافت نشد', 'my-theme'); ?>
            </p>
        <?php else : ?>
            <div class="product-price-filter__fields">
                <div class="product-price-filter__field">
                    <label class="product-price-filter__label" for="<?php echo esc_attr($panel_id . '-min'); ?>">
                        <?php echo esc_html($min_label); ?>
                    </label>
                    <div class="product-price-filter__input-wrapper">
                        <input id="<?php echo esc_attr($panel_id . '-min'); ?>" type="text"
                            inputmode="numeric" autocomplete="off" class="product-price-filter__input"
                            value="<?php echo esc_attr(my_theme_format_product_price_filter_value($current_min)); ?>"
                            data-price-min-input>
                        <span class="product-price-filter__currency"><?php echo esc_html__('تومان', 'my-theme'); ?></span>
                    </div>
                </div>

                <div class="product-price-filter__field">
                    <label class="product-price-filter__label" for="<?php echo esc_attr($panel_id . '-max'); ?>">
                        <?php echo esc_html($max_label); ?>
                    </label>
                    <div class="product-price-filter__input-wrapper">
                        <input id="<?php echo esc_attr($panel_id . '-max'); ?>" type="text"
                            inputmode="numeric" autocomplete="off" class="product-price-filter__input"
                            value="<?php echo esc_attr(my_theme_format_product_price_filter_value($current_max)); ?>"
                            data-price-max-input>
                        <span class="product-price-filter__currency"><?php echo esc_html__('تومان', 'my-theme'); ?></span>
                    </div>
                </div>
            </div>

            <div class="product-price-filter__range" data-price-range>
                <span class="product-price-filter__track" aria-hidden="true"></span>
                <span class="product-price-filter__active-track" data-price-active-track aria-hidden="true"></span>
                <input type="range" class="product-price-filter__range-input product-price-filter__range-input--min"
                    min="<?php echo esc_attr((string) $absolute_min); ?>" max="<?php echo esc_attr((string) $absolute_max); ?>"
                    step="1" value="<?php echo esc_attr((string) $current_min); ?>"
                    aria-label="<?php echo esc_attr__('حداقل قیمت', 'my-theme'); ?>"
                    aria-valuemin="<?php echo esc_attr((string) $absolute_min); ?>"
                    aria-valuemax="<?php echo esc_attr((string) $absolute_max); ?>"
                    aria-valuenow="<?php echo esc_attr((string) $current_min); ?>"
                    data-price-min-range>
                <input type="range" class="product-price-filter__range-input product-price-filter__range-input--max"
                    min="<?php echo esc_attr((string) $absolute_min); ?>" max="<?php echo esc_attr((string) $absolute_max); ?>"
                    step="1" value="<?php echo esc_attr((string) $current_max); ?>"
                    aria-label="<?php echo esc_attr__('حداکثر قیمت', 'my-theme'); ?>"
                    aria-valuemin="<?php echo esc_attr((string) $absolute_min); ?>"
                    aria-valuemax="<?php echo esc_attr((string) $absolute_max); ?>"
                    aria-valuenow="<?php echo esc_attr((string) $current_max); ?>"
                    data-price-max-range>
            </div>
            <div class="product-price-filter__slider-labels">
                <span class="product-price-filter__slider-label">
                    <?php echo esc_html__('کمترین', 'my-theme'); ?><br>
                    <?php echo esc_html__('قیمت', 'my-theme'); ?>
                </span>

                <span class="product-price-filter__slider-label">
                    <?php echo esc_html__('بیشترین', 'my-theme'); ?><br>
                    <?php echo esc_html__('قیمت', 'my-theme'); ?>
                </span>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
    return trim(ob_get_clean());
}

/**
 * Add selected price range to WooCommerce product queries.
 *
 * @param array $meta_query Existing meta query.
 * @return array
 */
function my_theme_filter_product_category_query_by_price($meta_query) {
    if (is_admin() || ! function_exists('is_product_category') || ! is_product_category()) {
        return $meta_query;
    }

    $selected = my_theme_get_selected_product_price_range();

    if (null === $selected['min'] && null === $selected['max']) {
        return $meta_query;
    }

    $min = null !== $selected['min'] ? (float) $selected['min'] : 0;
    $max = null !== $selected['max'] ? (float) $selected['max'] : PHP_INT_MAX;

    if ($min > $max) {
        [$min, $max] = [$max, $min];
    }

    $meta_query[] = [
        'key'     => '_price',
        'value'   => [$min, $max],
        'compare' => 'BETWEEN',
        'type'    => 'NUMERIC',
    ];

    return $meta_query;
}
add_filter('woocommerce_product_query_meta_query', 'my_theme_filter_product_category_query_by_price');

/**
 * Limit product-category archives to WooCommerce sale products when requested.
 *
 * @param WP_Query $query WooCommerce product query.
 * @return void
 */
function my_theme_filter_product_category_query_by_sale($query) {
    if (is_admin() || ! function_exists('is_product_category') || ! is_product_category() || ! my_theme_is_product_sale_filter_active()) {
        return;
    }

    if (! function_exists('wc_get_product_ids_on_sale') || ! is_callable([$query, 'get']) || ! is_callable([$query, 'set'])) {
        return;
    }

    $sale_ids = array_values(array_filter(array_map('absint', wc_get_product_ids_on_sale())));

    if (empty($sale_ids)) {
        $sale_ids = [0];
    }

    $existing_post__in = $query->get('post__in');

    if (is_array($existing_post__in) && ! empty($existing_post__in)) {
        $sale_ids = array_values(array_intersect(array_map('absint', $existing_post__in), $sale_ids));

        if (empty($sale_ids)) {
            $sale_ids = [0];
        }
    }

    $query->set('post__in', $sale_ids);
}
add_action('woocommerce_product_query', 'my_theme_filter_product_category_query_by_sale');

/**
 * Limit product-category archives to in-stock WooCommerce products when requested.
 *
 * @param array $meta_query Existing WooCommerce meta query.
 * @return array
 */
function my_theme_filter_product_category_query_by_stock($meta_query) {
    if (is_admin() || ! function_exists('is_product_category') || ! is_product_category() || ! my_theme_is_product_in_stock_filter_active()) {
        return $meta_query;
    }

    $meta_query[] = [
        'key'     => '_stock_status',
        'value'   => 'instock',
        'compare' => '=',
    ];

    return $meta_query;
}
add_filter('woocommerce_product_query_meta_query', 'my_theme_filter_product_category_query_by_stock');

/**
 * Use newest products as the default category archive ordering.
 *
 * @param string $orderby Default orderby.
 * @return string
 */
function my_theme_product_category_default_catalog_orderby($orderby) {
    if (function_exists('is_product_category') && is_product_category()) {
        return 'date';
    }

    return $orderby;
}
add_filter('woocommerce_default_catalog_orderby', 'my_theme_product_category_default_catalog_orderby');

/**
 * Add a custom discount order option to WooCommerce catalog ordering.
 *
 * @param array<string,string> $options Ordering options.
 * @return array<string,string>
 */
function my_theme_product_category_catalog_orderby_options($options) {
    $options['discount'] = __('بیشترین تخفیف', 'my-theme');

    return $options;
}
add_filter('woocommerce_catalog_orderby', 'my_theme_product_category_catalog_orderby_options');
add_filter('woocommerce_default_catalog_orderby_options', 'my_theme_product_category_catalog_orderby_options');

/**
 * Keep WooCommerce catalog ordering valid when discount ordering is selected.
 *
 * @param array<string,mixed> $args Ordering args.
 * @return array<string,mixed>
 */
function my_theme_product_category_discount_catalog_ordering_args($args) {
    if (function_exists('is_product_category') && is_product_category() && 'discount' === my_theme_get_selected_product_category_orderby()) {
        $args['orderby'] = 'date';
        $args['order']   = 'DESC';
        $args['meta_key'] = '';
    }

    return $args;
}
add_filter('woocommerce_get_catalog_ordering_args', 'my_theme_product_category_discount_catalog_ordering_args');

/**
 * Sort product-category archives by highest discount when requested.
 *
 * @param array    $clauses Query SQL clauses.
 * @param WP_Query $query   Query instance.
 * @return array
 */
function my_theme_product_category_discount_orderby_clauses($clauses, $query) {
    global $wpdb;

    if (
        is_admin()
        || ! function_exists('is_product_category')
        || ! is_product_category()
        || 'discount' !== my_theme_get_selected_product_category_orderby()
        || ! $query instanceof WP_Query
        || ! $query->is_main_query()
    ) {
        return $clauses;
    }

    $regular_alias = 'theme_regular_price_meta';
    $price_alias   = 'theme_current_price_meta';

    if (false === strpos($clauses['join'], $regular_alias)) {
        $clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS {$regular_alias} ON ({$wpdb->posts}.ID = {$regular_alias}.post_id AND {$regular_alias}.meta_key = '_regular_price')";
    }

    if (false === strpos($clauses['join'], $price_alias)) {
        $clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS {$price_alias} ON ({$wpdb->posts}.ID = {$price_alias}.post_id AND {$price_alias}.meta_key = '_price')";
    }

    $discount_expression = "CASE WHEN CAST({$regular_alias}.meta_value AS DECIMAL(20,6)) > 0 AND CAST({$regular_alias}.meta_value AS DECIMAL(20,6)) > CAST({$price_alias}.meta_value AS DECIMAL(20,6)) THEN ((CAST({$regular_alias}.meta_value AS DECIMAL(20,6)) - CAST({$price_alias}.meta_value AS DECIMAL(20,6))) / CAST({$regular_alias}.meta_value AS DECIMAL(20,6))) ELSE 0 END";

    $clauses['orderby'] = $discount_expression . " DESC, {$wpdb->posts}.post_date DESC";

    return $clauses;
}
add_filter('posts_clauses', 'my_theme_product_category_discount_orderby_clauses', 20, 2);

/**
 * Return query arguments that belong to the product category filter UI.
 *
 * @return array<int,string>
 */
function my_theme_get_product_active_filter_query_args() {
    $args = [
        'filter_category',
        my_theme_get_product_color_filter_query_arg_name(),
        my_theme_get_product_brand_filter_query_arg(),
        'min_price',
        'max_price',
        'on_sale',
        'in_stock',
        'paged',
        'product-page',
    ];

    foreach (array_keys($_GET) as $key) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $key = sanitize_key((string) $key);

        if (0 === strpos($key, 'filter_') || 0 === strpos($key, 'query_type_')) {
            $args[] = $key;
        }
    }

    return array_values(array_unique(array_filter($args)));
}

/**
 * Return the current archive page URL with pagination reset.
 *
 * @return string
 */
function my_theme_get_product_filter_current_archive_url() {
    return remove_query_arg(['paged', 'product-page'], get_pagenum_link(1));
}

/**
 * Build a product-filter removal URL from the current archive URL.
 *
 * @param array<int,string>      $arguments_to_remove Query arguments to remove.
 * @param array<string,string>   $arguments_to_set    Query arguments to replace.
 * @return string
 */
function my_theme_get_filter_removal_url(array $arguments_to_remove, array $arguments_to_set = []) {
    $url = my_theme_get_product_filter_current_archive_url();
    $url = remove_query_arg(array_values(array_unique(array_merge($arguments_to_remove, ['paged', 'product-page']))), $url);

    foreach ($arguments_to_set as $key => $value) {
        $key   = sanitize_key((string) $key);
        $value = is_scalar($value) ? trim((string) $value) : '';

        if ('' !== $key && '' !== $value) {
            $url = add_query_arg($key, $value, $url);
        }
    }

    return $url;
}

/**
 * Resolve a selected product category query filter.
 *
 * The archive category itself is intentionally preserved and is not treated as
 * a removable active filter unless an explicit filter_category query exists.
 *
 * @return WP_Term|null
 */
function my_theme_get_selected_product_category_filter_term() {
    if (empty($_GET['filter_category'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return null;
    }

    $raw_value = sanitize_text_field(wp_unslash($_GET['filter_category'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $term      = is_numeric($raw_value)
        ? get_term(absint($raw_value), 'product_cat')
        : get_term_by('slug', sanitize_title($raw_value), 'product_cat');

    return $term instanceof WP_Term && 'product_cat' === $term->taxonomy ? $term : null;
}

/**
 * Return the first valid term for a selected brand slug.
 *
 * @param string $slug Brand slug.
 * @return WP_Term|null
 */
function my_theme_get_product_brand_filter_term_by_slug($slug) {
    $slug = sanitize_title($slug);

    if ('' === $slug) {
        return null;
    }

    foreach (array_merge(my_theme_get_product_brand_taxonomies(), [my_theme_get_product_brand_filter_taxonomy()]) as $taxonomy) {
        if (! taxonomy_exists($taxonomy)) {
            continue;
        }

        $term = get_term_by('slug', $slug, $taxonomy);

        if ($term instanceof WP_Term) {
            return $term;
        }
    }

    return null;
}

/**
 * Build a URL for removing one selected brand while preserving other brands.
 *
 * @param string            $slug           Brand slug to remove.
 * @param array<int,string> $selected_slugs Current selected brand slugs.
 * @return string
 */
function my_theme_get_product_brand_chip_removal_url($slug, array $selected_slugs) {
    $query_arg = my_theme_get_product_brand_filter_query_arg();
    $remaining = array_values(array_diff(array_map('sanitize_title', $selected_slugs), [sanitize_title($slug)]));
    $remaining = array_values(array_unique(array_filter($remaining)));

    if (empty($remaining)) {
        return my_theme_get_filter_removal_url([$query_arg]);
    }

    return my_theme_get_filter_removal_url([$query_arg], [$query_arg => implode(',', $remaining)]);
}

/**
 * Return a readable label for a WooCommerce attribute filter query argument.
 *
 * @param string $query_arg Attribute filter query argument.
 * @return string
 */
function my_theme_get_product_attribute_filter_label($query_arg) {
    $attribute_slug = preg_replace('/^filter_/', '', sanitize_key($query_arg));
    $taxonomy       = taxonomy_exists($attribute_slug) ? $attribute_slug : 'pa_' . $attribute_slug;

    if (taxonomy_exists($taxonomy) && function_exists('wc_attribute_label')) {
        return wc_attribute_label($taxonomy);
    }

    return str_replace(['pa_', '_', '-'], ['', ' ', ' '], $attribute_slug);
}

/**
 * Resolve a selected WooCommerce attribute filter value to a readable name.
 *
 * @param string $query_arg Attribute filter query argument.
 * @param string $slug      Selected value slug.
 * @return string
 */
function my_theme_get_product_attribute_filter_value_label($query_arg, $slug) {
    $slug           = sanitize_title($slug);
    $attribute_slug = preg_replace('/^filter_/', '', sanitize_key($query_arg));
    $taxonomy       = taxonomy_exists($attribute_slug) ? $attribute_slug : 'pa_' . $attribute_slug;

    if (taxonomy_exists($taxonomy)) {
        $term = get_term_by('slug', $slug, $taxonomy);

        if ($term instanceof WP_Term) {
            return $term->name;
        }
    }

    return '';
}

/**
 * Return server-resolved active product filters for the category filter card.
 *
 * @return array<int,array{type:string,label:string,remove_url:string}>
 */
function my_theme_get_active_product_filters() {
    $filters = [];

    $category = my_theme_get_selected_product_category_filter_term();

    if ($category instanceof WP_Term) {
        $filters[] = [
            'type'       => 'category',
            'label'      => sprintf(__('دسته‌بندی: %s', 'my-theme'), $category->name),
            'remove_url' => my_theme_get_filter_removal_url(['filter_category']),
        ];
    }

    $selected_price = my_theme_get_selected_product_price_range();

    if (null !== $selected_price['min'] || null !== $selected_price['max']) {
        if (null !== $selected_price['min'] && null !== $selected_price['max']) {
            $price_label = sprintf(
                __('قیمت: از %1$s تا %2$s', 'my-theme'),
                my_theme_format_product_price_filter_value($selected_price['min']),
                my_theme_format_product_price_filter_value($selected_price['max'])
            );
        } elseif (null !== $selected_price['min']) {
            $price_label = sprintf(
                __('قیمت: از %s', 'my-theme'),
                my_theme_format_product_price_filter_value($selected_price['min'])
            );
        } else {
            $price_label = sprintf(
                __('قیمت: تا %s', 'my-theme'),
                my_theme_format_product_price_filter_value($selected_price['max'])
            );
        }

        $filters[] = [
            'type'       => 'price',
            'label'      => $price_label,
            'remove_url' => my_theme_get_filter_removal_url(['min_price', 'max_price']),
        ];
    }

    if (my_theme_is_product_sale_filter_active()) {
        $filters[] = [
            'type'       => 'sale',
            'label'      => __('کالاهای تخفیف‌دار', 'my-theme'),
            'remove_url' => my_theme_get_filter_removal_url(['on_sale']),
        ];
    }

    if (my_theme_is_product_in_stock_filter_active()) {
        $filters[] = [
            'type'       => 'stock',
            'label'      => __('کالاهای موجود', 'my-theme'),
            'remove_url' => my_theme_get_filter_removal_url(['in_stock']),
        ];
    }

    $color_taxonomy = my_theme_get_product_color_taxonomy();
    $selected_color = my_theme_get_selected_product_color_term($color_taxonomy);

    if ($selected_color instanceof WP_Term) {
        $filters[] = [
            'type'       => 'color',
            'label'      => sprintf(__('رنگ: %s', 'my-theme'), $selected_color->name),
            'remove_url' => my_theme_get_filter_removal_url([my_theme_get_product_color_filter_query_arg_name()]),
        ];
    }

    $selected_brand_slugs = my_theme_get_selected_product_brand_slugs();

    foreach ($selected_brand_slugs as $brand_slug) {
        $brand_term = my_theme_get_product_brand_filter_term_by_slug($brand_slug);

        if (! $brand_term instanceof WP_Term) {
            continue;
        }

        $filters[] = [
            'type'       => 'brand',
            'label'      => sprintf(__('برند: %s', 'my-theme'), $brand_term->name),
            'remove_url' => my_theme_get_product_brand_chip_removal_url($brand_slug, $selected_brand_slugs),
        ];
    }

    $reserved_args = [
        'filter_category',
        my_theme_get_product_color_filter_query_arg_name(),
        my_theme_get_product_brand_filter_query_arg(),
        'min_price',
        'max_price',
        'on_sale',
        'in_stock',
    ];

    foreach ($_GET as $query_arg => $raw_value) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $query_arg = sanitize_key((string) $query_arg);

        if (0 !== strpos($query_arg, 'filter_') || in_array($query_arg, $reserved_args, true)) {
            continue;
        }

        if (! is_scalar($raw_value)) {
            continue;
        }

        $values = array_filter(array_unique(array_map('sanitize_title', explode(',', (string) wp_unslash($raw_value)))));

        foreach ($values as $value) {
            $value_label = my_theme_get_product_attribute_filter_value_label($query_arg, $value);

            if ('' === $value_label) {
                continue;
            }

            $remaining = array_values(array_diff($values, [$value]));
            $remove_url = empty($remaining)
                ? my_theme_get_filter_removal_url([$query_arg, 'query_type_' . preg_replace('/^filter_/', '', $query_arg)])
                : my_theme_get_filter_removal_url([$query_arg], [$query_arg => implode(',', $remaining)]);

            $filters[] = [
                'type'       => 'attribute',
                'label'      => sprintf(
                    /* translators: 1: attribute label, 2: selected attribute value. */
                    __('%1$s: %2$s', 'my-theme'),
                    my_theme_get_product_attribute_filter_label($query_arg),
                    $value_label
                ),
                'remove_url' => $remove_url,
            ];
        }
    }

    return $filters;
}

/**
 * Render the active filter chips for the product category filter card.
 *
 * @return string
 */
function my_theme_render_product_active_filters() {
    $filters = my_theme_get_active_product_filters();

    if (empty($filters)) {
        return '';
    }

    $is_editor     = my_theme_single_product_is_editor_preview();
    $clear_all_url = my_theme_get_filter_removal_url(my_theme_get_product_active_filter_query_args());

    ob_start();
    ?>
<div class="product-active-filters" data-active-filters>
    <div class="product-active-filters__header">
        <span class="product-active-filters__title">
            <?php echo esc_html__('فیلترهای اعمال شده', 'my-theme'); ?>
        </span>

        <a class="product-active-filters__clear-all"
            aria-label="<?php echo esc_attr__('حذف همه فیلترها', 'my-theme'); ?>"
            href="<?php echo esc_url($is_editor ? '#' : $clear_all_url); ?>">
            <span><?php echo esc_html__('حذف همه', 'my-theme'); ?></span>
            <span class="product-active-filters__clear-all-icon" aria-hidden="true">
                <?php echo my_theme_product_active_filter_remove_svg('green'); ?>
            </span>
        </a>
    </div>

    <div class="product-active-filters__items">
        <?php foreach ($filters as $filter) : ?>
            <a class="product-active-filters__chip product-active-filters__chip--<?php echo esc_attr($filter['type']); ?>"
                aria-label="<?php echo esc_attr(sprintf(__('حذف فیلتر %s', 'my-theme'), $filter['label'])); ?>"
                href="<?php echo esc_url($is_editor ? '#' : $filter['remove_url']); ?>">
                <span class="product-active-filters__chip-label">
                    <?php echo esc_html($filter['label']); ?>
                </span>
                <span class="product-active-filters__remove-icon" aria-hidden="true">
                    <?php echo my_theme_product_active_filter_remove_svg(); ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php
    return trim(ob_get_clean());
}

/**
 * Render direct sale/in-stock toggle filters below the price section.
 *
 * @return string
 */
function my_theme_render_product_binary_filters() {
    $is_on_sale_active  = my_theme_is_product_sale_filter_active();
    $is_in_stock_active = my_theme_is_product_in_stock_filter_active();

    ob_start();
    ?>
<div class="product-binary-filters" data-product-binary-filters>
    <label class="product-binary-filter">
        <span class="product-binary-filter__label">
            <?php echo esc_html__('کالاهای تخفیف‌دار', 'my-theme'); ?>
        </span>

        <input type="checkbox" class="product-binary-filter__input" name="on_sale" value="1"
            data-product-binary-filter data-filter-parameter="on_sale"
            <?php checked($is_on_sale_active); ?>>

        <span class="product-binary-filter__switch" aria-hidden="true">
            <span class="product-binary-filter__thumb"></span>
        </span>
    </label>

    <label class="product-binary-filter">
        <span class="product-binary-filter__label">
            <?php echo esc_html__('کالاهای موجود', 'my-theme'); ?>
        </span>

        <input type="checkbox" class="product-binary-filter__input" name="in_stock" value="1"
            data-product-binary-filter data-filter-parameter="in_stock"
            <?php checked($is_in_stock_active); ?>>

        <span class="product-binary-filter__switch" aria-hidden="true">
            <span class="product-binary-filter__thumb"></span>
        </span>
    </label>
</div>
<?php
    return trim(ob_get_clean());
}

/**
 * Return product-category archive sorting options.
 *
 * @return array<string,string>
 */
function my_theme_get_product_category_sort_options() {
    return [
        'popularity' => __('پرفروش‌ترین', 'my-theme'),
        'date'       => __('جدیدترین', 'my-theme'),
        'price'      => __('ارزان‌ترین', 'my-theme'),
        'discount'   => __('بیشترین تخفیف', 'my-theme'),
    ];
}

/**
 * Return selected archive ordering key.
 *
 * @return string
 */
function my_theme_get_selected_product_category_orderby() {
    $options = my_theme_get_product_category_sort_options();
    $orderby = isset($_GET['orderby']) ? sanitize_key(wp_unslash($_GET['orderby'])) : 'date'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

    return isset($options[$orderby]) ? $orderby : 'date';
}

/**
 * Build a sorting URL while preserving active filters and resetting pagination.
 *
 * @param string $orderby Ordering key.
 * @return string
 */
function my_theme_get_product_category_sort_url($orderby) {
    $orderby = sanitize_key($orderby);
    $url     = remove_query_arg(['paged', 'product-page'], get_pagenum_link(1));

    if ('date' === $orderby) {
        return remove_query_arg('orderby', $url);
    }

    return add_query_arg('orderby', $orderby, $url);
}

/**
 * Return sorting icon SVG.
 *
 * @return string
 */
function my_theme_product_category_sort_icon_svg() {
    return <<<'SVG'
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <path d="M21 19H3M9 12H21M15 5H21" stroke="#1A1E1B" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
SVG;
}

/**
 * Return the query object used for product-category archive results.
 *
 * @return WP_Query
 */
function my_theme_get_product_category_results_query() {
    global $wp_query;

    if (! my_theme_single_product_is_editor_preview() && $wp_query instanceof WP_Query) {
        return $wp_query;
    }

    $category = my_theme_get_product_category_breadcrumb_term();
    $args     = [
        'post_type'              => 'product',
        'post_status'            => 'publish',
        'posts_per_page'         => 20,
        'no_found_rows'          => false,
        'ignore_sticky_posts'    => true,
        'update_post_meta_cache' => true,
        'update_post_term_cache' => true,
    ];

    if ($category instanceof WP_Term) {
        $args['tax_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
            [
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => [(int) $category->term_id],
            ],
        ];
    }

    return new WP_Query($args);
}

/**
 * Return the number of products displayed on WooCommerce archive pages.
 *
 * @return int
 */
function my_theme_product_archive_products_per_page() {
    return 20;
}
add_filter('loop_shop_per_page', 'my_theme_product_archive_products_per_page', 20);

/**
 * Limit WooCommerce product archive main queries to 20 products per page.
 *
 * @param WP_Query $query Query object.
 * @return void
 */
function my_theme_product_archive_posts_per_page($query) {
    if (
        is_admin()
        || ! $query instanceof WP_Query
        || ! $query->is_main_query()
    ) {
        return;
    }

    $is_product_archive = false;

    if (function_exists('is_shop') && is_shop()) {
        $is_product_archive = true;
    }

    if (function_exists('is_product_taxonomy') && is_product_taxonomy()) {
        $is_product_archive = true;
    }

    if (! $is_product_archive) {
        return;
    }

    $query->set('posts_per_page', my_theme_product_archive_products_per_page());
}
add_action('pre_get_posts', 'my_theme_product_archive_posts_per_page', 20);

/**
 * Apply archive-scoped product search without leaving the current archive.
 *
 * @param WP_Query $query Query object.
 * @return void
 */
function my_theme_product_archive_apply_product_search($query) {
    if (
        is_admin()
        || ! $query instanceof WP_Query
        || ! $query->is_main_query()
        || ! my_theme_is_product_archive_context()
        || empty($_GET['product_search']) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    ) {
        return;
    }

    $search = sanitize_text_field(wp_unslash($_GET['product_search'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $search = trim($search);

    if ('' === $search) {
        return;
    }

    $query->set('s', $search);
}
add_action('pre_get_posts', 'my_theme_product_archive_apply_product_search', 25);

/**
 * Return product archive pagination arrow SVG.
 *
 * @param string $direction Arrow direction. Accepts 'next' or 'prev'.
 * @return string
 */
function my_theme_product_category_pagination_arrow_svg($direction) {
    if ('prev' === $direction) {
        return <<<'SVG'
<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <path d="M6 12L10 8L6 4" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
SVG;
    }

    return <<<'SVG'
<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <path d="M10 12L6 8L10 4" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
SVG;
}

/**
 * Render the product-category archive toolbar.
 *
 * @param WP_Query $query Product results query.
 * @return string
 */
function my_theme_render_product_category_toolbar($query) {
    $query          = $query instanceof WP_Query ? $query : my_theme_get_product_category_results_query();
    $active_orderby = my_theme_get_selected_product_category_orderby();
    $is_editor      = my_theme_single_product_is_editor_preview();
    $count          = isset($query->found_posts) ? (int) $query->found_posts : 0;
    $options        = my_theme_get_product_category_sort_options();

    ob_start();
    ?>
<div class="product-category-toolbar">
    <div class="product-category-toolbar__sorting">
        <span class="product-category-toolbar__sorting-heading">
            <span class="product-category-toolbar__sorting-icon" aria-hidden="true">
                <?php echo my_theme_product_category_sort_icon_svg(); ?>
            </span>
            <span><?php echo esc_html__('مرتب‌سازی:', 'my-theme'); ?></span>
        </span>

        <nav class="product-category-toolbar__options" aria-label="<?php echo esc_attr__('مرتب‌سازی محصولات', 'my-theme'); ?>">
            <?php foreach ($options as $orderby => $label) : ?>
                <?php $is_active = $orderby === $active_orderby; ?>
                <a class="product-category-toolbar__option"
                    href="<?php echo esc_url($is_editor ? '#' : my_theme_get_product_category_sort_url($orderby)); ?>"
                    data-orderby="<?php echo esc_attr($orderby); ?>"
                    <?php echo $is_active ? 'aria-current="page"' : ''; ?>>
                    <?php echo esc_html($label); ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <div class="product-category-toolbar__count">
        <?php echo esc_html(sprintf(__('%s کالا', 'my-theme'), number_format_i18n($count))); ?>
    </div>
</div>
<?php
    return trim(ob_get_clean());
}

/**
 * Render pagination for product-category archive results.
 *
 * @param WP_Query $query Product results query.
 * @return string
 */
function my_theme_render_product_category_pagination($query) {
    if (! $query instanceof WP_Query) {
        return '';
    }

    $per_page = (int) $query->get('posts_per_page');
    if ($per_page <= 0) {
        $per_page = my_theme_product_archive_products_per_page();
    }

    // WooCommerce's block archive can leave max_num_pages unset on /shop
    // even though found_posts contains the full result count. Calculate a
    // reliable fallback so the existing archive pagination also renders there.
    $calculated_pages = $per_page > 0
        ? (int) ceil(max(0, (int) $query->found_posts) / $per_page)
        : 0;
    $total = max((int) $query->max_num_pages, $calculated_pages);

    if ($total <= 1) {
        return '';
    }

    $current = max(
        1,
        (int) get_query_var('paged'),
        (int) get_query_var('product-page'),
        (int) $query->get('paged')
    );
    $links   = paginate_links(
        [
            'total'     => $total,
            'current'   => $current,
            'type'      => 'list',
            'prev_text' => '<span class="screen-reader-text">' . esc_html__('صفحه قبلی', 'my-theme') . '</span>' . my_theme_product_category_pagination_arrow_svg('prev'),
            'next_text' => '<span class="screen-reader-text">' . esc_html__('صفحه بعدی', 'my-theme') . '</span>' . my_theme_product_category_pagination_arrow_svg('next'),
        ]
    );

    if (! is_string($links) || '' === $links) {
        return '';
    }

    $links = str_replace('class="prev page-numbers"', 'class="prev page-numbers product-category-results__pagination-link product-category-results__pagination-link--prev"', $links);
    $links = str_replace('class="next page-numbers"', 'class="next page-numbers product-category-results__pagination-link product-category-results__pagination-link--next"', $links);

    return '<nav class="product-category-results__pagination" aria-label="' . esc_attr__('صفحه‌بندی محصولات', 'my-theme') . '">' . $links . '</nav>';
}

/**
 * Render the product-category archive grid/content area.
 *
 * @param array         $attributes Block attributes.
 * @param string        $content    Block content.
 * @param WP_Block|null $block      Block instance.
 * @return string
 */
function my_theme_render_product_category_content_block($attributes = [], $content = '', $block = null) {
    $query = my_theme_get_product_category_results_query();
    $attrs = get_block_wrapper_attributes(['class' => 'product-category-layout__content product-category-results']);

    ob_start();
    ?>
<div <?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <?php echo my_theme_render_product_category_toolbar($query); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

    <?php if ($query->have_posts() && function_exists('my_theme_render_green_product_card')) : ?>
        <div class="product-category-results__grid greenProductSwiper" dir="rtl">
            <?php
            foreach ($query->posts as $post) {
                $product_id = $post instanceof WP_Post ? (int) $post->ID : absint($post);
                $product    = wc_get_product($product_id);

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
                    ]
                );
            }
            ?>
        </div>

        <?php echo my_theme_render_product_category_pagination($query); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php else : ?>
        <div class="product-category-results__empty">
            <?php wc_no_products_found(); ?>
        </div>
    <?php endif; ?>
</div>
<?php

    if (my_theme_single_product_is_editor_preview() && $query instanceof WP_Query) {
        wp_reset_postdata();
    }

    return trim(ob_get_clean());
}

/**
 * Render the product category filter card block.
 *
 * @param array         $attributes Block attributes.
 * @param WP_Block|null $block      Block instance.
 * @return string
 */
function my_theme_render_product_category_filter_card_block($attributes = [], $content = '', $block = null) {
    $title          = isset($attributes['title']) && '' !== trim((string) $attributes['title']) ? (string) $attributes['title'] : __('فیلتر', 'my-theme');
    $category_label = isset($attributes['categoryLabel']) && '' !== trim((string) $attributes['categoryLabel']) ? (string) $attributes['categoryLabel'] : __('دسته‌بندی', 'my-theme');
    $terms          = my_theme_get_product_category_filter_terms();
    $inner_content  = '' !== trim($content) ? $content : (my_theme_single_product_is_editor_preview() ? '' : do_blocks('<!-- wp:theme/product-category-color-filter /--><!-- wp:theme/product-category-brand-filter /--><!-- wp:theme/product-category-price-filter /-->'));
    $panel_id       = wp_unique_id('product-category-filter-panel-');
    $is_editor      = my_theme_single_product_is_editor_preview();
    $wrapper_attrs  = get_block_wrapper_attributes(
        [
            'class' => 'product-category-filter-card',
        ]
    );

    ob_start();
    ?>
<aside <?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    data-product-category-filter-card>
    <h2 class="product-category-filter-card__title">
        <?php echo esc_html($title); ?>
    </h2>

    <?php echo my_theme_render_product_active_filters(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

    <div class="product-category-filter-card__section product-filter-accordion" data-filter-accordion>
        <button type="button" class="product-category-filter-card__toggle"
            aria-expanded="<?php echo $is_editor ? 'true' : 'false'; ?>"
            aria-controls="<?php echo esc_attr($panel_id); ?>" data-category-filter-toggle>
            <span class="product-category-filter-card__toggle-label">
                <?php echo esc_html($category_label); ?>
            </span>

            <span class="product-category-filter-card__toggle-icon" aria-hidden="true">
                <?php echo my_theme_product_category_filter_toggle_arrow_svg(); ?>
            </span>
        </button>

        <div id="<?php echo esc_attr($panel_id); ?>" class="product-category-filter-card__panel"
            data-category-filter-panel <?php echo $is_editor ? '' : 'hidden'; ?>>
            <?php $category_search_empty_id = wp_unique_id('product-category-filter-empty-'); ?>
            <div class="product-category-filter-card__search">
                <input type="search" class="product-category-filter-card__search-input"
                    placeholder="<?php echo esc_attr__('جستجو دسته‌بندی', 'my-theme'); ?>"
                    aria-label="<?php echo esc_attr__('Ø¬Ø³ØªØ¬Ùˆ Ø¯Ø³ØªÙ‡â€ŒØ¨Ù†Ø¯ÛŒ', 'my-theme'); ?>"
                    aria-describedby="<?php echo esc_attr($category_search_empty_id); ?>"
                    autocomplete="off"
                    data-category-filter-search
                    data-filter-option-search
                    data-filter-target="category">

                <span class="product-category-filter-card__search-icon" aria-hidden="true">
                    <?php echo my_theme_product_category_filter_search_svg(); ?>
                </span>
            </div>

            <div class="product-category-filter-card__results" data-category-filter-results>
                <?php foreach ($terms as $term) : ?>
                    <?php
                    $term_link = get_term_link($term);

                    if (is_wp_error($term_link)) {
                        continue;
                    }
                    ?>
                    <a class="product-category-filter-card__item"
                        href="<?php echo esc_url($term_link); ?>"
                        data-filter-option
                        data-search-text="<?php echo esc_attr($term->name); ?>"
                        data-category-filter-item
                        data-category-filter-name="<?php echo esc_attr($term->name); ?>"
                        data-category-name="<?php echo esc_attr($term->name); ?>">
                        <span class="product-category-filter-card__item-icon" aria-hidden="true">
                            <?php echo my_theme_product_category_filter_item_arrow_svg(); ?>
                        </span>
                        <span class="product-category-filter-card__item-label">
                            <?php echo esc_html($term->name); ?>
                        </span>
                    </a>
                <?php endforeach; ?>

                <p id="<?php echo esc_attr($category_search_empty_id); ?>"
                    class="product-category-filter-card__empty"
                    data-category-filter-empty data-filter-search-empty <?php echo empty($terms) ? '' : 'hidden'; ?>>
                    <?php echo esc_html__('دسته‌بندی‌ای پیدا نشد', 'my-theme'); ?>
                </p>
            </div>
        </div>
    </div>

    <?php echo $inner_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

    <?php echo my_theme_render_product_binary_filters(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</aside>
<?php
    return trim(ob_get_clean());
}

/**
 * Return the block template markup used inside the dynamic single-product block.
 *
 * @return string
 */
function my_theme_single_product_content_block_template() {
    return <<<'HTML'
<!-- wp:theme/product-breadcrumb /-->
<!-- wp:theme/product-main -->
    <!-- wp:theme/product-gallery /-->
    <!-- wp:theme/product-information -->
        <!-- wp:theme/product-overview --><!-- wp:theme/product-title /--><!-- wp:theme/product-rating /--><!-- wp:theme/product-attributes /--><!-- /wp:theme/product-overview -->
        <!-- wp:theme/product-actions --><!-- wp:theme/product-wishlist /--><!-- wp:theme/product-share /--><!-- /wp:theme/product-actions -->
        <!-- wp:theme/product-meta --><!-- wp:theme/product-category-meta /--><!-- wp:theme/product-brand-meta /--><!-- /wp:theme/product-meta -->
    <!-- /wp:theme/product-information -->
    <!-- wp:theme/product-purchase -->
        <!-- wp:theme/product-purchase-info --><!-- wp:theme/product-guarantee /--><!-- wp:theme/product-weight /--><!-- /wp:theme/product-purchase-info -->
        <!-- wp:theme/product-price /-->
        <!-- wp:theme/product-purchase-actions --><!-- wp:theme/product-add-to-cart /--><!-- wp:theme/product-bulk-order /--><!-- /wp:theme/product-purchase-actions -->
        <!-- wp:theme/product-shipping /-->
    <!-- /wp:theme/product-purchase -->
<!-- /wp:theme/product-main -->
<!-- wp:theme/product-benefits -->
    <!-- wp:theme/product-benefit-item {"icon":"delivery","title":"ارسال سریع","description":"با هماهنگی قبلی"} /-->
    <!-- wp:theme/product-benefit-item {"icon":"return","title":"ضمانت مرجوعی","description":"تا ۷ روز کاری"} /-->
    <!-- wp:theme/product-benefit-item {"icon":"authenticity","title":"تضمین اصالت","description":"از بهترین برندها"} /-->
    <!-- wp:theme/product-benefit-item {"icon":"consultation","title":"مشاوره تخصصی","description":"قبل و بعد از خرید"} /-->
<!-- /wp:theme/product-benefits -->
<!-- wp:theme/product-details /-->
<!-- wp:theme/product-similar-products /-->
<!-- wp:theme/product-related-products /-->
HTML;
}

/**
 * Render the complete WooCommerce single-product page for frontend and editor.
 *
 * @param array         $attributes Block attributes.
 * @param string        $content    Block content.
 * @param WP_Block|null $block      Block instance.
 * @return string
 */
function my_theme_render_single_product_content_block($attributes = [], $content = '', $block = null) {
    $product = my_theme_single_product_get_current_product($block);

    if (! $product instanceof WC_Product) {
        if (my_theme_single_product_is_editor_preview()) {
            return '<div class="single-product-editor-placeholder" role="note">' . esc_html__('برای پیش‌نمایش قالب محصول، ابتدا یک محصول منتشرشده ایجاد کنید.', 'my-theme') . '</div>';
        }

        return '';
    }

    global $post;

    $previous_post    = $post;
    $previous_product = $GLOBALS['product'] ?? null;
    $product_post     = get_post($product->get_id());

    $GLOBALS['product'] = $product;

    if ($product_post instanceof WP_Post) {
        $post = $product_post;
        setup_postdata($post);
    }

    $inner_content = '' !== trim($content) ? $content : do_blocks(my_theme_single_product_content_block_template());
    $output        = '<main class="wp-block-group alignfull single-product-page" data-single-product-root>' . $inner_content . '</main>';

    if ($product_post instanceof WP_Post) {
        wp_reset_postdata();
    }

    $post = $previous_post;

    if ($previous_product instanceof WC_Product) {
        $GLOBALS['product'] = $previous_product;
    } else {
        unset($GLOBALS['product']);
    }

    return $output;
}

/**
 * Render a section-level single-product dynamic block.
 *
 * @param string        $section Section key.
 * @param WP_Block|null $block   Block instance.
 * @return string
 */
function my_theme_render_single_product_section_block($section, $block = null, $content = '', $attributes = []) {
    if ($block instanceof WP_Block) { $attributes = array_merge($block->attributes, $attributes); }
    $product = my_theme_single_product_get_current_product($block);

    if (! $product instanceof WC_Product && ! in_array($section, ['benefits'], true)) {
        return my_theme_single_product_is_editor_preview()
            ? '<div class="single-product-editor-placeholder" role="note">' . esc_html__('برای پیش‌نمایش قالب محصول، ابتدا یک محصول منتشرشده ایجاد کنید.', 'my-theme') . '</div>'
            : '';
    }

    global $post;

    $previous_post    = $post;
    $previous_product = $GLOBALS['product'] ?? null;
    $product_post     = $product instanceof WC_Product ? get_post($product->get_id()) : null;

    if ($product instanceof WC_Product) {
        $GLOBALS['product'] = $product;
    }

    if ($product_post instanceof WP_Post) {
        $post = $product_post;
        setup_postdata($post);
    }

    switch ($section) {
        case 'breadcrumb':
            $output = do_blocks('<!-- wp:woocommerce/breadcrumbs {"fontSize":"base","style":{"typography":{"fontWeight":"400"}},"className":"single-product-breadcrumb"} /-->');
            break;

        case 'main':
            $inner = '' !== trim($content) ? $content : do_blocks(
                '<!-- wp:theme/product-gallery /--><!-- wp:theme/product-information --><!-- wp:theme/product-overview --><!-- wp:theme/product-title /--><!-- wp:theme/product-rating /--><!-- wp:theme/product-attributes /--><!-- /wp:theme/product-overview --><!-- wp:theme/product-actions --><!-- wp:theme/product-wishlist /--><!-- wp:theme/product-share /--><!-- /wp:theme/product-actions --><!-- wp:theme/product-meta --><!-- wp:theme/product-category-meta /--><!-- wp:theme/product-brand-meta /--><!-- /wp:theme/product-meta --><!-- /wp:theme/product-information --><!-- wp:theme/product-purchase --><!-- wp:theme/product-purchase-info --><!-- wp:theme/product-guarantee /--><!-- wp:theme/product-weight /--><!-- /wp:theme/product-purchase-info --><!-- wp:theme/product-price /--><!-- wp:theme/product-purchase-actions --><!-- wp:theme/product-add-to-cart /--><!-- wp:theme/product-bulk-order /--><!-- /wp:theme/product-purchase-actions --><!-- wp:theme/product-shipping /--><!-- /wp:theme/product-purchase -->'
            );
            $output = '<div class="wp-block-group single-product-section">' . $inner . '</div>';
            break;

        case 'gallery':
            $output = '<div class="wp-block-group single-product-section__gallery">'
                . do_blocks('<!-- wp:woocommerce/product-image-gallery {"className":"single-product-gallery"} /-->')
                . '</div>';
            break;

        case 'information':
            $inner = '' !== trim($content) ? $content : do_blocks(
                '<!-- wp:theme/product-overview --><!-- wp:theme/product-title /--><!-- wp:theme/product-rating /--><!-- wp:theme/product-attributes /--><!-- /wp:theme/product-overview --><!-- wp:theme/product-actions --><!-- wp:theme/product-wishlist /--><!-- wp:theme/product-share /--><!-- /wp:theme/product-actions --><!-- wp:theme/product-meta --><!-- wp:theme/product-category-meta /--><!-- wp:theme/product-brand-meta /--><!-- /wp:theme/product-meta -->'
            );
            $output = '<div class="wp-block-group single-product-section__details">'
                . $inner
                . '</div>';
            break;

        case 'overview':
            $inner = '' !== trim($content) ? $content : do_blocks('<!-- wp:theme/product-title /--><!-- wp:theme/product-rating /--><!-- wp:theme/product-attributes /-->');
            $output = $inner;
            break;
        case 'title':
            $output = my_theme_single_product_render_details_header($product, false, $attributes, 'title');
            break;
        case 'rating':
            $output = my_theme_single_product_render_details_header($product, false, $attributes, 'rating');
            if ('' === $output && my_theme_single_product_is_editor_preview()) {
                $output = '<div class="single-product-rating-row" role="note"><span class="single-product-rating-row__star">' . my_theme_single_product_rating_star_svg() . '</span><span class="single-product-rating-row__count">' . esc_html__('هنوز امتیازی ثبت نشده است.', 'my-theme') . '</span></div>';
            }
            break;
        case 'attributes':
            $output = my_theme_single_product_render_details_header($product, false, $attributes, 'attributes');
            break;

        case 'actions':
            $inner = '' !== trim($content) ? $content : do_blocks('<!-- wp:theme/product-wishlist /--><!-- wp:theme/product-share /-->');
            $output = '<div class="single-product-actions-row">' . $inner . '</div>';
            break;
        case 'wishlist':
            $output = my_theme_single_product_render_actions_row($product, $attributes, 'wishlist');
            break;
        case 'share':
            $output = my_theme_single_product_render_actions_row($product, $attributes, 'share');
            break;

        case 'meta':
            $inner = '' !== trim($content) ? $content : do_blocks('<!-- wp:theme/product-category-meta /--><!-- wp:theme/product-brand-meta /-->');
            $output = '<div class="single-product-meta-summary">' . $inner . '</div>';
            break;
        case 'category-meta':
            $output = my_theme_single_product_render_meta_summary($product, $attributes, 'category');
            break;
        case 'brand-meta':
            $output = my_theme_single_product_render_meta_summary($product, $attributes, 'brand');
            if ('' === $output && my_theme_single_product_is_editor_preview()) {
                $output = '<div class="single-product-meta-summary__row" role="note"><span class="single-product-meta-summary__label">' . esc_html(sanitize_text_field($attributes['brandLabel'] ?? __('برند:', 'my-theme'))) . '</span><span class="single-product-meta-summary__value">' . esc_html__('برای این محصول ثبت نشده است.', 'my-theme') . '</span></div>';
            }
            break;

        case 'purchase':
            $inner = '' !== trim($content) ? $content : do_blocks(
                '<!-- wp:theme/product-purchase-info --><!-- wp:theme/product-guarantee /--><!-- wp:theme/product-weight /--><!-- /wp:theme/product-purchase-info --><!-- wp:theme/product-price /--><!-- wp:theme/product-purchase-actions --><!-- wp:theme/product-add-to-cart /--><!-- wp:theme/product-bulk-order /--><!-- /wp:theme/product-purchase-actions --><!-- wp:theme/product-shipping /-->'
            );
            $output = '<div class="wp-block-group single-product-section__purchase">'
                . '<aside class="single-product-purchase-card">' . $inner . '</aside>'
                . '</div>';
            break;

        case 'purchase-info':
            $inner = '' !== trim($content) ? $content : do_blocks(
                '<!-- wp:theme/product-guarantee /--><!-- wp:theme/product-weight /-->'
            );
            $output = '<div class="single-product-purchase-card__info-list">' . $inner . '</div>';
            break;

        case 'guarantee':
            $label = sanitize_text_field($attributes['label'] ?? __('گارانتی اصالت و سلامت فیزیکی کالا', 'my-theme'));
            $output = '<div class="single-product-purchase-card__info-row single-product-purchase-card__info-row--guarantee">'
                . '<span class="single-product-purchase-card__info-icon" aria-hidden="true">' . my_theme_single_product_guarantee_svg() . '</span>'
                . '<span class="single-product-purchase-card__info-text">' . esc_html($label) . '</span></div>';
            break;

        case 'weight':
            $weight = my_theme_single_product_get_formatted_weight($product);
            $output = '' === $weight ? '' : '<div class="single-product-purchase-card__info-row single-product-purchase-card__info-row--weight">'
                . '<span class="single-product-purchase-card__info-icon" aria-hidden="true">' . my_theme_single_product_weight_svg() . '</span>'
                . '<span class="single-product-purchase-card__info-text">' . esc_html($weight) . '</span></div>';
            break;

        case 'price':
            $output = my_theme_single_product_render_purchase_pricing($product, $attributes);
            break;

        case 'purchase-actions':
            $inner = '' !== trim($content) ? $content : do_blocks(
                '<!-- wp:theme/product-add-to-cart /--><!-- wp:theme/product-bulk-order /-->'
            );
            $output = '<div class="single-product-purchase-card__actions">' . $inner . '</div>';
            break;

        case 'add-to-cart':
            $output = my_theme_single_product_render_purchase_cart_button(
                $product,
                sanitize_text_field($attributes['label'] ?? ''),
                sanitize_text_field($attributes['contactLabel'] ?? ''),
                esc_url_raw($attributes['contactUrl'] ?? '')
            );
            break;

        case 'bulk-order':
            $label = sanitize_text_field($attributes['label'] ?? __('سفارش عمده', 'my-theme'));
            $output = '<button type="button" class="single-product-purchase-card__action single-product-purchase-card__action--bulk" data-product-id="'
                . esc_attr($product->get_id()) . '" data-product-title="' . esc_attr($product->get_title()) . '">' . esc_html($label) . '</button>';
            break;

        case 'shipping':
            $output = my_theme_single_product_render_purchase_shipping($product, $attributes);
            break;

        case 'benefits':
            $inner = '' !== trim($content) ? $content : do_blocks('<!-- wp:theme/product-benefit-item {"icon":"delivery","title":"ارسال سریع","description":"با هماهنگی قبلی"} /--><!-- wp:theme/product-benefit-item {"icon":"return","title":"ضمانت مرجوعی","description":"تا ۷ روز کاری"} /--><!-- wp:theme/product-benefit-item {"icon":"authenticity","title":"تضمین اصالت","description":"از بهترین برندها"} /--><!-- wp:theme/product-benefit-item {"icon":"consultation","title":"مشاوره تخصصی","description":"قبل و بعد از خرید"} /-->');
            $output = '<section class="wp-block-group product-benefits-section"><div class="product-benefits-box"><div class="product-benefits-list" aria-label="' . esc_attr__('مزایای خرید محصول','my-theme') . '">' . $inner . '</div></div></section>';
            break;

        case 'benefit-item':
            $output = my_theme_single_product_render_benefit_item(
                sanitize_key($attributes['icon'] ?? 'delivery'),
                sanitize_text_field($attributes['title'] ?? ''),
                sanitize_text_field($attributes['description'] ?? ''),
                esc_url_raw($attributes['imageUrl'] ?? ''),
                sanitize_text_field($attributes['imageAlt'] ?? '')
            );
            break;

        case 'details':
            $output = '<section class="wp-block-group product-details-tabs-section">' . my_theme_single_product_render_details_tabs($product, $attributes) . '</section>';
            break;

        case 'similar-products':
            $similar_products = my_theme_single_product_render_related_products_section($product, ['title'=>$attributes['sectionTitle'] ?? __('محصولات مشابه','my-theme'),'previous_label'=>$attributes['previousLabel'] ?? __('محصولات قبلی','my-theme'),'next_label'=>$attributes['nextLabel'] ?? __('محصولات بعدی','my-theme')]);
            $output = '' === $similar_products ? '' : '<section class="wp-block-group related-products-section">' . $similar_products . '</section>';
            break;

        case 'related-products':
            $related_products = my_theme_single_product_render_related_products_section(
                $product,
                [
                    'title'                     => $attributes['sectionTitle'] ?? __('محصولات مرتبط', 'my-theme'),
                    'previous_label'            => $attributes['previousLabel'] ?? __('محصولات قبلی','my-theme'),
                    'next_label'                => $attributes['nextLabel'] ?? __('محصولات بعدی','my-theme'),
                    'carousel_extra_attributes' => 'data-connected-products-carousel',
                ]
            );
            $output = '' === $related_products ? '' : '<section class="wp-block-group connected-products-section related-products-section">' . $related_products . '</section>';
            break;

        default:
            $output = '';
            break;
    }

    if ($product_post instanceof WP_Post) {
        wp_reset_postdata();
    }

    $post = $previous_post;

    if ($previous_product instanceof WC_Product) {
        $GLOBALS['product'] = $previous_product;
    } else {
        unset($GLOBALS['product']);
    }

    return $output;
}

/**
 * Render the main three-column product section.
 *
 * @return string
 */
function my_theme_single_product_render_main_section_block() {
    return do_blocks(
        <<<'HTML'
<!-- wp:group {"className":"single-product-section","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"left","verticalAlignment":"top"}} -->
<div class="wp-block-group single-product-section">
    <!-- wp:group {"className":"single-product-section__gallery","layout":{"type":"default"}} -->
    <div class="wp-block-group single-product-section__gallery">
        <!-- wp:woocommerce/product-image-gallery {"className":"single-product-gallery"} /-->
    </div>
    <!-- /wp:group -->

    <!-- wp:group {"className":"single-product-section__details","layout":{"type":"default"}} -->
    <div class="wp-block-group single-product-section__details"></div>
    <!-- /wp:group -->

    <!-- wp:group {"className":"single-product-section__purchase","layout":{"type":"default"}} -->
    <div class="wp-block-group single-product-section__purchase"></div>
    <!-- /wp:group -->
</div>
<!-- /wp:group -->
HTML
    );
}

/**
 * Format a WooCommerce average rating without unnecessary trailing zeros.
 *
 * @param string|float $average_rating Product average rating.
 * @return string
 */
function my_theme_single_product_format_average_rating($average_rating) {
    $formatted_rating = wc_format_decimal((float) $average_rating, 1);
    $formatted_rating = rtrim(rtrim($formatted_rating, '0'), '.');

    return '' === $formatted_rating ? '0' : $formatted_rating;
}

/**
 * Return the inline star SVG used by the custom product rating row.
 *
 * @return string
 */
function my_theme_single_product_rating_star_svg() {
    return '<svg width="16" height="15" viewBox="0 0 16 15" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M7.89307 0.55896C7.97594 0.570552 8.05678 0.593473 8.1333 0.627319L8.24463 0.685913L8.34619 0.759155C8.4104 0.812641 8.46703 0.874716 8.51416 0.943726L8.57764 1.05212V1.0531L10.1177 4.17224C10.1797 4.29769 10.2711 4.40631 10.3843 4.48865C10.4975 4.571 10.6295 4.62456 10.7681 4.6449L14.2114 5.1488H14.2104C14.3356 5.16694 14.4557 5.21092 14.562 5.27771L14.6636 5.35193L14.7534 5.43982C14.8371 5.53311 14.9008 5.64303 14.9399 5.76306C14.9921 5.92334 14.9988 6.09551 14.9585 6.25916C14.9181 6.42275 14.833 6.57216 14.7124 6.68982H14.7114L12.2212 9.1156C12.1209 9.21342 12.0458 9.33394 12.0024 9.46716C11.9591 9.6004 11.9486 9.74214 11.9722 9.88025L12.5591 13.3041C12.5885 13.4708 12.5707 13.6432 12.5073 13.8002C12.4439 13.9569 12.3375 14.0924 12.2007 14.1918C12.0637 14.2913 11.9018 14.3509 11.7329 14.3627C11.5641 14.3744 11.395 14.3386 11.2456 14.2592V14.2582L8.16943 12.641C8.04566 12.5761 7.90782 12.5424 7.76807 12.5424C7.62802 12.5424 7.48971 12.5769 7.36572 12.642L7.36475 12.641L4.28857 14.2582L4.28955 14.2592C4.14012 14.3383 3.97088 14.3737 3.80225 14.3617C3.63382 14.3497 3.47204 14.2911 3.33545 14.1918C3.19872 14.0923 3.09217 13.9559 3.02881 13.7992C2.96586 13.6433 2.94743 13.4727 2.97607 13.307L3.56396 9.88123C3.58768 9.74298 3.57707 9.60055 3.53369 9.46716C3.50114 9.36716 3.45082 9.27423 3.38525 9.19275L3.31494 9.1156L0.824707 6.6908L0.825684 6.68982C0.704651 6.57246 0.617475 6.4247 0.57666 6.26111C0.535749 6.09708 0.542133 5.92485 0.594238 5.76404L0.641113 5.64685C0.696336 5.53345 0.775276 5.43268 0.872559 5.35095L0.974121 5.27673C1.08094 5.20987 1.20096 5.16564 1.32666 5.14783L4.76807 4.6449C4.90671 4.62473 5.0385 4.57099 5.15186 4.48865C5.2651 4.40635 5.35643 4.29775 5.41846 4.17224L6.9585 1.0531V1.05212C7.03319 0.901415 7.1486 0.774641 7.2915 0.685913L7.40283 0.627319C7.51746 0.576649 7.64182 0.550171 7.76807 0.550171L7.89307 0.55896Z" fill="#FFB81C" stroke="#FFB81C" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>';
}

/**
 * Return the inline arrow SVG used by the product attributes link button.
 *
 * @return string
 */
function my_theme_single_product_arrow_svg() {
    return '<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M7.5 2.5L4 6L7.5 9.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
}

/**
 * Return the inline arrow SVG used by the mobile specifications reveal button.
 *
 * @return string
 */
function my_theme_single_product_specs_arrow_svg() {
    return '<svg width="8" height="12" viewBox="0 0 8 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M3.34799 4.90372C2.92005 5.29957 2.92005 5.97607 3.34799 6.37192L6.73372 9.50372C7.16167 9.89957 7.16167 10.5761 6.73372 10.9719L6.69287 11.0097C6.30959 11.3642 5.71807 11.3642 5.33479 11.0097L0.320966 6.37192C-0.106978 5.97607 -0.106979 5.29957 0.320965 4.90372L5.33479 0.265932C5.71807 -0.0886023 6.30959 -0.0886033 6.69287 0.265931L6.73372 0.303717C7.16167 0.699566 7.16167 1.37607 6.73372 1.77192L3.34799 4.90372Z" fill="#009E00"/></svg>';
}

/**
 * Convert Western digits to Persian digits.
 *
 * @param string|int $value Value to localize.
 * @return string
 */
function my_theme_single_product_persian_digits($value) {
    return strtr((string) $value, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
}

/**
 * Convert a Gregorian date to Jalali date parts.
 *
 * @param int $gy Gregorian year.
 * @param int $gm Gregorian month.
 * @param int $gd Gregorian day.
 * @return array{0:int,1:int,2:int}
 */
function my_theme_single_product_gregorian_to_jalali($gy, $gm, $gd) {
    $g_days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    $j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];

    $gy -= 1600;
    $gm -= 1;
    $gd -= 1;

    $g_day_no = 365 * $gy + (int) (($gy + 3) / 4) - (int) (($gy + 99) / 100) + (int) (($gy + 399) / 400);

    for ($i = 0; $i < $gm; ++$i) {
        $g_day_no += $g_days_in_month[$i];
    }

    if ($gm > 1 && (($gy + 1600) % 4 === 0 && (($gy + 1600) % 100 !== 0 || ($gy + 1600) % 400 === 0))) {
        ++$g_day_no;
    }

    $g_day_no += $gd;
    $j_day_no = $g_day_no - 79;
    $j_np     = (int) ($j_day_no / 12053);
    $j_day_no %= 12053;
    $jy       = 979 + 33 * $j_np + 4 * (int) ($j_day_no / 1461);
    $j_day_no %= 1461;

    if ($j_day_no >= 366) {
        $jy += (int) (($j_day_no - 1) / 365);
        $j_day_no = ($j_day_no - 1) % 365;
    }

    for ($i = 0; $i < 11 && $j_day_no >= $j_days_in_month[$i]; ++$i) {
        $j_day_no -= $j_days_in_month[$i];
    }

    return [$jy, $i + 1, $j_day_no + 1];
}

/**
 * Format a comment date in Persian Jalali format.
 *
 * @param WP_Comment $comment Review comment.
 * @return string
 */
function my_theme_single_product_format_review_date($comment) {
    $timestamp = get_comment_date('U', $comment);
    $year      = (int) wp_date('Y', $timestamp);
    $month     = (int) wp_date('n', $timestamp);
    $day       = (int) wp_date('j', $timestamp);
    [$jy, $jm, $jd] = my_theme_single_product_gregorian_to_jalali($year, $month, $day);

    $months = [
        1  => __('فروردین', 'my-theme'),
        2  => __('اردیبهشت', 'my-theme'),
        3  => __('خرداد', 'my-theme'),
        4  => __('تیر', 'my-theme'),
        5  => __('مرداد', 'my-theme'),
        6  => __('شهریور', 'my-theme'),
        7  => __('مهر', 'my-theme'),
        8  => __('آبان', 'my-theme'),
        9  => __('آذر', 'my-theme'),
        10 => __('دی', 'my-theme'),
        11 => __('بهمن', 'my-theme'),
        12 => __('اسفند', 'my-theme'),
    ];

    return sprintf('%1$s %2$s %3$s', my_theme_single_product_persian_digits($jd), $months[$jm], my_theme_single_product_persian_digits($jy));
}

/**
 * Return the visitor key used for review-vote tracking.
 *
 * @return string
 */
function my_theme_single_product_get_review_voter_key() {
    if (is_user_logged_in()) {
        return 'u:' . get_current_user_id();
    }

    $token = isset($_COOKIE['my_theme_review_vote_token']) ? sanitize_text_field(wp_unslash($_COOKIE['my_theme_review_vote_token'])) : '';

    if ('' === $token) {
        return '';
    }

    return 'g:' . hash_hmac('sha256', $token, wp_salt('nonce'));
}

/**
 * Return current review vote counts and visitor state.
 *
 * @param int $comment_id Comment ID.
 * @return array{likes:int,dislikes:int,state:string}
 */
function my_theme_single_product_get_review_vote_data($comment_id) {
    $votes = get_comment_meta($comment_id, '_my_theme_review_votes', true);
    $votes = is_array($votes) ? $votes : [];
    $likes = 0;
    $dislikes = 0;

    foreach ($votes as $vote) {
        if ('like' === $vote) {
            ++$likes;
        } elseif ('dislike' === $vote) {
            ++$dislikes;
        }
    }

    $state = '';
    $voter_key = my_theme_single_product_get_review_voter_key();

    if ('' !== $voter_key && isset($votes[$voter_key])) {
        $state = (string) $votes[$voter_key];
    }

    return [
        'likes'    => $likes,
        'dislikes' => $dislikes,
        'state'    => $state,
    ];
}

/**
 * Return the inline heart SVG used by the wishlist action.
 *
 * @return string
 */
function my_theme_single_product_heart_svg() {
    return '<svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M11.9902 0.650391C13.2561 0.650391 14.3343 1.2749 15.1133 2.24414C15.8977 3.22019 16.3496 4.51511 16.3496 5.75488C16.3496 8.26884 14.4636 10.6619 12.377 12.502C11.3537 13.4043 10.3232 14.138 9.53906 14.6455C9.1474 14.899 8.81964 15.0943 8.58887 15.2246C8.55745 15.2424 8.52731 15.2574 8.5 15.2725C8.47269 15.2574 8.44255 15.2424 8.41113 15.2246C8.18036 15.0943 7.8526 14.899 7.46094 14.6455C6.67683 14.138 5.64631 13.4043 4.62305 12.502C2.53645 10.6619 0.650391 8.26884 0.650391 5.75488C0.650419 4.51511 1.10234 3.22019 1.88672 2.24414C2.66567 1.2749 3.74388 0.650391 5.00977 0.650391C6.46508 0.650541 7.42173 1.41021 7.99414 2.11914C8.11754 2.27199 8.30356 2.36133 8.5 2.36133C8.69644 2.36133 8.88246 2.27199 9.00586 2.11914C9.57827 1.41021 10.5349 0.650541 11.9902 0.650391Z" stroke="#4A4A4A" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>';
}

/**
 * Return the Telegram share SVG.
 *
 * @return string
 */
function my_theme_single_product_telegram_svg() {
    $gradient_id = esc_attr(wp_unique_id('product-share-telegram-gradient-'));

    return '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M10 20C15.5228 20 20 15.5228 20 10C20 4.47715 15.5228 0 10 0C4.47715 0 0 4.47715 0 10C0 15.5228 4.47715 20 10 20Z" fill="url(#' . $gradient_id . ')"/><path d="M6.76953 10.731L7.95595 14.0148C7.95595 14.0148 8.10428 14.3221 8.26311 14.3221C8.42195 14.3221 10.7844 11.8644 10.7844 11.8644L13.4114 6.79025L6.81186 9.88334L6.76953 10.731Z" fill="#C8DAEA"/><path d="M8.34209 11.5732L8.11434 13.9937C8.11434 13.9937 8.01901 14.7353 8.76051 13.9937C9.50201 13.252 10.2118 12.6801 10.2118 12.6801" fill="#A9C6D8"/><path d="M6.79067 10.8482L4.35017 10.053C4.35017 10.053 4.05851 9.93468 4.15242 9.66635C4.17176 9.61101 4.21075 9.56393 4.32742 9.48301C4.86817 9.1061 14.3363 5.70301 14.3363 5.70301C14.3363 5.70301 14.6036 5.61293 14.7613 5.67285C14.8003 5.68492 14.8354 5.70714 14.863 5.73723C14.8906 5.76732 14.9097 5.8042 14.9183 5.8441C14.9354 5.91457 14.9425 5.98707 14.9395 6.05951C14.9388 6.12218 14.9312 6.18026 14.9254 6.27135C14.8678 7.20176 13.1421 14.1458 13.1421 14.1458C13.1421 14.1458 13.0388 14.5521 12.6689 14.566C12.578 14.5689 12.4874 14.5536 12.4026 14.5207C12.3178 14.4879 12.2404 14.4384 12.1752 14.375C11.4493 13.7506 8.94026 12.0644 8.38584 11.6936C8.37333 11.6851 8.3628 11.674 8.35495 11.661C8.3471 11.6481 8.34212 11.6336 8.34034 11.6186C8.33259 11.5795 8.37509 11.5311 8.37509 11.5311C8.37509 11.5311 12.7439 7.64776 12.8602 7.2401C12.8692 7.20851 12.8352 7.19293 12.7895 7.20676C12.4993 7.31351 7.46917 10.4901 6.91401 10.8407C6.87404 10.8528 6.8318 10.8553 6.79067 10.8482Z" fill="white"/><defs><linearGradient id="' . $gradient_id . '" x1="10" y1="20" x2="10" y2="0" gradientUnits="userSpaceOnUse"><stop stop-color="#1D93D2"/><stop offset="1" stop-color="#38B0E3"/></linearGradient></defs></svg>';
}

/**
 * Return the WhatsApp share SVG.
 *
 * @return string
 */
function my_theme_single_product_whatsapp_svg() {
    return '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M0 20L1.41284 14.8646C0.540006 13.3581 0.0815332 11.6491 0.0836418 9.90999C0.0859426 4.44555 4.55279 0 10.0419 0C12.7056 0.00134738 15.2058 1.03331 17.0861 2.90657C18.9664 4.77984 20.0009 7.2698 20 9.91794C19.9976 15.382 15.53 19.8283 10.0418 19.8283H10.0374C8.37095 19.8277 6.73344 19.4115 5.27891 18.6219L0 20Z" fill="#23B33A"/><path d="M10.0031 2.00002C5.59028 2.00002 2.00158 5.58724 2.00001 9.99648C1.99784 11.5021 2.42184 12.9776 3.22302 14.2524L3.41335 14.555L2.60504 17.5058L5.63292 16.7119L5.9253 16.8851C7.15328 17.6138 8.56126 17.9992 9.99711 18H10.0001C14.4096 18 17.9983 14.4124 18 10.0029C18.0033 8.9519 17.7981 7.91069 17.3963 6.93954C16.9944 5.96839 16.404 5.08659 15.659 4.3452C14.9182 3.59974 14.0368 3.00863 13.066 2.60608C12.0952 2.20353 11.0541 1.99753 10.0031 2.00002Z" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M7.54256 5.46451C7.35814 5.0228 7.16408 5.01385 6.98889 5.0062L6.51726 5C6.35318 5 6.08659 5.06638 5.86121 5.33189C5.63584 5.59741 5 6.23912 5 7.54433C5 8.84953 5.88169 10.1107 6.00455 10.2879C6.12741 10.4651 7.70664 13.2289 10.2076 14.2923C12.2859 15.176 12.7088 15.0002 13.1601 14.9561C13.6113 14.9119 14.6158 14.3144 14.8207 13.6949C15.0256 13.0754 15.0257 12.5447 14.9643 12.4337C14.9028 12.3227 14.7388 12.2568 14.4925 12.124C14.2462 11.9913 13.0368 11.3496 12.8113 11.261C12.5858 11.1724 12.4218 11.1283 12.2576 11.3939C12.0934 11.6594 11.6225 12.2566 11.4788 12.4337C11.3352 12.6108 11.1919 12.633 10.9457 12.5004C10.6994 12.3678 9.90712 12.0875 8.96707 11.1838C8.23568 10.4806 7.74197 9.61217 7.59823 9.3468C7.4545 9.08143 7.58297 8.9377 7.70637 8.80552C7.81678 8.68662 7.95235 8.49571 8.07561 8.34087C8.19887 8.18604 8.23943 8.07536 8.32133 7.89859C8.40324 7.72182 8.36242 7.56655 8.30085 7.43394C8.23929 7.30132 7.76124 5.98919 7.54256 5.46451Z" fill="#23B33A"/></svg>';
}

/**
 * Return the native share SVG fallback.
 *
 * @return string
 */
function my_theme_single_product_native_share_svg() {
    return <<<'SVG'
<svg
    width="18"
    height="20"
    viewBox="0 0 18 20"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    aria-hidden="true"
    focusable="false"
>
    <path d="M8.94095 0.000366211H9.00491C9.02235 1.5741 8.99582 4.73077 8.99582 4.73077C7.51111 3.88664 6.01622 3.06239 4.53223 2.21567C5.50435 1.65537 6.47721 1.09666 7.45079 0.539554C7.9119 0.275853 8.40754 0.0493497 8.94059 0.000366211H8.94095Z" fill="#B8CE01"/>
    <path d="M9.0019 0H9.11091C9.6883 0.0526665 10.2101 0.331467 10.7075 0.614319C11.6339 1.14393 12.559 1.676 13.4829 2.21052C11.9931 3.04766 10.487 3.89806 8.995 4.73262C8.98228 3.15888 9.01789 1.57373 9.00044 0H9.0019Z" fill="#7DB425"/>
    <path d="M4.5333 2.21384L8.9969 4.72894L4.50242 7.39025C4.50242 7.39025 4.50024 5.70161 4.50532 4.85968C4.50133 3.98534 4.5333 2.21384 4.5333 2.21384Z" fill="#F6A925"/>
    <path d="M13.4882 2.21088L13.5009 2.21787C13.4973 3.78645 13.5009 5.35503 13.5009 6.92361C13.4983 7.07719 13.4969 7.39392 13.4969 7.39392L8.99805 4.73076C8.99805 4.73076 11.9987 3.04802 13.4896 2.21088H13.4882Z" fill="#35AC9D"/>
    <path d="M13.4969 2.21753C14.4882 2.78876 15.4726 3.37251 16.4573 3.95479C16.9336 4.22585 17.4384 4.52638 17.6985 5.03721C16.3039 5.81984 13.4951 7.39431 13.4951 7.39431C13.4951 7.39431 13.4929 7.07721 13.4951 6.92363C13.4951 5.35505 13.4929 3.78648 13.4951 2.2179L13.4969 2.21753Z" fill="#59D6BD"/>
    <path d="M1.68957 3.86822C2.62161 3.31577 4.53073 2.2153 4.53073 2.2153L4.50457 7.39024C4.50457 7.39024 1.70665 5.82682 0.316406 5.04382C0.619454 4.49801 1.16669 4.16617 1.68957 3.86822Z" fill="#EF7414"/>
    <path d="M8.99367 4.73041L13.4925 7.3932C13.4925 7.3932 12.0449 8.20529 11.3261 8.59532C10.558 9.03727 8.9973 9.91456 8.9973 9.91456L4.50391 7.39025L8.99367 4.73041Z" fill="white"/>
    <path d="M0 7.10923C0.00399703 6.41463 -0.00835621 5.67729 0.314677 5.04382C1.70601 5.82682 4.50284 7.39024 4.50284 7.39024C2.99959 8.27121 1.50361 9.16543 0.00109116 10.0475C-0.00108904 9.06784 0.00109116 8.08854 0.00109116 7.10923H0Z" fill="#E74B50"/>
    <path d="M17.6988 5.0372C17.9728 5.5565 18.0127 6.16051 17.9975 6.73837C17.9975 7.83626 17.9975 10.0571 17.9975 10.0571L13.4961 7.39393C13.4961 7.39393 16.3042 5.81983 17.6988 5.0372Z" fill="#794387"/>
    <path d="M4.50329 7.39026C4.5102 7.38731 8.99668 9.91457 8.99668 9.91457C8.99668 9.91457 8.9985 12.5549 8.99923 13.8465C9.0065 14.2623 8.99668 15.1028 8.99668 15.1028C7.49707 14.2638 6.00545 13.4174 4.50547 12.5785L4.50293 12.567C4.50293 10.8415 4.50293 9.11586 4.50293 7.39026H4.50329Z" fill="#E4E4E4"/>
    <path d="M0.00134028 10.0475C1.50386 9.16361 2.99984 8.27123 4.50309 7.39026C4.50164 9.1161 4.50164 10.8417 4.50309 12.567C4.27235 12.4694 4.06233 12.3302 3.84213 12.212C2.56198 11.4986 1.28584 10.7686 0.000976562 10.0623V10.0486L0.00134028 10.0475Z" fill="#794387"/>
    <path d="M13.4953 7.39392C13.4953 8.45216 13.4953 9.51016 13.4953 10.5679C13.4906 11.2408 13.512 11.9111 13.4953 12.5836C11.9979 13.4086 8.99609 15.1027 8.99609 15.1027C8.99609 15.1027 9.00591 14.2623 8.99864 13.8465C8.99864 12.5549 8.99609 9.91455 8.99609 9.91455L13.4953 7.39392Z" fill="#F1F1F1"/>
    <path d="M13.4955 7.39392C13.4955 7.39392 17.9968 10.0508 17.9968 10.0571C16.5009 10.8975 13.4958 12.5836 13.4958 12.5836C13.5125 11.9111 13.4911 11.2408 13.4958 10.5679C13.4958 9.50966 13.4955 8.45167 13.4955 7.39392Z" fill="#4C3683"/>
    <path d="M0.000363722 10.0623C1.28523 10.7698 2.55955 11.4986 3.84115 12.2131C4.06135 12.3313 4.27138 12.4709 4.50211 12.5681L4.50466 12.5796C3.12386 13.3955 1.7438 14.2117 0.364458 15.0284C0.219111 14.8041 0.146437 14.5426 0.0915691 14.2826C0.00472447 13.8491 0 13.4049 0 12.9644C0 11.997 0 11.0296 0 10.0623H0.000363722Z" fill="#4C3683"/>
    <path d="M17.9963 10.0571C17.9984 11.0754 17.9963 12.0938 17.9963 13.1121C18.0108 13.7651 17.9919 14.4608 17.6485 15.0365C16.4058 14.2999 13.4941 12.5836 13.4941 12.5836C13.4941 12.5836 16.5003 10.8976 17.9963 10.0571Z" fill="#E74B50"/>
    <path d="M0.365234 15.0273C1.74457 14.2096 3.12464 13.3934 4.50543 12.5785C4.49635 14.3032 4.50834 16.0283 4.49962 17.7527C3.52216 17.1941 2.54725 16.6311 1.57488 16.0637C1.12031 15.7911 0.654838 15.488 0.365234 15.0273Z" fill="#0F68A0"/>
    <path d="M4.50484 12.5785C6.00482 13.4174 7.49644 14.2638 8.99605 15.1028C7.49898 15.9867 4.5012 17.7545 4.49902 17.7527C4.50774 16.0283 4.49575 14.3032 4.50484 12.5785Z" fill="#49BDCA"/>
    <path d="M13.488 12.5869L13.4953 12.5836C13.4997 14.3087 13.4862 16.0342 13.5022 17.7593L13.4909 17.7648C12.3042 17.0463 11.1033 16.3506 9.91105 15.6405C9.61091 15.4674 8.99609 15.1028 8.99609 15.1028C8.99609 15.1028 11.9906 13.4138 13.488 12.5869Z" fill="#F6A925"/>
    <path d="M13.4953 12.5836C13.6279 12.6753 16.407 14.3006 17.6497 15.0365C17.4731 15.3421 17.1933 15.5664 16.9113 15.7672C16.4564 16.0773 15.9709 16.3369 15.4978 16.6161C14.8325 16.9973 14.1715 17.3862 13.5022 17.7578C13.4862 16.0327 13.4997 14.3072 13.4953 12.5821V12.5836Z" fill="#EF7414"/>
    <path d="M8.99605 15.1028V19.9985C8.6236 19.9429 8.21772 19.856 7.88778 19.6641C6.75735 19.0284 5.6251 18.3964 4.49902 17.7527C5.99609 16.8669 7.49898 15.9885 8.99605 15.1028Z" fill="#7DB425"/>
    <path d="M8.99609 15.1028C8.99609 15.1028 9.61091 15.4674 9.91105 15.6405C11.1033 16.3505 12.3042 17.0463 13.4909 17.7648C12.3333 18.414 11.1757 19.0646 10.0182 19.7168C9.729 19.8795 9.40817 19.9761 9.07821 20C9.05787 20 8.99609 20 8.99609 20V15.1028Z" fill="#B8CE01"/>
</svg>
SVG;
}


/**
 * Return the generic mobile share SVG.
 *
 * @return string
 */
function my_theme_single_product_mobile_share_svg() {
    return '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M15 5.83333C15.6454 5.96714 16.1323 6.19049 16.5237 6.56324C17.5 7.49317 17.5 8.98991 17.5 11.9833C17.5 14.9767 17.5 16.4734 16.5237 17.4034C15.5474 18.3333 13.976 18.3333 10.8333 18.3333H9.16667C6.02397 18.3333 4.45262 18.3333 3.47631 17.4034C2.5 16.4734 2.5 14.9767 2.5 11.9833C2.5 8.98991 2.5 7.49317 3.47631 6.56324C3.86765 6.19049 4.35458 5.96714 5 5.83333" stroke="#4A4A4A" stroke-width="1.3" stroke-linecap="round"/><path d="M10.0211 1.66709L10 11.6667M12.5 4.10738C12.5 4.10738 11.1276 2.4503 10.3723 1.81256C10.2635 1.72063 10.1426 1.67213 10.0211 1.66709C9.88558 1.66148 9.74925 1.70992 9.62775 1.81243C8.87242 2.45004 7.5 4.10738 7.5 4.10738" stroke="#4A4A4A" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>';
}

/**
 * Return the review star SVG used in mobile review cards.
 *
 * @param bool $filled Whether the star is filled.
 * @return string
 */
function my_theme_single_product_review_star_svg($filled = true) {
    $fill = $filled ? '#FFB81C' : 'none';

    return '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M7.89307 1.10913C7.97594 1.12072 8.05678 1.14364 8.1333 1.17749L8.24463 1.23608L8.34619 1.30933C8.4104 1.36281 8.46703 1.42489 8.51416 1.4939L8.57764 1.60229L10.1177 4.72144C10.1797 4.84689 10.2711 4.95551 10.3843 5.03785C10.4975 5.1202 10.6295 5.17376 10.7681 5.1941L14.2114 5.698C14.3356 5.71614 14.4557 5.76012 14.562 5.82691L14.6636 5.90113L14.7534 5.98902C14.8371 6.08231 14.9008 6.19223 14.9399 6.31226C14.9921 6.47254 14.9988 6.64471 14.9585 6.80836C14.9181 6.97195 14.833 7.12136 14.7124 7.23902L12.2212 9.66577C12.1209 9.76359 12.0458 9.88411 12.0024 10.0173C11.9591 10.1506 11.9486 10.2923 11.9722 10.4304L12.5591 13.8542C12.5885 14.0209 12.5707 14.1934 12.5073 14.3503C12.4439 14.5071 12.3375 14.6426 12.2007 14.742C12.0637 14.8415 11.9018 14.9011 11.7329 14.9128C11.5641 14.9246 11.395 14.8888 11.2456 14.8093L8.16943 13.1912C8.04566 13.1263 7.90782 13.0925 7.76807 13.0925C7.62802 13.0925 7.48971 13.1271 7.36572 13.1921L4.28857 14.8083C4.14012 14.8885 3.97088 14.9239 3.80225 14.9119C3.63382 14.8999 3.47204 14.8413 3.33545 14.742C3.19872 14.6425 3.09217 14.5061 3.02881 14.3494C2.96586 14.1935 2.94743 14.0229 2.97607 13.8572L3.56396 10.4314C3.58768 10.2932 3.57707 10.1507 3.53369 10.0173C3.50114 9.91733 3.45082 9.8244 3.38525 9.74292L3.31494 9.66577L0.824707 7.24097C0.704651 7.12263 0.617475 6.97487 0.57666 6.81128C0.535749 6.64725 0.542133 6.47502 0.594238 6.31421L0.641113 6.19702C0.696336 6.08362 0.775276 5.98285 0.872559 5.90112L0.974121 5.8269C1.08094 5.76004 1.20096 5.71581 1.32666 5.698L4.76807 5.19507C4.90671 5.1749 5.0385 5.12116 5.15186 5.03882C5.2651 4.95652 5.35643 4.84792 5.41846 4.72241L6.9585 1.60327C7.03319 1.45159 7.1486 1.32481 7.2915 1.23608L7.40283 1.17749C7.51746 1.12682 7.64182 1.10034 7.76807 1.10034L7.89307 1.10913Z" fill="' . esc_attr($fill) . '" stroke="#FFB81C" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>';
}

/**
 * Return the star SVG used by the custom mobile review form rating control.
 *
 * @return string
 */
function my_theme_single_product_review_form_star_svg() {
    return '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M10.1484 1.36035C10.2459 1.3738 10.3413 1.40014 10.4316 1.43945L10.5635 1.50781L10.6846 1.59375C10.761 1.65645 10.8282 1.72998 10.8848 1.81152L10.9609 1.93945L10.9619 1.94043L12.8096 5.62012L12.8711 5.72656C12.9396 5.82949 13.027 5.92054 13.1299 5.99414C13.2671 6.09225 13.4281 6.15619 13.5977 6.18066H13.5967L17.7295 6.77539H17.7285C17.9247 6.80334 18.1102 6.88465 18.2637 7.01172C18.3788 7.10709 18.4727 7.22576 18.5391 7.35938L18.5967 7.49805L18.6328 7.64355C18.6593 7.79082 18.6548 7.94251 18.6182 8.08887C18.5693 8.28379 18.4662 8.46046 18.3223 8.59863L18.3213 8.59961L15.333 11.4609L15.332 11.46C15.2113 11.5757 15.1228 11.7186 15.0713 11.874C15.0198 12.0297 15.0072 12.1955 15.0352 12.3564L15.7393 16.3955C15.775 16.5945 15.7535 16.7996 15.6768 16.9863C15.6001 17.1729 15.4724 17.3329 15.3096 17.4492C15.1468 17.5654 14.9553 17.6338 14.7568 17.6475C14.5582 17.661 14.3586 17.62 14.1816 17.5273V17.5264L10.4912 15.6191C10.3403 15.5413 10.1716 15.5 10 15.5C9.82866 15.5001 9.66047 15.5414 9.50977 15.6191L5.81738 17.5264L5.81836 17.5273C5.64152 17.6194 5.4424 17.6603 5.24414 17.6465C5.0459 17.6326 4.85484 17.5644 4.69238 17.4482C4.52984 17.332 4.40181 17.1726 4.3252 16.9863C4.26767 16.8464 4.24088 16.6961 4.24609 16.5459L4.26172 16.3965L4.9668 12.3574L4.97949 12.2354C4.98547 12.1137 4.96924 11.9917 4.93066 11.875C4.87911 11.7191 4.7891 11.5769 4.66797 11.4609V11.46L1.67969 8.60059L1.68066 8.59961C1.53629 8.46187 1.4323 8.28659 1.38281 8.0918C1.33313 7.89602 1.34005 7.68974 1.40332 7.49805L1.46094 7.35938C1.52751 7.22516 1.62158 7.10636 1.7373 7.01074L1.8584 6.92383C1.9845 6.84624 2.12548 6.79521 2.27246 6.77441L6.40332 6.18066H6.4043C6.57399 6.15637 6.73475 6.09221 6.87207 5.99414C7.00919 5.89616 7.1184 5.76726 7.19238 5.62012L9.04004 1.94043V1.93945C9.13017 1.76047 9.26879 1.61139 9.43848 1.50781L9.57031 1.43945C9.70584 1.38056 9.85263 1.34961 10.001 1.34961L10.1484 1.36035Z" fill="currentColor" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>';
}

/**
 * Return feedback icon SVG.
 *
 * @param string $type Feedback type.
 * @return string
 */
function my_theme_single_product_review_feedback_svg($type) {
    if ('dislike' === $type) {
        return '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M6.25 2.5H14.25C15.103 2.5 15.829 3.119 15.963 3.962L16.667 8.333C16.842 9.421 16.001 10.417 14.899 10.417H12.5L12.917 14.167C13.032 15.206 12.218 16.111 11.173 16.111C10.61 16.111 10.082 15.841 9.752 15.386L6.25 10.556V2.5Z" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M3.333 2.5H6.25V10.833H3.333V2.5Z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/></svg>';
    }

    return '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M13.75 17.5H5.75C4.897 17.5 4.171 16.881 4.037 16.038L3.333 11.667C3.158 10.579 3.999 9.583 5.101 9.583H7.5L7.083 5.833C6.968 4.794 7.782 3.889 8.827 3.889C9.39 3.889 9.918 4.159 10.248 4.614L13.75 9.444V17.5Z" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M16.667 17.5H13.75V9.167H16.667V17.5Z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/></svg>';
}

/**
 * Return the copy-link SVG fallback.
 *
 * @return string
 */
function my_theme_single_product_copy_svg() {
    return <<<'SVG'
<svg
    width="20"
    height="20"
    viewBox="0 0 20 20"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    aria-hidden="true"
    focusable="false"
>
    <path d="M1.97697 0.307757C2.31511 0.527533 2.64457 0.760365 2.97245 0.995129C2.99831 1.01357 3.02417 1.03201 3.05081 1.05101C3.12663 1.10514 3.20232 1.15944 3.27795 1.21384C3.30216 1.23122 3.32637 1.24859 3.35131 1.2665C3.47955 1.35936 3.60491 1.4547 3.72815 1.55414C3.75373 1.57457 3.77931 1.59499 3.80567 1.61603C3.85459 1.65532 3.9031 1.69513 3.95113 1.73549C3.97301 1.75302 3.99488 1.77055 4.01742 1.7886C4.03642 1.80434 4.05542 1.82009 4.075 1.83631C4.13833 1.86972 4.13833 1.86972 4.21628 1.84057C4.30908 1.79152 4.38803 1.73584 4.47125 1.67183C4.66002 1.53152 4.85562 1.41317 5.06109 1.29924C5.11525 1.26913 5.11525 1.26913 5.17052 1.23842C7.54888 -0.0660784 10.3159 -0.328493 12.9119 0.423312C14.6829 0.951627 16.4335 2.04147 17.5943 3.50039C17.6709 3.59606 17.7502 3.68896 17.8298 3.78211C18.1939 4.22106 18.492 4.69553 18.7671 5.19435C18.7812 5.2195 18.7954 5.24466 18.8099 5.27057C20.073 7.523 20.3058 10.2756 19.6263 12.7475C19.1054 14.58 18.035 16.3259 16.5725 17.5587C16.5544 17.5739 16.5364 17.5891 16.5178 17.6048C14.9924 18.8845 13.1384 19.734 11.1524 19.9583C11.1117 19.963 11.1117 19.963 11.0702 19.9678C10.7252 20.0022 10.3786 19.9997 10.0322 19.9999C9.98219 20 9.98219 20 9.93113 20C9.42206 19.9995 8.92805 19.9814 8.42576 19.8916C8.39226 19.8859 8.35876 19.8802 8.32424 19.8743C6.40685 19.5414 4.67226 18.6724 3.23847 17.3588C3.20657 17.3303 3.17468 17.3018 3.14183 17.2724C2.24799 16.4711 1.51949 15.4476 1.00987 14.3619C0.987492 14.3143 0.964831 14.2668 0.941942 14.2194C0.508581 13.321 0.247977 12.3657 0.102396 11.3807C0.097089 11.3451 0.091782 11.3096 0.0863141 11.2729C-0.00103474 10.634 0.00793242 9.98678 0.00767367 9.34332C0.00753689 9.25292 0.00735291 9.16252 0.00717339 9.07212C0.00672656 8.82859 0.00652227 8.58506 0.00637502 8.34153C0.00628251 8.18914 0.00614149 8.03675 0.00599245 7.88436C0.00558799 7.46127 0.00524512 7.03818 0.00513206 6.61509C0.00512476 6.58815 0.00511751 6.56121 0.00510999 6.53346C0.00510273 6.50646 0.00509539 6.47947 0.00508792 6.45165C0.00507317 6.39696 0.00505835 6.34228 0.00504339 6.28759C0.00503603 6.26046 0.00502865 6.23333 0.00502107 6.20539C0.0048895 5.76487 0.00430779 5.32436 0.00353507 4.88384C0.00274561 4.43009 0.00233212 3.97634 0.00229337 3.52259C0.00226298 3.2684 0.0020739 3.01421 0.00146811 2.76003C0.000954783 2.54391 0.000786529 2.3278 0.00106043 2.11168C0.00118981 2.0016 0.00110698 1.89154 0.000692706 1.78146C0.00026889 1.66181 0.000465649 1.54218 0.000811814 1.42253C0.000543923 1.38823 0.000276009 1.35393 0 1.31859C0.00243144 0.957495 0.0590507 0.630222 0.312299 0.361914C0.331073 0.340992 0.349847 0.32007 0.369191 0.298514C0.847003 -0.179369 1.47229 -0.0133106 1.97697 0.307757ZM12.581 6.45876C12.5385 6.50144 12.496 6.54412 12.4534 6.58679C12.3625 6.67801 12.2717 6.76928 12.1808 6.86059C12.0373 7.00491 11.8936 7.1491 11.7499 7.29327C11.4454 7.5989 11.1409 7.90467 10.8365 8.21045C10.5069 8.54155 10.1773 8.87262 9.84747 9.20353C9.70454 9.34695 9.56168 9.49046 9.41889 9.63402C9.33037 9.723 9.24177 9.8119 9.15316 9.90079C9.11198 9.94212 9.07083 9.98349 9.0297 10.0249C8.97376 10.0812 8.91772 10.1374 8.86164 10.1936C8.84525 10.2101 8.82885 10.2267 8.81195 10.2438C8.69936 10.3563 8.69936 10.3563 8.62527 10.3934C8.61737 10.3746 8.60946 10.3559 8.60132 10.3366C8.54913 10.2428 8.48574 10.1784 8.40998 10.1023C8.36366 10.0556 8.36366 10.0556 8.31641 10.0079C8.28256 9.9741 8.24869 9.94029 8.21482 9.90649C8.17992 9.87147 8.14502 9.83643 8.11014 9.80138C8.0369 9.72788 7.96354 9.6545 7.89009 9.5812C7.79679 9.48805 7.70378 9.39462 7.61086 9.30109C7.53874 9.22857 7.46646 9.15621 7.39414 9.08389C7.35984 9.04956 7.32559 9.01518 7.29139 8.98074C6.79607 8.48253 6.28916 8.10067 5.56194 8.07923C5.00594 8.10264 4.5343 8.30398 4.13627 8.6937C3.71661 9.17819 3.60414 9.68635 3.63099 10.3101C3.64542 10.4951 3.69662 10.6576 3.7705 10.8266C3.78344 10.8571 3.79639 10.8876 3.80972 10.9191C4.06914 11.456 4.62578 11.8903 5.04017 12.3071C5.07224 12.3394 5.1043 12.3716 5.13637 12.4039C5.28693 12.5554 5.43757 12.7069 5.5883 12.8583C5.76073 13.0314 5.93287 13.2048 6.10478 13.3785C6.23889 13.5139 6.37326 13.649 6.50782 13.784C6.58767 13.8641 6.66741 13.9443 6.74687 14.0248C7.3051 14.6139 7.3051 14.6139 8.01011 14.9925C8.04749 15.0049 8.08487 15.0173 8.12338 15.03C8.62647 15.1726 9.14585 15.1049 9.61244 14.8738C10.0473 14.61 10.4034 14.1822 10.7608 13.8245C10.8175 13.7679 10.8742 13.7113 10.9309 13.6547C11.0996 13.4862 11.2683 13.3176 11.4368 13.1489C11.5005 13.0852 11.5642 13.0215 11.6279 12.9578C11.9093 12.6764 12.1907 12.3949 12.472 12.1133C12.5404 12.0447 12.6089 11.9761 12.6774 11.9076C12.6944 11.8905 12.7114 11.8735 12.729 11.8559C13.0051 11.5795 13.2815 11.3033 13.558 11.0272C13.8428 10.7429 14.1274 10.4583 14.4117 10.1734C14.571 10.0138 14.7303 9.85438 14.89 9.69521C15.0401 9.5456 15.1898 9.39566 15.3393 9.24549C15.394 9.1906 15.4489 9.13583 15.5039 9.08118C15.993 8.59549 15.993 8.59549 16.3023 7.9855C16.3152 7.94821 16.3282 7.91092 16.3415 7.8725C16.4817 7.37333 16.4089 6.83817 16.1623 6.38462C15.8505 5.87488 15.4213 5.57098 14.8434 5.42764C13.8519 5.23722 13.2269 5.80854 12.581 6.45876Z" fill="#0CBC8D"/>
</svg>
SVG;
}

/**
 * Return escaped sharing data for the current product.
 *
 * @param WC_Product $product Current WooCommerce product.
 * @return array
 */
function my_theme_single_product_get_share_data($product) {
    $product_url   = get_permalink($product->get_id());
    $product_title = $product->get_name();

    if (! $product_url) {
        $product_url = home_url('/');
    }

    return [
        'url'       => $product_url,
        'title'     => $product_title,
        'url_raw'   => rawurlencode($product_url),
        'title_raw' => rawurlencode($product_title),
    ];
}

/**
 * Render the wishlist and sharing actions row.
 *
 * @param WC_Product $product Current WooCommerce product.
 * @return string
 */
function my_theme_single_product_render_actions_row($product, $attributes = [], $part = 'all') {
    $share_data        = my_theme_single_product_get_share_data($product);
    $is_editor_preview = is_admin() || (defined('REST_REQUEST') && REST_REQUEST);
    $telegram_url      = 'https://t.me/share/url?url=' . $share_data['url_raw'] . '&text=' . $share_data['title_raw'];
    $whatsapp_url      = 'https://api.whatsapp.com/send?text=' . rawurlencode($share_data['title'] . ' ' . $share_data['url']);

    $wishlist_label = sanitize_text_field($attributes['wishlistLabel'] ?? __('افزودن به علاقمندی', 'my-theme'));
    $share_label = sanitize_text_field($attributes['shareLabel'] ?? __('اشتراک گذاری:', 'my-theme'));
    ob_start();
    ?>
<?php if ('all' === $part) : ?><div class="single-product-actions-row"><?php endif; ?>
    <?php if (in_array($part, ['all','wishlist'], true)) : ?>
    <div class="single-product-wishlist-action">
        <button type="button" class="single-product-wishlist-button" aria-pressed="false"
            data-product-id="<?php echo esc_attr($product->get_id()); ?>"
            aria-label="<?php echo esc_attr($wishlist_label); ?>">
            <span
                class="single-product-wishlist-button__text"><?php echo esc_html($wishlist_label); ?></span>
            <span class="single-product-wishlist-button__icon" aria-hidden="true">
                <?php echo my_theme_single_product_heart_svg(); ?>
            </span>
        </button>
    </div>
    <?php endif; ?>

    <?php if (in_array($part, ['all','share'], true)) : ?>
    <div class="single-product-share-action">
        <span class="single-product-share-action__label"><?php echo esc_html($share_label); ?></span>
        <button type="button"
            class="single-product-share-action__mobile-button single-product-share-link--native"
            data-share-title="<?php echo esc_attr($share_data['title']); ?>"
            data-share-url="<?php echo esc_url($share_data['url']); ?>"
            aria-label="<?php echo esc_attr__('اشتراک گذاری محصول', 'my-theme'); ?>">
            <?php echo my_theme_single_product_mobile_share_svg(); ?>
        </button>
        <div class="single-product-share-action__links">
            <a class="single-product-share-link" href="<?php echo esc_url($is_editor_preview ? '#' : $telegram_url); ?>"
                <?php echo $is_editor_preview ? '' : 'target="_blank" rel="noopener noreferrer"'; ?>
                aria-label="<?php echo esc_attr__('اشتراک گذاری در تلگرام', 'my-theme'); ?>">
                <?php echo my_theme_single_product_telegram_svg(); ?>
            </a>
            <a class="single-product-share-link" href="<?php echo esc_url($is_editor_preview ? '#' : $whatsapp_url); ?>"
                <?php echo $is_editor_preview ? '' : 'target="_blank" rel="noopener noreferrer"'; ?>
                aria-label="<?php echo esc_attr__('اشتراک گذاری در واتساپ', 'my-theme'); ?>">
                <?php echo my_theme_single_product_whatsapp_svg(); ?>
            </a>
            <button type="button" class="single-product-share-link single-product-share-link--native"
                data-share-title="<?php echo esc_attr($share_data['title']); ?>"
                data-share-url="<?php echo esc_url($share_data['url']); ?>"
                aria-label="<?php echo esc_attr__('اشتراک گذاری محصول', 'my-theme'); ?>">
                <?php echo my_theme_single_product_native_share_svg(); ?>
            </button>
            <button type="button" class="single-product-share-link single-product-share-link--copy"
                data-share-url="<?php echo esc_url($share_data['url']); ?>"
                aria-label="<?php echo esc_attr__('کپی لینک محصول', 'my-theme'); ?>">
                <?php echo my_theme_single_product_copy_svg(); ?>
            </button>
        </div>
    </div>
    <?php endif; ?>
<?php if ('all' === $part) : ?></div><?php endif; ?>
<?php
    return trim(ob_get_clean());
}

/**
 * Return visible product attributes with readable labels and values.
 *
 * @param WC_Product $product Current WooCommerce product.
 * @return array
 */
function my_theme_single_product_get_visible_attributes($product) {
    $attributes = [];

    foreach ($product->get_attributes() as $attribute) {
        if (! $attribute instanceof WC_Product_Attribute || ! $attribute->get_visible()) {
            continue;
        }

        $label  = wc_attribute_label($attribute->get_name(), $product);
        $values = [];

        if ($attribute->is_taxonomy()) {
            foreach ($attribute->get_terms() as $term) {
                if ($term instanceof WP_Term) {
                    $values[] = $term->name;
                }
            }
        } else {
            $values = $attribute->get_options();
        }

        $values = array_filter(array_map('wp_strip_all_tags', array_map('trim', $values)));

        if ('' === trim($label) || empty($values)) {
            continue;
        }

        $attributes[] = [
            'label' => $label,
            'value' => implode('، ', $values),
        ];
    }

    return $attributes;
}

/**
 * Return product term names joined with the Persian comma.
 *
 * @param WC_Product $product  Current WooCommerce product.
 * @param string     $taxonomy Product taxonomy name.
 * @return string
 */
function my_theme_single_product_get_joined_term_names($product, $taxonomy) {
    if (! taxonomy_exists($taxonomy)) {
        return '';
    }

    $terms = get_the_terms($product->get_id(), $taxonomy);

    if (empty($terms) || is_wp_error($terms)) {
        return '';
    }

    $term_names = [];

    foreach ($terms as $term) {
        if ($term instanceof WP_Term && '' !== trim($term->name)) {
            $term_names[] = wp_strip_all_tags($term->name);
        }
    }

    return implode('، ', $term_names);
}

/**
 * Return the product brand summary from the registered product brand taxonomy.
 *
 * @param WC_Product $product Current WooCommerce product.
 * @return string
 */
function my_theme_single_product_get_brand_summary($product) {
    foreach (my_theme_get_product_brand_taxonomies() as $taxonomy) {
        $brand_names = my_theme_single_product_get_joined_term_names($product, $taxonomy);

        if ('' !== $brand_names) {
            return $brand_names;
        }
    }

    return '';
}

/**
 * Render category and brand metadata below the actions row.
 *
 * @param WC_Product $product Current WooCommerce product.
 * @return string
 */
function my_theme_single_product_render_meta_summary($product, $attributes = [], $part = 'all') {
    $category_names = my_theme_single_product_get_joined_term_names($product, 'product_cat');
    $brand_names    = my_theme_single_product_get_brand_summary($product);

    if ('' === $category_names && '' === $brand_names) {
        return '';
    }

    ob_start();
    ?>
<?php if ('all' === $part) : ?><div class="single-product-meta-summary"><?php endif; ?>
    <?php if ('' !== $category_names && in_array($part, ['all','category'], true)) : ?>
    <div class="single-product-meta-summary__row">
        <span class="single-product-meta-summary__label"><?php echo esc_html(sanitize_text_field($attributes['categoryLabel'] ?? __('دسته‌بندی:', 'my-theme'))); ?></span>
        <span class="single-product-meta-summary__value"><?php echo esc_html($category_names); ?></span>
    </div>
    <?php endif; ?>
    <?php if ('' !== $brand_names && in_array($part, ['all','brand'], true)) : ?>
    <div class="single-product-meta-summary__row">
        <span class="single-product-meta-summary__label"><?php echo esc_html(sanitize_text_field($attributes['brandLabel'] ?? __('برند:', 'my-theme'))); ?></span>
        <span class="single-product-meta-summary__value"><?php echo esc_html($brand_names); ?></span>
    </div>
    <?php endif; ?>
<?php if ('all' === $part) : ?></div><?php endif; ?>
<?php
    return trim(ob_get_clean());
}

/**
 * Return the inline guarantee SVG used by the purchase card.
 *
 * @return string
 */
function my_theme_single_product_guarantee_svg() {
    return <<<'SVG'
<svg
    width="24"
    height="24"
    viewBox="0 0 24 24"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    aria-hidden="true"
    focusable="false"
>
    <path d="M10.7925 15.1698C10.5925 15.1698 10.4025 15.0898 10.2625 14.9498L7.8425 12.5298C7.5525 12.2398 7.5525 11.7598 7.8425 11.4698C8.1325 11.1798 8.6125 11.1798 8.9025 11.4698L10.7925 13.3598L15.0925 9.05979C15.3825 8.76979 15.8625 8.76979 16.1525 9.05979C16.4425 9.34979 16.4425 9.82978 16.1525 10.1198L11.3225 14.9498C11.1825 15.0898 10.9925 15.1698 10.7925 15.1698Z" fill="#717171"/>
    <path d="M12.0028 22.75C11.3728 22.75 10.7428 22.54 10.2528 22.12L8.67281 20.76C8.51281 20.62 8.11281 20.48 7.90281 20.48H6.18281C4.70281 20.48 3.50281 19.28 3.50281 17.8V16.09C3.50281 15.88 3.36281 15.49 3.22281 15.33L1.87281 13.74C1.05281 12.77 1.05281 11.24 1.87281 10.27L3.22281 8.68C3.36281 8.52 3.50281 8.13 3.50281 7.92V6.2C3.50281 4.72 4.70281 3.52 6.18281 3.52H7.91281C8.12281 3.52 8.52281 3.37 8.68281 3.24L10.2628 1.88C11.2428 1.04 12.7728 1.04 13.7528 1.88L15.3328 3.24C15.4928 3.38 15.8928 3.52 16.1028 3.52H17.8028C19.2828 3.52 20.4828 4.72 20.4828 6.2V7.9C20.4828 8.11 20.6328 8.51 20.7728 8.67L22.1328 10.25C22.9728 11.23 22.9728 12.76 22.1328 13.74L20.7728 15.32C20.6328 15.48 20.4828 15.88 20.4828 16.09V17.79C20.4828 19.27 19.2828 20.47 17.8028 20.47H16.1028C15.8928 20.47 15.4928 20.62 15.3328 20.75L13.7528 22.11C13.2628 22.54 12.6328 22.75 12.0028 22.75ZM6.18281 5.02C5.53281 5.02 5.00281 5.55 5.00281 6.2V7.91C5.00281 8.48 4.73281 9.21 4.36281 9.64L3.01281 11.23C2.66281 11.64 2.66281 12.35 3.01281 12.76L4.36281 14.35C4.73281 14.79 5.00281 15.51 5.00281 16.08V17.79C5.00281 18.44 5.53281 18.97 6.18281 18.97H7.91281C8.49281 18.97 9.22281 19.24 9.66281 19.62L11.2428 20.98C11.6528 21.33 12.3728 21.33 12.7828 20.98L14.3628 19.62C14.8028 19.25 15.5328 18.97 16.1128 18.97H17.8128C18.4628 18.97 18.9928 18.44 18.9928 17.79V16.09C18.9928 15.51 19.2628 14.78 19.6428 14.34L21.0028 12.76C21.3528 12.35 21.3528 11.63 21.0028 11.22L19.6428 9.64C19.2628 9.2 18.9928 8.47 18.9928 7.89V6.2C18.9928 5.55 18.4628 5.02 17.8128 5.02H16.1128C15.5328 5.02 14.8028 4.75 14.3628 4.37L12.7828 3.01C12.3728 2.66 11.6528 2.66 11.2428 3.01L9.66281 4.38C9.22281 4.75 8.48281 5.02 7.91281 5.02H6.18281Z" fill="#717171"/>
</svg>
SVG;
}

/**
 * Return the inline weight SVG used by the purchase card.
 *
 * @return string
 */
function my_theme_single_product_weight_svg() {
    return <<<'SVG'
<svg
    width="24"
    height="24"
    viewBox="0 0 24 24"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    aria-hidden="true"
    focusable="false"
>
    <path d="M12 8C13.6568 8 15 6.65685 15 5C15 3.34315 13.6568 2 12 2C10.3431 2 8.99997 3.34315 8.99997 5C8.99997 6.65685 10.3431 8 12 8Z" stroke="#717171" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M6.49997 8C6.06723 8.00449 5.64761 8.14923 5.30412 8.41248C4.96063 8.67573 4.71179 9.0433 4.59497 9.46L2.09997 18.5C2.02437 18.7926 2.01606 19.0985 2.07565 19.3947C2.13525 19.691 2.2612 19.9699 2.44405 20.2105C2.6269 20.4511 2.8619 20.6471 3.13138 20.7839C3.40086 20.9206 3.69783 20.9945 3.99997 21H20C20.3086 20.9999 20.6131 20.9283 20.8895 20.7909C21.1659 20.6535 21.4068 20.454 21.5932 20.208C21.7796 19.962 21.9066 19.6762 21.9642 19.3729C22.0217 19.0697 22.0083 18.7572 21.925 18.46L19.4 9.5C19.2898 9.07341 19.0419 8.69512 18.6947 8.42388C18.3476 8.15265 17.9205 8.00364 17.48 8H6.49997Z" stroke="#717171" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
SVG;
}

/**
 * Return the formatted WooCommerce product weight.
 *
 * @param WC_Product|null $product Current WooCommerce product.
 * @return string
 */
function my_theme_single_product_get_formatted_weight($product) {
    if (! $product instanceof WC_Product) {
        return '';
    }

    $weight = $product->get_weight();

    if ('' === $weight || 0.0 === (float) $weight) {
        return '';
    }

    return wc_format_weight($weight);
}

/**
 * Return a localized WooCommerce price number without a currency label.
 *
 * @param string|float $price Product price.
 * @return string
 */
function my_theme_single_product_format_price_number($price) {
    if ('' === $price || ! is_numeric($price)) {
        return '';
    }

    $decimals = wc_get_price_decimals();

    return number_format_i18n((float) $price, $decimals);
}

/**
 * Return pricing data for the purchase card.
 *
 * @param WC_Product|null $product Current WooCommerce product.
 * @return array
 */
function my_theme_single_product_get_purchase_pricing($product) {
    $pricing = [
        'regular_price'       => '',
        'final_price'         => '',
        'discount_percentage' => 0,
        'show_discount'       => false,
    ];

    if (! $product instanceof WC_Product) {
        return $pricing;
    }

    if ($product->is_type('variable')) {
        $min_price = $product->get_variation_price('min', true);
        $max_price = $product->get_variation_price('max', true);

        if ('' === $min_price) {
            return $pricing;
        }

        $min_price = my_theme_single_product_format_price_number($min_price);
        $max_price = my_theme_single_product_format_price_number($max_price);

        $pricing['final_price'] = $min_price === $max_price ? $min_price : $min_price . ' - ' . $max_price;

        return $pricing;
    }

    $current_price = $product->get_price();

    if ('' === $current_price) {
        return $pricing;
    }

    $pricing['final_price'] = my_theme_single_product_format_price_number(
        wc_get_price_to_display(
            $product,
            [
                'price' => (float) $current_price,
            ]
        )
    );

    $regular_price = $product->get_regular_price();
    $sale_price    = $product->get_sale_price();

    if ($product->is_on_sale() && '' !== $regular_price && '' !== $sale_price) {
        $regular_price_float = (float) $regular_price;
        $sale_price_float    = (float) $sale_price;

        if (0 < $regular_price_float && $sale_price_float < $regular_price_float) {
            $pricing['regular_price'] = my_theme_single_product_format_price_number(
                wc_get_price_to_display(
                    $product,
                    [
                        'price' => $regular_price_float,
                    ]
                )
            );
            $pricing['discount_percentage'] = (int) round((($regular_price_float - $sale_price_float) / $regular_price_float) * 100);
            $pricing['show_discount']       = 0 < $pricing['discount_percentage'];
        }
    }

    return $pricing;
}

/**
 * Render the purchase card pricing area.
 *
 * @param WC_Product|null $product Current WooCommerce product.
 * @return string
 */
function my_theme_single_product_render_purchase_pricing($product = null, $attributes = []) {
    $pricing = my_theme_single_product_get_purchase_pricing($product);

    if ('' === $pricing['final_price']) {
        return '';
    }

    ob_start();
    ?>
<div class="single-product-purchase-card__pricing">
    <?php if ($pricing['show_discount']) : ?>
    <div class="single-product-purchase-card__discount-row">
        <span
            class="single-product-purchase-card__discount-badge"><?php echo esc_html(number_format_i18n($pricing['discount_percentage']) . '%'); ?></span>
        <span
            class="single-product-purchase-card__regular-price"><?php echo esc_html($pricing['regular_price']); ?></span>
    </div>
    <?php endif; ?>
    <div class="single-product-purchase-card__final-row">
        <span class="single-product-purchase-card__currency"><?php echo esc_html(sanitize_text_field($attributes['currencyLabel'] ?? __('تومان', 'my-theme'))); ?></span>
        <span class="single-product-purchase-card__final-price"><?php echo esc_html($pricing['final_price']); ?></span>
    </div>
</div>
<?php
    return trim(ob_get_clean());
}

/**
 * Return the destination used by the product contact action.
 *
 * @param string $custom_url Optional block-level URL override.
 * @return string
 */
function my_theme_single_product_get_contact_url($custom_url = '') {
    if ('' !== trim($custom_url)) {
        return esc_url_raw($custom_url);
    }

    $contact_page_id = (int) get_option('my_theme_contact_page_id', 0);
    if ($contact_page_id > 0 && 'publish' === get_post_status($contact_page_id)) {
        $permalink = get_permalink($contact_page_id);
        if ($permalink) {
            return $permalink;
        }
    }

    $contact_page = get_page_by_path('contact', OBJECT, 'page');
    if ($contact_page instanceof WP_Post) {
        return get_permalink($contact_page);
    }

    return home_url('/contact/');
}

/**
 * Determine whether product purchasing should be replaced by contact action.
 *
 * @param WC_Product|null $product Current WooCommerce product.
 * @return bool
 */
function my_theme_single_product_requires_contact($product = null) {
    if (! $product instanceof WC_Product) {
        return false;
    }

    $price = $product->get_price();
    if ('' === $price || ! is_numeric($price) || (float) $price <= 0) {
        return true;
    }

    if (! $product->is_in_stock()) {
        return true;
    }

    if ($product->managing_stock()) {
        $quantity = $product->get_stock_quantity();
        if (null !== $quantity && (float) $quantity <= 0) {
            return true;
        }
    }

    return false;
}

/**
 * Render the purchase card add-to-cart or contact control.
 *
 * @param WC_Product|null $product Current WooCommerce product.
 * @param string          $custom_label Custom add-to-cart label.
 * @param string          $contact_label Custom contact label.
 * @param string          $contact_url Custom contact URL.
 * @return string
 */
function my_theme_single_product_render_purchase_cart_button($product = null, $custom_label = '', $contact_label = '', $contact_url = '') {
    $button_text = '' !== trim($custom_label) ? $custom_label : __('افزودن به سبد خرید', 'my-theme');
    $contact_text = '' !== trim($contact_label) ? $contact_label : __('تماس بگیرید', 'my-theme');

    if (! $product instanceof WC_Product) {
        return sprintf(
            '<button type="button" class="single-product-purchase-card__action single-product-purchase-card__action--cart" disabled aria-disabled="true">%s</button>',
            esc_html($button_text)
        );
    }

    if (my_theme_single_product_requires_contact($product)) {
        return sprintf(
            '<a class="single-product-purchase-card__action single-product-purchase-card__action--cart single-product-purchase-card__action--contact" href="%1$s" data-product-id="%2$s">%3$s</a>',
            esc_url(my_theme_single_product_get_contact_url($contact_url)),
            esc_attr($product->get_id()),
            esc_html($contact_text)
        );
    }

    $is_simple_purchasable = $product->is_type('simple') && $product->is_purchasable() && $product->is_in_stock();

    if (is_admin()) {
        return sprintf(
            '<button type="button" class="single-product-purchase-card__action single-product-purchase-card__action--cart" data-product-id="%1$s">%2$s</button>',
            esc_attr($product->get_id()),
            esc_html($button_text)
        );
    }

    if (! $is_simple_purchasable) {
        return sprintf(
            '<button type="button" class="single-product-purchase-card__action single-product-purchase-card__action--cart" disabled aria-disabled="true" data-product-id="%1$s">%2$s</button>',
            esc_attr($product->get_id()),
            esc_html($button_text)
        );
    }

    ob_start();
    ?>
<form class="single-product-purchase-card__cart-form cart"
    action="<?php echo esc_url(apply_filters('woocommerce_add_to_cart_form_action', $product->get_permalink())); ?>"
    method="post" enctype="multipart/form-data">
    <button type="submit" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>"
        class="single-product-purchase-card__action single-product-purchase-card__action--cart single_add_to_cart_button button alt"
        data-product-id="<?php echo esc_attr($product->get_id()); ?>">
        <?php echo esc_html($button_text); ?>
    </button>
</form>
<?php
    return trim(ob_get_clean());
}

/**
 * Render the purchase card action buttons.
 *
 * @param WC_Product|null $product Current WooCommerce product.
 * @return string
 */
function my_theme_single_product_render_purchase_actions($product = null) {
    $product_id    = $product instanceof WC_Product ? $product->get_id() : 0;
    $product_title = $product instanceof WC_Product ? $product->get_title() : '';

    ob_start();
    ?>
<div class="single-product-purchase-card__actions">
    <?php echo my_theme_single_product_render_purchase_cart_button($product); ?>
    <button type="button" class="single-product-purchase-card__action single-product-purchase-card__action--bulk"
        data-product-id="<?php echo esc_attr($product_id); ?>"
        data-product-title="<?php echo esc_attr($product_title); ?>">
        <?php echo esc_html__('سفارش عمده', 'my-theme'); ?>
    </button>
</div>
<?php
    return trim(ob_get_clean());
}

/**
 * Return the inline arrow SVG used by the purchase card shipping details button.
 *
 * @return string
 */
function my_theme_single_product_shipping_arrow_svg() {
    return <<<'SVG'
<svg width="8" height="12" viewBox="0 0 8 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <path d="M3.34799 4.90369C2.92005 5.29954 2.92005 5.97604 3.34799 6.37189L6.73372 9.50369C7.16167 9.89953 7.16167 10.576 6.73372 10.9719L6.69287 11.0097C6.30959 11.3642 5.71807 11.3642 5.33479 11.0097L0.320966 6.37189C-0.106978 5.97604 -0.106979 5.29954 0.320965 4.90369L5.33479 0.265901C5.71807 -0.0886329 6.30959 -0.0886338 6.69287 0.2659L6.73372 0.303687C7.16167 0.699535 7.16167 1.37604 6.73372 1.77189L3.34799 4.90369Z" fill="#009E00"/>
</svg>
SVG;
}

/**
 * Render the purchase card shipping information row.
 *
 * @param WC_Product|null $product Current WooCommerce product.
 * @return string
 */
function my_theme_single_product_render_purchase_shipping($product = null, $attributes = []) {
    $product_id    = $product instanceof WC_Product ? $product->get_id() : 0;
    $product_title = $product instanceof WC_Product ? $product->get_title() : '';

    $title = sanitize_text_field($attributes['title'] ?? __('روش و هزینه ارسال', 'my-theme'));
    $details_label = sanitize_text_field($attributes['detailsLabel'] ?? __('جزئیات', 'my-theme'));
    ob_start();
    ?>
<div class="single-product-purchase-card__shipping">
    <span class="single-product-purchase-card__shipping-title">
        <?php echo esc_html($title); ?>
    </span>
    <button type="button" class="single-product-purchase-card__shipping-details"
        data-product-id="<?php echo esc_attr($product_id); ?>"
        data-product-title="<?php echo esc_attr($product_title); ?>">
        <span
            class="single-product-purchase-card__shipping-details-text"><?php echo esc_html($details_label); ?></span>
        <span class="single-product-purchase-card__shipping-details-icon">
            <?php echo my_theme_single_product_shipping_arrow_svg(); ?>
        </span>
    </button>
</div>
<?php
    return trim(ob_get_clean());
}

/**
 * Render the purchase card shell and its first information rows.
 *
 * @param WC_Product|null $product Current WooCommerce product.
 * @return string
 */
function my_theme_single_product_render_purchase_card($product = null) {
    $formatted_weight = my_theme_single_product_get_formatted_weight($product);

    ob_start();
    ?>
<aside class="single-product-purchase-card">
    <div class="single-product-purchase-card__info-list">
        <div class="single-product-purchase-card__info-row single-product-purchase-card__info-row--guarantee">
            <span class="single-product-purchase-card__info-icon" aria-hidden="true">
                <?php echo my_theme_single_product_guarantee_svg(); ?>
            </span>
            <span
                class="single-product-purchase-card__info-text"><?php echo esc_html__('گارانتی اصالت و سلامت فیزیکی کالا', 'my-theme'); ?></span>
        </div>
        <?php if ('' !== $formatted_weight) : ?>
        <div class="single-product-purchase-card__info-row single-product-purchase-card__info-row--weight">
            <span class="single-product-purchase-card__info-icon" aria-hidden="true">
                <?php echo my_theme_single_product_weight_svg(); ?>
            </span>
            <span class="single-product-purchase-card__info-text"><?php echo esc_html($formatted_weight); ?></span>
        </div>
        <?php endif; ?>
    </div>
    <?php echo my_theme_single_product_render_purchase_pricing($product); ?>
    <?php echo my_theme_single_product_render_purchase_actions($product); ?>
    <?php echo my_theme_single_product_render_purchase_shipping($product); ?>
</aside>
<?php
    return trim(ob_get_clean());
}

/**
 * Return the inline SVG for a product benefit item.
 *
 * @param string $icon Icon key.
 * @return string
 */
function my_theme_single_product_benefit_icon_svg($icon) {
    $icons = [
        'delivery'     => <<<'SVG'
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <path d="M15.9976 19.5068H7.99756" stroke="#9CA3AF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M18.9936 19.0068H20.9976C21.5498 19.0068 21.9976 18.5054 21.9976 17.8868V11.0385C21.9976 10.7536 21.949 10.4714 21.8546 10.207L20.4998 6.41474C20.1961 5.5644 19.4607 5.00684 18.643 5.00684H14.9976" stroke="#9CA3AF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M21.9976 14.0068H18.4976C18.2214 14.0068 17.9976 13.6711 17.9976 13.2568V8.00684H21" stroke="#9CA3AF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M18.5581 18.4462C18.9871 18.8751 19.1155 19.5203 18.8834 20.0808C18.6512 20.6413 18.1043 21.0068 17.4976 21.0068C16.891 21.0069 16.344 20.6414 16.1118 20.0809C15.8796 19.5205 16.0079 18.8753 16.4369 18.4463L16.437 18.4462C16.7183 18.1649 17.0998 18.0068 17.4976 18.0068C17.8954 18.0068 18.2769 18.1649 18.5581 18.4462V18.4462" stroke="#9CA3AF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M7.55813 18.4462C7.98714 18.8752 8.1155 19.5203 7.88337 20.0808C7.65123 20.6413 7.10431 21.0068 6.49764 21.0068C5.89096 21.0069 5.344 20.6414 5.11181 20.081C4.87962 19.5205 5.00791 18.8753 5.43687 18.4464L5.43717 18.446C5.71844 18.1648 6.09992 18.0068 6.49769 18.0068C6.89545 18.0069 7.2769 18.1649 7.55811 18.4462V18.4462" stroke="#9CA3AF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M4.99756 19.0068H2.99756C2.44527 19.0068 1.99756 18.5128 1.99756 17.9034V4.11028C1.99756 3.50087 2.44527 3.00684 2.99756 3.00684H13.9976C14.5498 3.00684 14.9976 3.50087 14.9976 4.11028V14.0413" stroke="#9CA3AF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M14.9976 14.5068H1.99756" stroke="#9CA3AF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
SVG,
        'return'       => <<<'SVG'
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <path fill-rule="evenodd" clip-rule="evenodd" d="M21 16.0407V7.95872C21 7.24372 20.619 6.58372 20 6.22672L13 2.18572C12.381 1.82872 11.619 1.82872 11 2.18572L4 6.22572C3.381 6.58372 3 7.24372 3 7.95872V16.0417C3 16.7567 3.381 17.4167 4 17.7737L11 21.8147C11.619 22.1717 12.381 22.1717 13 21.8147L20 17.7737C20.619 17.4157 21 16.7557 21 16.0407Z" stroke="#9CA3AF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M12 22.08V12" stroke="#9CA3AF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M12 12L20.73 6.95996" stroke="#9CA3AF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M3.27344 6.95996L12.0034 12" stroke="#9CA3AF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
SVG,
        'authenticity' => <<<'SVG'
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <path d="M10.7925 15.1698C10.5925 15.1698 10.4025 15.0898 10.2625 14.9498L7.8425 12.5298C7.5525 12.2398 7.5525 11.7598 7.8425 11.4698C8.1325 11.1798 8.6125 11.1798 8.9025 11.4698L10.7925 13.3598L15.0925 9.05979C15.3825 8.76979 15.8625 8.76979 16.1525 9.05979C16.4425 9.34979 16.4425 9.82978 16.1525 10.1198L11.3225 14.9498C11.1825 15.0898 10.9925 15.1698 10.7925 15.1698Z" fill="#9CA3AF"/>
    <path d="M12.0028 22.75C11.3728 22.75 10.7428 22.54 10.2528 22.12L8.67281 20.76C8.51281 20.62 8.11281 20.48 7.90281 20.48H6.18281C4.70281 20.48 3.50281 19.28 3.50281 17.8V16.09C3.50281 15.88 3.36281 15.49 3.22281 15.33L1.87281 13.74C1.05281 12.77 1.05281 11.24 1.87281 10.27L3.22281 8.68C3.36281 8.52 3.50281 8.13 3.50281 7.92V6.2C3.50281 4.72 4.70281 3.52 6.18281 3.52H7.91281C8.12281 3.52 8.52281 3.37 8.68281 3.24L10.2628 1.88C11.2428 1.04 12.7728 1.04 13.7528 1.88L15.3328 3.24C15.4928 3.38 15.8928 3.52 16.1028 3.52H17.8028C19.2828 3.52 20.4828 4.72 20.4828 6.2V7.9C20.4828 8.11 20.6328 8.51 20.7728 8.67L22.1328 10.25C22.9728 11.23 22.9728 12.76 22.1328 13.74L20.7728 15.32C20.6328 15.48 20.4828 15.88 20.4828 16.09V17.79C20.4828 19.27 19.2828 20.47 17.8028 20.47H16.1028C15.8928 20.47 15.4928 20.62 15.3328 20.75L13.7528 22.11C13.2628 22.54 12.6328 22.75 12.0028 22.75ZM6.18281 5.02C5.53281 5.02 5.00281 5.55 5.00281 6.2V7.91C5.00281 8.48 4.73281 9.21 4.36281 9.64L3.01281 11.23C2.66281 11.64 2.66281 12.35 3.01281 12.76L4.36281 14.35C4.73281 14.79 5.00281 15.51 5.00281 16.08V17.79C5.00281 18.44 5.53281 18.97 6.18281 18.97H7.91281C8.49281 18.97 9.22281 19.24 9.66281 19.62L11.2428 20.98C11.6528 21.33 12.3728 21.33 12.7828 20.98L14.3628 19.62C14.8028 19.25 15.5328 18.97 16.1128 18.97H17.8128C18.4628 18.97 18.9928 18.44 18.9928 17.79V16.09C18.9928 15.51 19.2628 14.78 19.6428 14.34L21.0028 12.76C21.3528 12.35 21.3528 11.63 21.0028 11.22L19.6428 9.64C19.2628 9.2 18.9928 8.47 18.9928 7.89V6.2C18.9928 5.55 18.4628 5.02 17.8128 5.02H16.1128C15.5328 5.02 14.8028 4.75 14.3628 4.37L12.7828 3.01C12.3728 2.66 11.6528 2.66 11.2428 3.01L9.66281 4.38C9.22281 4.75 8.48281 5.02 7.91281 5.02H6.18281Z" fill="#9CA3AF"/>
</svg>
SVG,
        'consultation' => <<<'SVG'
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <path d="M3 11H6C6.53043 11 7.03914 11.2107 7.41421 11.5858C7.78929 11.9609 8 12.4696 8 13V16C8 16.5304 7.78929 17.0391 7.41421 17.4142C7.03914 17.7893 6.53043 18 6 18H5C4.46957 18 3.96086 17.7893 3.58579 17.4142C3.21071 17.0391 3 16.5304 3 16V11ZM3 11C3 9.8181 3.23279 8.64778 3.68508 7.55585C4.13738 6.46392 4.80031 5.47177 5.63604 4.63604C6.47177 3.80031 7.46392 3.13738 8.55585 2.68508C9.64778 2.23279 10.8181 2 12 2C13.1819 2 14.3522 2.23279 15.4442 2.68508C16.5361 3.13738 17.5282 3.80031 18.364 4.63604C19.1997 5.47177 19.8626 6.46392 20.3149 7.55585C20.7672 8.64778 21 9.8181 21 11M21 11V16M21 11H18C17.4696 11 16.9609 11.2107 16.5858 11.5858C16.2107 11.9609 16 12.4696 16 13V16C16 16.5304 16.2107 17.0391 16.5858 17.4142C16.9609 17.7893 17.4696 18 18 18H19C19.5304 18 20.0391 17.7893 20.4142 17.4142C20.7893 17.0391 21 16.5304 21 16M21 16V18C21 19.0609 20.5786 20.0783 19.8284 20.8284C19.0783 21.5786 18.0609 22 17 22H12" stroke="#9CA3AF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
SVG,
    ];

    return $icons[$icon] ?? '';
}

/**
 * Render one static product benefit item.
 *
 * @param string $icon        Icon key.
 * @param string $title       Benefit title.
 * @param string $description Benefit description.
 * @return string
 */
function my_theme_single_product_render_benefit_item($icon, $title, $description, $image_url = '', $image_alt = '') {
    ob_start();
    ?>
<div class="product-benefit">
    <span class="product-benefit__icon" aria-hidden="true">
        <?php if ('' !== $image_url) : ?><img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($image_alt); ?>" width="24" height="24"><?php else : ?><?php echo my_theme_single_product_benefit_icon_svg($icon); ?><?php endif; ?>
    </span>
    <div class="product-benefit__content">
        <span class="product-benefit__title"><?php echo esc_html($title); ?></span>
        <span class="product-benefit__description"><?php echo esc_html($description); ?></span>
    </div>
</div>
<?php
    return trim(ob_get_clean());
}

/**
 * Render the static product benefits section below the first product area.
 *
 * @return string
 */
function my_theme_single_product_render_benefits_section($attributes = []) {
    $benefits = [
        [
            'visible'    => false !== ($attributes['showBenefit1'] ?? true),
            'icon'        => 'delivery',
            'title'       => sanitize_text_field($attributes['benefit1Title'] ?? __('ارسال سریع', 'my-theme')),
            'description' => sanitize_text_field($attributes['benefit1Description'] ?? __('با هماهنگی قبلی', 'my-theme')),
            'image_url' => esc_url_raw($attributes['benefit1ImageUrl'] ?? ''), 'image_alt' => sanitize_text_field($attributes['benefit1ImageAlt'] ?? ''),
        ],
        [
            'visible'    => false !== ($attributes['showBenefit2'] ?? true),
            'icon'        => 'return',
            'title'       => sanitize_text_field($attributes['benefit2Title'] ?? __('ضمانت مرجوعی', 'my-theme')),
            'description' => sanitize_text_field($attributes['benefit2Description'] ?? __('تا ۷ روز کاری', 'my-theme')),
            'image_url' => esc_url_raw($attributes['benefit2ImageUrl'] ?? ''), 'image_alt' => sanitize_text_field($attributes['benefit2ImageAlt'] ?? ''),
        ],
        [
            'visible'    => false !== ($attributes['showBenefit3'] ?? true),
            'icon'        => 'authenticity',
            'title'       => sanitize_text_field($attributes['benefit3Title'] ?? __('تضمین اصالت', 'my-theme')),
            'description' => sanitize_text_field($attributes['benefit3Description'] ?? __('از بهترین برندها', 'my-theme')),
            'image_url' => esc_url_raw($attributes['benefit3ImageUrl'] ?? ''), 'image_alt' => sanitize_text_field($attributes['benefit3ImageAlt'] ?? ''),
        ],
        [
            'visible'    => false !== ($attributes['showBenefit4'] ?? true),
            'icon'        => 'consultation',
            'title'       => sanitize_text_field($attributes['benefit4Title'] ?? __('مشاوره تخصصی', 'my-theme')),
            'description' => sanitize_text_field($attributes['benefit4Description'] ?? __('قبل و بعد از خرید', 'my-theme')),
            'image_url' => esc_url_raw($attributes['benefit4ImageUrl'] ?? ''), 'image_alt' => sanitize_text_field($attributes['benefit4ImageAlt'] ?? ''),
        ],
    ];

    ob_start();
    ?>
<div class="product-benefits-box">
    <div class="product-benefits-list" aria-label="<?php echo esc_attr__('مزایای خرید محصول', 'my-theme'); ?>">
        <?php
            foreach ($benefits as $benefit) {
                if (! $benefit['visible']) { continue; }
                echo my_theme_single_product_render_benefit_item($benefit['icon'], $benefit['title'], $benefit['description'], $benefit['image_url'], $benefit['image_alt']);
            }
            ?>
    </div>
</div>
<?php
    return trim(ob_get_clean());
}

/**
 * Render the product full description tab content.
 *
 * @param WC_Product|null $product Current WooCommerce product.
 * @return string
 */
function my_theme_single_product_render_description_tab($product = null, $more_label = '') {
    if (! $product instanceof WC_Product) {
        return '';
    }

    $description = $product->get_description();

    if ('' === trim(wp_strip_all_tags($description))) {
        return '';
    }

    $description_content = apply_filters('the_content', $description);

    ob_start();
    ?>
<div class="product-description-expandable" data-expandable-description>
    <div id="product-description-content" class="product-description-expandable__content is-collapsed">
        <?php echo $description_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>
    <button type="button" class="product-description-expandable__toggle" aria-expanded="false"
        aria-controls="product-description-content" hidden>
        <span class="product-description-expandable__toggle-text">
            <?php echo esc_html('' !== $more_label ? $more_label : __('بیشتر', 'my-theme')); ?>
        </span>
        <span class="product-description-expandable__toggle-icon">
            <?php echo my_theme_single_product_arrow_svg(); ?>
        </span>
    </button>
</div>
<?php
    return trim(ob_get_clean());
}

/**
 * Render all visible WooCommerce product attributes for the specifications tab.
 *
 * @param WC_Product|null $product Current WooCommerce product.
 * @return string
 */
function my_theme_single_product_render_specifications_tab($product = null, $view_all_label = '') {
    if (! $product instanceof WC_Product) {
        return '';
    }

    $attributes = my_theme_single_product_get_visible_attributes($product);

    if (empty($attributes)) {
        return '';
    }

    ob_start();
    ?>
<dl class="product-specifications-list" data-mobile-specifications-list>
    <?php foreach ($attributes as $index => $attribute) : ?>
    <div
        class="product-specifications-list__item product-specifications-row<?php echo $index >= 5 ? ' product-specifications-row--extra' : ''; ?>"
        <?php echo $index >= 5 ? 'data-mobile-specification-extra' : ''; ?>>
        <dt class="product-specifications-list__name"><?php echo esc_html($attribute['label']); ?></dt>
        <dd class="product-specifications-list__value"><?php echo esc_html($attribute['value']); ?></dd>
    </div>
    <?php endforeach; ?>
</dl>
<?php if (count($attributes) > 5) : ?>
<button type="button" class="product-specifications-view-all" aria-expanded="false"
    data-mobile-specifications-toggle>
    <span class="product-specifications-view-all__text">
        <?php echo esc_html('' !== $view_all_label ? $view_all_label : __('مشاهده همه مشخصات', 'my-theme')); ?>
    </span>
    <span class="product-specifications-view-all__icon">
        <?php echo my_theme_single_product_specs_arrow_svg(); ?>
    </span>
</button>
<?php endif; ?>
<?php
    return trim(ob_get_clean());
}

/**
 * Render native WooCommerce reviews for the reviews tab.
 *
 * @param WC_Product|null $product Current WooCommerce product.
 * @return string
 */
function my_theme_single_product_render_mobile_review_card($comment) {
    $rating = (int) get_comment_meta($comment->comment_ID, 'rating', true);
    $rating = max(0, min(5, $rating));
    $hide_author = '1' === (string) get_comment_meta($comment->comment_ID, '_product_review_hide_author', true);
    $author = $hide_author ? __('کاربر ناشناس', 'my-theme') : trim((string) get_comment_author($comment));

    if ('' === $author && ! $hide_author && is_user_logged_in()) {
        $author = trim((string) $comment->comment_author_email);
    }

    if ('' === $author) {
        $author = __('کاربر', 'my-theme');
    }

    $vote_data = my_theme_single_product_get_review_vote_data((int) $comment->comment_ID);

    ob_start();
    ?>
<article class="product-review-card" data-review-id="<?php echo esc_attr($comment->comment_ID); ?>">
    <div class="product-review-card__header">
        <span class="product-review-card__author"><?php echo esc_html($author); ?></span>
        <time class="product-review-card__date"
            datetime="<?php echo esc_attr(get_comment_date('c', $comment)); ?>"><?php echo esc_html(my_theme_single_product_format_review_date($comment)); ?></time>
    </div>
    <div class="product-review-card__rating"
        aria-label="<?php echo esc_attr(sprintf(__('امتیاز %s از ۵', 'my-theme'), my_theme_single_product_persian_digits($rating))); ?>">
        <?php for ($star = 1; $star <= 5; ++$star) : ?>
        <span class="product-review-card__star">
            <?php echo my_theme_single_product_review_star_svg($star <= $rating); ?>
        </span>
        <?php endfor; ?>
    </div>
    <div class="product-review-card__content">
        <?php comment_text($comment); ?>
    </div>
    <div class="product-review-card__feedback" aria-live="polite">
        <button type="button"
            class="product-review-feedback product-review-feedback--like<?php echo 'like' === $vote_data['state'] ? ' is-selected' : ''; ?>"
            data-review-vote="like" data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>"
            aria-pressed="<?php echo 'like' === $vote_data['state'] ? 'true' : 'false'; ?>"
            aria-label="<?php echo esc_attr__('پسندیدن این دیدگاه', 'my-theme'); ?>">
            <?php echo my_theme_single_product_review_feedback_svg('like'); ?>
            <span class="product-review-feedback__count"
                data-review-vote-count="like"><?php echo esc_html(my_theme_single_product_persian_digits($vote_data['likes'])); ?></span>
        </button>
        <button type="button"
            class="product-review-feedback product-review-feedback--dislike<?php echo 'dislike' === $vote_data['state'] ? ' is-selected' : ''; ?>"
            data-review-vote="dislike" data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>"
            aria-pressed="<?php echo 'dislike' === $vote_data['state'] ? 'true' : 'false'; ?>"
            aria-label="<?php echo esc_attr__('نپسندیدن این دیدگاه', 'my-theme'); ?>">
            <?php echo my_theme_single_product_review_feedback_svg('dislike'); ?>
            <span class="product-review-feedback__count"
                data-review-vote-count="dislike"><?php echo esc_html(my_theme_single_product_persian_digits($vote_data['dislikes'])); ?></span>
        </button>
    </div>
</article>
<?php
    return trim(ob_get_clean());
}

/**
 * Render the custom mobile review cards from approved WooCommerce reviews.
 *
 * @param WC_Product $product Current WooCommerce product.
 * @return string
 */
function my_theme_single_product_render_mobile_reviews($product) {
    $comments = get_comments(
        [
            'post_id' => $product->get_id(),
            'status'  => 'approve',
            'type'    => 'review',
            'orderby' => 'comment_date_gmt',
            'order'   => 'DESC',
        ]
    );

    if (empty($comments)) {
        return '<div class="product-reviews-mobile-list"><p class="product-reviews-mobile-list__empty">' . esc_html__('هنوز دیدگاهی ثبت نشده است.', 'my-theme') . '</p></div>';
    }

    ob_start();
    ?>
<div class="product-reviews-mobile-list" aria-label="<?php echo esc_attr__('دیدگاه‌های مشتریان', 'my-theme'); ?>">
    <?php foreach ($comments as $comment) : ?>
    <?php echo my_theme_single_product_render_mobile_review_card($comment); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php endforeach; ?>
</div>
<?php
    return trim(ob_get_clean());
}

function my_theme_single_product_render_reviews_tab($product = null) {
    if (! $product instanceof WC_Product || ! comments_open($product->get_id())) {
        return '';
    }

    if (is_admin()) {
        return my_theme_single_product_render_mobile_reviews($product) . '<p class="product-details-tab-panel__editor-note">' . esc_html__('فرم ثبت دیدگاه در نمای سایت نمایش داده می‌شود.', 'my-theme') . '</p>';
    }

    if (is_admin()) {
        return '<p class="product-details-tab-panel__editor-note">' . esc_html__('دیدگاه‌های محصول در نمای سایت نمایش داده می‌شوند.', 'my-theme') . '</p>';
    }

    ob_start();

    $GLOBALS['product'] = $product;
    echo my_theme_single_product_render_mobile_reviews($product); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    comments_template();

    return trim(ob_get_clean());
}

/**
 * Handle AJAX voting for WooCommerce product reviews.
 *
 * @return void
 */
function my_theme_single_product_handle_review_vote() {
    check_ajax_referer('my_theme_review_vote', 'nonce');

    $comment_id = isset($_POST['commentId']) ? absint(wp_unslash($_POST['commentId'])) : 0;
    $vote       = isset($_POST['vote']) ? sanitize_key(wp_unslash($_POST['vote'])) : '';

    if (! in_array($vote, ['like', 'dislike'], true)) {
        wp_send_json_error(['message' => __('نوع رأی نامعتبر است.', 'my-theme')], 400);
    }

    $comment = get_comment($comment_id);

    if (! $comment instanceof WP_Comment || '1' !== (string) $comment->comment_approved || 'product' !== get_post_type($comment->comment_post_ID)) {
        wp_send_json_error(['message' => __('دیدگاه معتبر نیست.', 'my-theme')], 404);
    }

    $voter_key = my_theme_single_product_get_review_voter_key();

    if ('' === $voter_key && ! is_user_logged_in()) {
        $token = wp_generate_uuid4();
        $secure = is_ssl();
        setcookie('my_theme_review_vote_token', $token, time() + YEAR_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN, $secure, true);
        $_COOKIE['my_theme_review_vote_token'] = $token;
        $voter_key = my_theme_single_product_get_review_voter_key();
    }

    if ('' === $voter_key) {
        wp_send_json_error(['message' => __('امکان ثبت رأی وجود ندارد.', 'my-theme')], 403);
    }

    $votes = get_comment_meta($comment_id, '_my_theme_review_votes', true);
    $votes = is_array($votes) ? $votes : [];

    $votes[$voter_key] = $vote;
    update_comment_meta($comment_id, '_my_theme_review_votes', $votes);

    $likes = 0;
    $dislikes = 0;

    foreach ($votes as $stored_vote) {
        if ('like' === $stored_vote) {
            ++$likes;
        } elseif ('dislike' === $stored_vote) {
            ++$dislikes;
        }
    }

    update_comment_meta($comment_id, '_my_theme_review_like_count', $likes);
    update_comment_meta($comment_id, '_my_theme_review_dislike_count', $dislikes);

    wp_send_json_success(
        [
            'likes'    => $likes,
            'dislikes' => $dislikes,
            'state'    => $vote,
            'likesText' => my_theme_single_product_persian_digits($likes),
            'dislikesText' => my_theme_single_product_persian_digits($dislikes),
        ]
    );
}
add_action('wp_ajax_my_theme_review_vote', 'my_theme_single_product_handle_review_vote');
add_action('wp_ajax_nopriv_my_theme_review_vote', 'my_theme_single_product_handle_review_vote');

/**
 * Return a mobile rating star button.
 *
 * @param int $rating Rating value.
 * @return string
 */
function my_theme_single_product_render_form_rating_star($rating) {
    return sprintf(
        '<button type="button" class="product-review-rating-star" data-review-rating-star="%1$d" aria-pressed="false" aria-label="%2$s">%3$s</button>',
        absint($rating),
        esc_attr(sprintf(__('%s ستاره', 'my-theme'), my_theme_single_product_persian_digits($rating))),
        my_theme_single_product_review_form_star_svg()
    );
}

/**
 * Customize the native WooCommerce review form markup.
 *
 * @param array $args WooCommerce review form args.
 * @return array
 */
function my_theme_single_product_review_form_args($args) {
    $commenter = wp_get_current_commenter();
    $user      = wp_get_current_user();
    $name      = is_user_logged_in() ? $user->display_name : ($commenter['comment_author'] ?? '');
    $phone     = is_user_logged_in() ? get_user_meta(get_current_user_id(), 'billing_phone', true) : '';
    $stars     = '';

    for ($rating = 1; $rating <= 5; ++$rating) {
        $stars .= my_theme_single_product_render_form_rating_star($rating);
    }

    $args['title_reply']         = '';
    $args['title_reply_before']  = '';
    $args['title_reply_after']   = '';
    $args['comment_notes_before']= '';
    $args['comment_notes_after'] = '';
    $args['label_submit']        = __('ثبت دیدگاه', 'my-theme');
    $args['class_submit']        = 'submit product-review-form__submit';
    $args['class_form']          = trim(($args['class_form'] ?? 'comment-form') . ' product-review-form');

    $args['comment_field'] =
        '<p class="product-review-form__intro">' . esc_html__('دیدگاه خود را با ما به اشتراک بگذارید.', 'my-theme') . '</p>' .
        '<div class="product-review-form__rating-row">' .
            '<label class="product-review-form__label" for="rating">' . esc_html__('امتیاز شما*', 'my-theme') . '</label>' .
            '<span class="product-review-rating-stars" role="radiogroup" aria-label="' . esc_attr__('امتیاز شما', 'my-theme') . '">' . $stars . '</span>' .
            '<select name="rating" id="rating" class="product-review-rating-select product-review-form__native-rating" required aria-required="true">' .
                '<option value="">' . esc_html__('انتخاب امتیاز', 'my-theme') . '</option>' .
                '<option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option>' .
            '</select>' .
        '</div>';

    if (is_user_logged_in()) {
        $args['comment_field'] .=
            '<p class="comment-form-author product-review-form__field product-review-form__name-field">' .
                '<label class="product-review-form__label" for="author">' . esc_html__('نام و نام خانوادگی*', 'my-theme') . '</label>' .
                '<input id="author" name="my_theme_review_author_name" type="text" value="' . esc_attr($name) . '" required autocomplete="name" />' .
            '</p>';
    }

    $args['comment_field'] .=
        '<p class="comment-form-phone product-review-form__field product-review-form__phone-field">' .
            '<label class="product-review-form__label" for="my_theme_review_phone">' . esc_html__('شماره موبایل*', 'my-theme') . '</label>' .
            '<input id="my_theme_review_phone" name="my_theme_review_phone" type="tel" inputmode="tel" value="' . esc_attr($phone) . '" required autocomplete="tel" />' .
        '</p>' .
        '<p class="comment-form-comment product-review-form__field product-review-form__comment-field">' .
            '<label class="product-review-form__label" for="comment">' . esc_html__('دیدگاه*', 'my-theme') . '</label>' .
            '<textarea id="comment" name="comment" cols="45" rows="8" required></textarea>' .
        '</p>' .
        '<label class="product-review-form__anonymous-row" for="product-review-hide-author">' .
                '<input id="product-review-hide-author" class="product-review-form__anonymous-checkbox" name="product_review_hide_author" type="checkbox" value="1" />' .
                '<span class="product-review-form__anonymous-text">' . esc_html__('عدم نمایش نام شما در دیدگاه', 'my-theme') . '</span>' .
            '</label>';

    if (! is_user_logged_in()) {
        $name_required = (bool) get_option('require_name_email', 1);
        $required_attr = $name_required ? ' required' : '';

        $args['fields']['author'] =
            '<p class="comment-form-author product-review-form__field product-review-form__name-field">' .
                '<label class="product-review-form__label" for="author">' . esc_html__('نام و نام خانوادگی*', 'my-theme') . '</label>' .
                '<input id="author" name="author" type="text" value="' . esc_attr($commenter['comment_author'] ?? '') . '"' . $required_attr . ' autocomplete="name" />' .
            '</p>';

        if (isset($args['fields']['email'])) {
            $args['fields']['email'] = str_replace('<p class="comment-form-email">', '<p class="comment-form-email product-review-form__field">', $args['fields']['email']);
        }
    }

    return $args;
}
add_filter('woocommerce_product_review_comment_form_args', 'my_theme_single_product_review_form_args');

/**
 * Validate mobile review extra fields before WordPress stores the comment.
 *
 * @param array $commentdata Comment data.
 * @return array
 */
function my_theme_single_product_validate_review_extra_fields($commentdata) {
    if (empty($commentdata['comment_post_ID']) || 'product' !== get_post_type((int) $commentdata['comment_post_ID'])) {
        return $commentdata;
    }

    $phone = isset($_POST['my_theme_review_phone']) ? sanitize_text_field(wp_unslash($_POST['my_theme_review_phone'])) : '';

    if ('' === trim($phone)) {
        wp_die(esc_html__('لطفاً شماره موبایل را وارد کنید.', 'my-theme'), esc_html__('خطا در ثبت دیدگاه', 'my-theme'), ['response' => 400]);
    }

    if (! is_user_logged_in()) {
        return $commentdata;
    }

    $author_name = isset($_POST['my_theme_review_author_name']) ? sanitize_text_field(wp_unslash($_POST['my_theme_review_author_name'])) : '';

    if ('' === trim($author_name)) {
        wp_die(esc_html__('لطفاً نام و نام خانوادگی را وارد کنید.', 'my-theme'), esc_html__('خطا در ثبت دیدگاه', 'my-theme'), ['response' => 400]);
    }

    $commentdata['comment_author'] = $author_name;

    return $commentdata;
}
add_filter('preprocess_comment', 'my_theme_single_product_validate_review_extra_fields');

/**
 * Store protected mobile review extra fields as comment metadata.
 *
 * @param int $comment_id Comment ID.
 * @return void
 */
function my_theme_single_product_save_review_extra_fields($comment_id) {
    $comment = get_comment($comment_id);

    if (! $comment instanceof WP_Comment || 'product' !== get_post_type($comment->comment_post_ID)) {
        return;
    }

    $phone = isset($_POST['my_theme_review_phone']) ? sanitize_text_field(wp_unslash($_POST['my_theme_review_phone'])) : '';
    $hide_author = (isset($_POST['product_review_hide_author']) || isset($_POST['my_theme_review_hide_author'])) ? '1' : '0';

    if ('' !== $phone) {
        update_comment_meta($comment_id, '_product_review_phone', $phone);
    }

    update_comment_meta($comment_id, '_product_review_hide_author', $hide_author);
}
add_action('comment_post', 'my_theme_single_product_save_review_extra_fields');

/**
 * Return brand terms from the same sources used by the product metadata summary.
 *
 * @param WC_Product $product Current WooCommerce product.
 * @return WP_Term[]
 */
function my_theme_single_product_get_brand_terms($product) {
    foreach (my_theme_get_product_brand_taxonomies() as $taxonomy) {
        if (! taxonomy_exists($taxonomy)) {
            continue;
        }

        $terms = get_the_terms($product->get_id(), $taxonomy);

        if (! empty($terms) && ! is_wp_error($terms)) {
            return array_values(array_filter($terms, static function ($term) {
                return $term instanceof WP_Term;
            }));
        }
    }

    return [];
}

/**
 * Normalize a brand label for matching taxonomy terms to Brand CPT posts.
 *
 * @param string $value Brand label, slug, or title.
 * @return string
 */
function my_theme_single_product_normalize_brand_label($value) {
    $value = rawurldecode((string) $value);
    $value = wp_strip_all_tags($value);
    $value = trim($value);
    $value = preg_replace('/^(برند|brand)[\s\-_]*/iu', '', $value);
    $value = preg_replace('/\s+/u', ' ', $value);

    $value = trim((string) $value);

    return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
}

/**
 * Return possible Brand CPT IDs from a product meta value.
 *
 * @param mixed $value Product meta value.
 * @return int[]
 */
function my_theme_single_product_extract_brand_post_ids($value) {
    $ids = [];

    if (is_array($value)) {
        foreach ($value as $item) {
            $ids = array_merge($ids, my_theme_single_product_extract_brand_post_ids($item));
        }

        return $ids;
    }

    if (is_numeric($value)) {
        return [absint($value)];
    }

    if (is_string($value) && '' !== trim($value)) {
        $decoded = maybe_unserialize($value);

        if ($decoded !== $value) {
            return my_theme_single_product_extract_brand_post_ids($decoded);
        }

        if (preg_match_all('/\d+/', $value, $matches)) {
            foreach ($matches[0] as $match) {
                $ids[] = absint($match);
            }
        }
    }

    return array_values(array_filter(array_unique($ids)));
}

/**
 * Validate and return a published Brand CPT post.
 *
 * @param int $brand_post_id Brand post ID.
 * @return WP_Post|null
 */
function my_theme_single_product_get_valid_brand_post($brand_post_id) {
    $brand_post = get_post(absint($brand_post_id));

    if (
        ! $brand_post instanceof WP_Post ||
        'brands' !== get_post_type($brand_post) ||
        'publish' !== get_post_status($brand_post)
    ) {
        return null;
    }

    return $brand_post;
}

/**
 * Find the matching Brand CPT post for a product brand taxonomy term.
 *
 * @param WP_Term $brand_term Assigned brand term.
 * @return WP_Post|null
 */
function my_theme_single_product_get_brand_post_from_term($brand_term) {
    if (! $brand_term instanceof WP_Term) {
        return null;
    }

    $term_candidates = array_filter(array_unique([
        my_theme_single_product_normalize_brand_label($brand_term->name),
        my_theme_single_product_normalize_brand_label($brand_term->slug),
        my_theme_single_product_normalize_brand_label(sanitize_title($brand_term->name)),
        preg_replace('/\D+/', '', rawurldecode((string) $brand_term->name)),
        preg_replace('/\D+/', '', rawurldecode((string) $brand_term->slug)),
    ]));

    if (empty($term_candidates)) {
        return null;
    }

    $brand_posts = get_posts([
        'post_type'              => 'brands',
        'post_status'            => 'publish',
        'posts_per_page'         => -1,
        'orderby'                => 'menu_order title',
        'order'                  => 'ASC',
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ]);

    foreach ($brand_posts as $brand_post) {
        $post_candidates = array_filter(array_unique([
            my_theme_single_product_normalize_brand_label($brand_post->post_title),
            my_theme_single_product_normalize_brand_label($brand_post->post_name),
        ]));

        if (array_intersect($term_candidates, $post_candidates)) {
            return $brand_post;
        }
    }

    return null;
}

/**
 * Return the assigned Brand CPT post for a product.
 *
 * @param WC_Product $product Current WooCommerce product.
 * @return WP_Post|null
 */
function my_theme_single_product_get_assigned_brand_post($product) {
    if (! $product instanceof WC_Product) {
        return null;
    }

    $meta_keys = [
        'brand',
        'brands',
        'brand_id',
        'brands_id',
        '_brand',
        '_brands',
        '_brand_id',
        '_brands_id',
        'product_brand',
        'product_brand_id',
        '_product_brand',
        '_product_brand_id',
    ];

    foreach ($meta_keys as $meta_key) {
        $brand_post_ids = my_theme_single_product_extract_brand_post_ids(get_post_meta($product->get_id(), $meta_key, true));

        foreach ($brand_post_ids as $brand_post_id) {
            $brand_post = my_theme_single_product_get_valid_brand_post($brand_post_id);

            if ($brand_post instanceof WP_Post) {
                return $brand_post;
            }
        }
    }

    foreach (my_theme_single_product_get_brand_terms($product) as $brand_term) {
        $brand_post = my_theme_single_product_get_brand_post_from_term($brand_term);

        if ($brand_post instanceof WP_Post) {
            return $brand_post;
        }
    }

    return null;
}

/**
 * Return filtered Gutenberg content for the assigned Brand CPT post.
 *
 * @param WP_Post $brand_post Assigned Brand post.
 * @return string
 */
function my_theme_single_product_get_brand_post_content($brand_post) {
    if (! $brand_post instanceof WP_Post) {
        return '';
    }

    $brand_description_raw = get_post_field('post_content', $brand_post->ID);

    if ('' === trim(wp_strip_all_tags((string) $brand_description_raw))) {
        return '';
    }

    return apply_filters('the_content', $brand_description_raw);
}

/**
 * Render Brand CPT Gutenberg content for the existing assigned product brand.
 *
 * @param WC_Product|null $product Current WooCommerce product.
 * @return string
 */
function my_theme_single_product_render_brand_tab($product = null) {
    if (! $product instanceof WC_Product) {
        return '';
    }

    $brand_post = my_theme_single_product_get_assigned_brand_post($product);

    if (! $brand_post instanceof WP_Post) {
        return '';
    }

    $brand_description = my_theme_single_product_get_brand_post_content($brand_post);

    if ('' === $brand_description) {
        return '';
    }

    ob_start();
    ?>
<div class="product-brand-panel">
    <article class="product-brand-panel__item">
        <div class="product-brand-panel__description product-brand-content">
            <div class="product-brand-expandable" data-expandable-brand>
                <div id="product-brand-content-<?php echo esc_attr($product->get_id() . '-' . $brand_post->ID); ?>"
                    class="product-brand-expandable__content is-collapsed">
                    <?php echo $brand_description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
                <button type="button" class="product-brand-expandable__toggle" aria-expanded="false"
                    aria-controls="product-brand-content-<?php echo esc_attr($product->get_id() . '-' . $brand_post->ID); ?>" hidden>
                    <span class="product-brand-expandable__toggle-text">
                        <?php echo esc_html__('بیشتر', 'my-theme'); ?>
                    </span>
                    <span class="product-brand-expandable__toggle-icon" aria-hidden="true">
                        <?php echo my_theme_single_product_arrow_svg(); ?>
                    </span>
                </button>
            </div>
        </div>
    </article>
</div>
<?php
    return trim(ob_get_clean());
}

/**
 * Render the tabbed product details section.
 *
 * @param WC_Product|null $product Current WooCommerce product.
 * @return string
 */
function my_theme_single_product_render_details_tabs($product = null, $attributes = []) {
    $tabs = [
        [
            'key'     => 'description',
            'label'   => sanitize_text_field($attributes['descriptionLabel'] ?? __('توضیحات', 'my-theme')),
            'panel_id'=> 'product-tab-description',
            'mobile_id' => 'product-details-description',
            'mobile_heading_id' => 'product-details-description-title',
            'content' => my_theme_single_product_render_description_tab($product, sanitize_text_field($attributes['moreLabel'] ?? '')),
        ],
        [
            'key'     => 'specifications',
            'label'   => sanitize_text_field($attributes['specificationsLabel'] ?? __('مشخصات', 'my-theme')),
            'panel_id'=> 'product-specifications',
            'mobile_id' => 'product-details-specifications',
            'mobile_heading_id' => 'product-details-specifications-title',
            'content' => my_theme_single_product_render_specifications_tab($product, sanitize_text_field($attributes['viewAllSpecificationsLabel'] ?? '')),
        ],
        [
            'key'     => 'reviews',
            'label'   => sanitize_text_field($attributes['reviewsLabel'] ?? __('دیدگاه‌ها', 'my-theme')),
            'panel_id'=> 'product-tab-reviews',
            'mobile_id' => 'product-details-reviews',
            'mobile_heading_id' => 'product-details-reviews-title',
            'mobile_label' => sanitize_text_field($attributes['reviewsMobileLabel'] ?? __('امتیاز و دیدگاه مشتری‌ها', 'my-theme')),
            'content' => my_theme_single_product_render_reviews_tab($product),
        ],
        [
            'key'     => 'brand',
            'label'   => sanitize_text_field($attributes['brandLabel'] ?? __('درباره برند', 'my-theme')),
            'panel_id'=> 'product-tab-brand',
            'mobile_id' => 'product-details-brand',
            'mobile_heading_id' => 'product-details-brand-title',
            'content' => my_theme_single_product_render_brand_tab($product),
        ],
    ];

    ob_start();
    ?>
<div class="product-details-tabs" data-product-tabs>
    <div class="product-details-tabs__list" role="tablist" data-product-details-tabs-list
        aria-label="<?php echo esc_attr(sanitize_text_field($attributes['tabsAriaLabel'] ?? __('اطلاعات محصول', 'my-theme'))); ?>">
        <?php foreach ($tabs as $index => $tab) : ?>
        <?php $is_active = 0 === $index; ?>
        <button type="button" class="product-details-tab<?php echo $is_active ? ' is-active' : ''; ?>" role="tab"
            id="product-tab-button-<?php echo esc_attr($tab['key']); ?>"
            aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
            aria-controls="<?php echo esc_attr($tab['panel_id']); ?>" tabindex="<?php echo $is_active ? '0' : '-1'; ?>"
            data-product-tab-target="<?php echo esc_attr($tab['panel_id']); ?>"
            data-product-details-target="<?php echo esc_attr($tab['key']); ?>">
            <?php echo esc_html($tab['label']); ?>
        </button>
        <?php endforeach; ?>
    </div>
    <div class="product-details-tabs__content">
        <?php foreach ($tabs as $index => $tab) : ?>
        <?php $is_active = 0 === $index; ?>
        <div id="<?php echo esc_attr($tab['panel_id']); ?>"
            class="product-details-tab-panel<?php echo $is_active ? ' is-active' : ''; ?>" role="tabpanel"
            aria-labelledby="product-tab-button-<?php echo esc_attr($tab['key']); ?>" tabindex="0"
            <?php echo $is_active ? '' : 'hidden'; ?>>
            <section id="<?php echo esc_attr($tab['mobile_id']); ?>"
                class="product-details-mobile-section product-details-mobile-section--<?php echo esc_attr($tab['key']); ?>"
                data-product-details-section="<?php echo esc_attr($tab['key']); ?>"
                aria-labelledby="<?php echo esc_attr($tab['mobile_heading_id']); ?>">
                <span class="product-details-mobile-section__scroll-anchor"
                    data-product-details-anchor="<?php echo esc_attr($tab['key']); ?>" aria-hidden="true"></span>
                <h2 id="<?php echo esc_attr($tab['mobile_heading_id']); ?>" class="product-details-mobile-section__title">
                    <?php echo esc_html(sprintf(__('%s:', 'my-theme'), $tab['mobile_label'] ?? $tab['label'])); ?>
                </h2>
                <div class="product-details-mobile-section__content">
            <?php
                    if ('' !== $tab['content']) {
                        echo $tab['content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    }
                    ?>
                </div>
            </section>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php
    return trim(ob_get_clean());
}

/**
 * Return related WooCommerce products for the current product.
 *
 * @param WC_Product|null $product Current WooCommerce product.
 * @return WC_Product[]
 */
function my_theme_single_product_get_related_products($product = null) {
    if (! $product instanceof WC_Product || ! function_exists('wc_get_related_products')) {
        return [];
    }

    $related_ids = wc_get_related_products($product->get_id(), 12, [$product->get_id()]);

    if (empty($related_ids)) {
        return [];
    }

    $related_products = [];

    foreach ($related_ids as $related_id) {
        $related_product = wc_get_product($related_id);

        if (! $related_product instanceof WC_Product || $related_product->get_id() === $product->get_id()) {
            continue;
        }

        if ('publish' !== get_post_status($related_product->get_id()) || ! $related_product->is_visible()) {
            continue;
        }

        $related_products[] = $related_product;
    }

    return $related_products;
}

/**
 * Render the related-products carousel below the product details tabs.
 *
 * @param WC_Product|null $product Current WooCommerce product.
 * @param array           $args    Render options.
 * @return string
 */
function my_theme_single_product_render_related_products_section($product = null, $args = []) {
    $related_products = my_theme_single_product_get_related_products($product);

    if (empty($related_products) || ! function_exists('my_theme_render_green_product_card')) {
        return '';
    }

    $args = wp_parse_args(
        $args,
        [
            'title'                     => __('محصولات مشابه', 'my-theme'),
            'carousel_extra_attributes' => '',
            'previous_label'            => __('محصولات قبلی', 'my-theme'),
            'next_label'                => __('محصولات بعدی', 'my-theme'),
        ]
    );

    ob_start();
    ?>
<div class="related-products-section__inner">
    <h2 class="related-products-section__title"><?php echo esc_html($args['title']); ?></h2>
    <div class="related-products-section__carousel related-products-carousel" data-product-carousel>
        <button type="button"
            class="related-products-carousel__arrow related-products-carousel__arrow--right green-prev"
            aria-label="<?php echo esc_attr($args['previous_label']); ?>">
            <svg width="8" height="12" viewBox="0 0 8 12" fill="none" xmlns="http://www.w3.org/2000/svg"
                aria-hidden="true" focusable="false">
                <path
                    d="M4.65201 4.90369C5.07995 5.29954 5.07995 5.97604 4.65201 6.37189L1.26628 9.50369C0.83833 9.89953 0.83833 10.576 1.26628 10.9719L1.30713 11.0097C1.69041 11.3642 2.28193 11.3642 2.66521 11.0097L7.67903 6.37189C8.10698 5.97604 8.10698 5.29954 7.67904 4.90369L2.66521 0.265901C2.28193 -0.0886329 1.69041 -0.0886338 1.30713 0.2659L1.26628 0.303687C0.83833 0.699535 0.83833 1.37604 1.26628 1.77189L4.65201 4.90369Z"
                    fill="currentColor" />
            </svg>
        </button>
        <div class="related-products-carousel__viewport">
            <div class="swiper greenProductSwiper related-products-carousel__swiper" dir="rtl"
                data-related-products-carousel data-slides-per-view="5" data-space-between="24"
                <?php echo $args['carousel_extra_attributes']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                <div class="swiper-wrapper">
                    <?php
                        foreach ($related_products as $related_product) {
                            echo my_theme_render_green_product_card(
                                $related_product,
                                [
                                    'show_promo_header' => false,
                                ]
                            );
                        }
                        if (count($related_products) > 5) :
                        ?>
                    <div class="swiper-slide related-products-carousel__spacer" aria-hidden="true"
                        data-carousel-spacer="true"></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <button type="button" class="related-products-carousel__arrow related-products-carousel__arrow--left green-next"
            aria-label="<?php echo esc_attr($args['next_label']); ?>">
            <svg width="8" height="12" viewBox="0 0 8 12" fill="none" xmlns="http://www.w3.org/2000/svg"
                aria-hidden="true" focusable="false">
                <path
                    d="M3.34799 4.90369C2.92005 5.29954 2.92005 5.97604 3.34799 6.37189L6.73372 9.50369C7.16167 9.89953 7.16167 10.576 6.73372 10.9719L6.69287 11.0097C6.30959 11.3642 5.71807 11.3642 5.33479 11.0097L0.320966 6.37189C-0.106978 5.97604 -0.106979 5.29954 0.320965 4.90369L5.33479 0.265901C5.71807 -0.0886329 6.30959 -0.0886338 6.69287 0.2659L6.73372 0.303687C7.16167 0.699535 7.16167 1.37604 6.73372 1.77189L3.34799 4.90369Z"
                    fill="currentColor" />
            </svg>
        </button>
    </div>
</div>
<?php
    return trim(ob_get_clean());
}

/**
 * Render the product title and custom rating row for the middle column.
 *
 * @param WC_Product $product Current WooCommerce product.
 * @return string
 */
function my_theme_single_product_render_details_header($product, $include_footer = true, $attributes = [], $part = 'all') {
    $product_title  = $product->get_title();
    $rating_count   = (int) $product->get_rating_count();
    $average_rating = $product->get_average_rating();
    $attributes         = my_theme_single_product_get_visible_attributes($product);
    $visible_attributes = array_slice($attributes, 0, 6);

    ob_start();
    ?>
<?php if (in_array($part, ['all','title'], true)) : ?><div class="single-product-title-wrapper">
    <h1 class="single-product-title product-title"><?php echo esc_html($product_title); ?></h1>
</div><?php endif; ?>
<?php if (in_array($part, ['all','rating'], true) && 0 < $rating_count && '' !== $average_rating) : ?>
<?php
        $formatted_average = my_theme_single_product_format_average_rating($average_rating);

        /* translators: %s: Number of customers who submitted a rating. */
        $rating_count_text = sprintf(_n('امتیاز %s خریدار', 'امتیاز %s خریدار', $rating_count, 'my-theme'), number_format_i18n($rating_count));

        /* translators: 1: Average rating, 2: Rating count text. */
        $rating_row_label = sprintf(__('امتیاز میانگین %1$s از 5، %2$s', 'my-theme'), $formatted_average, $rating_count_text);
        ?>
<div class="single-product-rating-row" aria-label="<?php echo esc_attr($rating_row_label); ?>">
    <span class="single-product-rating-row__star">
        <?php echo my_theme_single_product_rating_star_svg(); ?>
    </span>
    <span class="single-product-rating-row__average"><?php echo esc_html($formatted_average); ?></span>
    <span
        class="single-product-rating-row__count"><?php echo esc_html(sprintf(_x('(%s)', 'rating count wrapper', 'my-theme'), $rating_count_text)); ?></span>
</div>
<?php endif; ?>
<?php if (in_array($part, ['all','attributes'], true) && ! empty($visible_attributes)) : ?>
<div class="single-product-attributes" aria-label="<?php echo esc_attr__('ویژگی‌های محصول', 'my-theme'); ?>">
    <dl class="single-product-attributes__list">
        <?php foreach ($visible_attributes as $attribute) : ?>
        <div class="single-product-attributes__item">
            <span class="single-product-attributes__bullet" aria-hidden="true"></span>
            <dt class="single-product-attributes__name"><?php echo esc_html($attribute['label']); ?>:</dt>
            <dd class="single-product-attributes__value"><?php echo esc_html($attribute['value']); ?></dd>
        </div>
        <?php endforeach; ?>
    </dl>
    <button class="single-product-attributes__button" type="button" data-product-details-target="specifications"
        data-product-details-view-all aria-controls="product-details-specifications">
        <span
            class="single-product-attributes__button-text"><?php echo esc_html(sanitize_text_field($attributes['featuresButtonLabel'] ?? __('مشاهده همه ویژگی‌ها', 'my-theme'))); ?></span>
        <span class="single-product-attributes__button-icon">
            <?php echo my_theme_single_product_arrow_svg(); ?>
        </span>
    </button>
</div>
<?php endif; ?>
<?php if ($include_footer && 'all' === $part) : ?>
<?php echo my_theme_single_product_render_actions_row($product); ?>
<?php echo my_theme_single_product_render_meta_summary($product); ?>
<?php endif; ?>
<?php
    return trim(ob_get_clean());
}

/**
 * Inject the dynamic middle-column header into the existing single-product group.
 *
 * @param string   $block_content Rendered block HTML.
 * @param array    $block         Parsed block data.
 * @param WP_Block $instance      Current block instance.
 * @return string
 */
function my_theme_render_single_product_details_group($block_content, $block, $instance) {
    if ('core/group' !== ($block['blockName'] ?? '')) {
        return $block_content;
    }

    $class_name = $block['attrs']['className'] ?? '';

    if (! str_contains($class_name, 'single-product-section__details')) {
        return $block_content;
    }

    if (str_contains($block_content, 'single-product-title')) {
        return $block_content;
    }

    $product = my_theme_single_product_get_current_product($instance);

    if (! $product instanceof WC_Product) {
        return $block_content;
    }

    $details_header = my_theme_single_product_render_details_header($product);

    if ('' === $details_header) {
        return $block_content;
    }

    $updated_content = preg_replace('/<\/div>\s*$/', $details_header . '</div>', $block_content, 1);

    return is_string($updated_content) ? $updated_content : $block_content;
}
add_filter('render_block', 'my_theme_render_single_product_details_group', 10, 3);

/**
 * Inject the empty purchase-card shell into the existing left column.
 *
 * @param string $block_content Rendered block HTML.
 * @param array  $block         Parsed block data.
 * @return string
 */
function my_theme_render_single_product_purchase_group($block_content, $block, $instance) {
    if ('core/group' !== ($block['blockName'] ?? '')) {
        return $block_content;
    }

    $class_name = $block['attrs']['className'] ?? '';

    if (! str_contains($class_name, 'single-product-section__purchase')) {
        return $block_content;
    }

    if (str_contains($block_content, 'single-product-purchase-card')) {
        return $block_content;
    }

    $product = my_theme_single_product_get_current_product($instance);
    $purchase_card = my_theme_single_product_render_purchase_card($product);
    $updated_content = preg_replace('/<\/div>\s*$/', $purchase_card . '</div>', $block_content, 1);

    return is_string($updated_content) ? $updated_content : $block_content;
}
add_filter('render_block', 'my_theme_render_single_product_purchase_group', 10, 3);

/**
 * Inject the static benefits strip into the single-product benefits section.
 *
 * @param string $block_content Rendered block HTML.
 * @param array  $block         Parsed block data.
 * @return string
 */
function my_theme_render_single_product_benefits_section($block_content, $block) {
    if ('core/group' !== ($block['blockName'] ?? '')) {
        return $block_content;
    }

    $class_name = $block['attrs']['className'] ?? '';

    if (! str_contains($class_name, 'product-benefits-section')) {
        return $block_content;
    }

    if (str_contains($block_content, 'product-benefits-box')) {
        return $block_content;
    }

    $benefits_section = my_theme_single_product_render_benefits_section();

    if ('' === $benefits_section) {
        return $block_content;
    }

    $updated_content = preg_replace('/<\/section>\s*$/', $benefits_section . '</section>', $block_content, 1);

    return is_string($updated_content) ? $updated_content : $block_content;
}
add_filter('render_block', 'my_theme_render_single_product_benefits_section', 10, 2);

/**
 * Inject the tabbed product details panel into the single-product template.
 *
 * @param string   $block_content Rendered block HTML.
 * @param array    $block         Parsed block data.
 * @param WP_Block $instance      Current block instance.
 * @return string
 */
function my_theme_render_single_product_details_tabs_section($block_content, $block, $instance) {
    if ('core/group' !== ($block['blockName'] ?? '')) {
        return $block_content;
    }

    $class_name = $block['attrs']['className'] ?? '';

    if (! str_contains($class_name, 'product-details-tabs-section')) {
        return $block_content;
    }

    if (str_contains($block_content, 'data-product-tabs')) {
        return $block_content;
    }

    $product = my_theme_single_product_get_current_product($instance);
    $tabs    = my_theme_single_product_render_details_tabs($product);

    if ('' === $tabs) {
        return $block_content;
    }

    $updated_content = preg_replace('/<\/section>\s*$/', $tabs . '</section>', $block_content, 1);

    return is_string($updated_content) ? $updated_content : $block_content;
}
add_filter('render_block', 'my_theme_render_single_product_details_tabs_section', 10, 3);

/**
 * Append the tabbed product details panel immediately after the benefits section
 * when a saved Site Editor template does not yet contain the tabs placeholder.
 *
 * @param string   $block_content Rendered block HTML.
 * @param array    $block         Parsed block data.
 * @param WP_Block $instance      Current block instance.
 * @return string
 */
function my_theme_append_single_product_details_tabs_to_main($block_content, $block, $instance) {
    if ('core/group' !== ($block['blockName'] ?? '')) {
        return $block_content;
    }

    $class_name = $block['attrs']['className'] ?? '';

    if (! str_contains($class_name, 'single-product-page')) {
        return $block_content;
    }

    if (str_contains($block_content, 'data-product-tabs')) {
        return $block_content;
    }

    $product = my_theme_single_product_get_current_product($instance);
    $tabs    = my_theme_single_product_render_details_tabs($product);

    if ('' === $tabs) {
        return $block_content;
    }

    if (! str_contains($block_content, 'product-benefits-section')) {
        return $block_content;
    }

    $tabs_section    = '<section class="wp-block-group product-details-tabs-section">' . $tabs . '</section>';
    $updated_content = preg_replace(
        '/(<section\b[^>]*class="[^"]*\bproduct-benefits-section\b[^"]*"[^>]*>.*?<\/section>)/s',
        '$1' . $tabs_section,
        $block_content,
        1
    );

    return is_string($updated_content) ? $updated_content : $block_content;
}
add_filter('render_block', 'my_theme_append_single_product_details_tabs_to_main', 20, 3);

/**
 * Inject the related-products carousel into its single-product section.
 *
 * @param string   $block_content Rendered block HTML.
 * @param array    $block         Parsed block data.
 * @param WP_Block $instance      Current block instance.
 * @return string
 */
function my_theme_render_single_product_related_products_section($block_content, $block, $instance) {
    if ('core/group' !== ($block['blockName'] ?? '')) {
        return $block_content;
    }

    $class_name = $block['attrs']['className'] ?? '';

    if (! str_contains($class_name, 'related-products-section') || str_contains($class_name, 'connected-products-section')) {
        return $block_content;
    }

    if (str_contains($block_content, 'data-related-products-carousel')) {
        return $block_content;
    }

    $product          = my_theme_single_product_get_current_product($instance);
    $related_products = my_theme_single_product_render_related_products_section($product);

    if ('' === $related_products) {
        return '';
    }

    $updated_content = preg_replace('/<\/section>\s*$/', $related_products . '</section>', $block_content, 1);

    return is_string($updated_content) ? $updated_content : $block_content;
}
add_filter('render_block', 'my_theme_render_single_product_related_products_section', 10, 3);

/**
 * Inject the connected-products carousel into its single-product section.
 *
 * @param string   $block_content Rendered block HTML.
 * @param array    $block         Parsed block data.
 * @param WP_Block $instance      Current block instance.
 * @return string
 */
function my_theme_render_single_product_connected_products_section($block_content, $block, $instance) {
    if ('core/group' !== ($block['blockName'] ?? '')) {
        return $block_content;
    }

    $class_name = $block['attrs']['className'] ?? '';

    if (! str_contains($class_name, 'connected-products-section')) {
        return $block_content;
    }

    if (str_contains($block_content, 'data-connected-products-carousel')) {
        return $block_content;
    }

    $product            = my_theme_single_product_get_current_product($instance);
    $connected_products = my_theme_single_product_render_related_products_section(
        $product,
        [
            'title'                     => __('محصولات مرتبط', 'my-theme'),
            'carousel_extra_attributes' => 'data-connected-products-carousel',
        ]
    );

    if ('' === $connected_products) {
        return '';
    }

    $updated_content = preg_replace('/<\/section>\s*$/', $connected_products . '</section>', $block_content, 1);

    return is_string($updated_content) ? $updated_content : $block_content;
}
add_filter('render_block', 'my_theme_render_single_product_connected_products_section', 10, 3);

/**
 * Append related products after the tabs when a saved Site Editor template does
 * not yet contain the related-products placeholder.
 *
 * @param string   $block_content Rendered block HTML.
 * @param array    $block         Parsed block data.
 * @param WP_Block $instance      Current block instance.
 * @return string
 */
function my_theme_append_single_product_related_products_to_main($block_content, $block, $instance) {
    if ('core/group' !== ($block['blockName'] ?? '')) {
        return $block_content;
    }

    $class_name = $block['attrs']['className'] ?? '';

    if (! str_contains($class_name, 'single-product-page')) {
        return $block_content;
    }

    if (str_contains($block_content, 'data-related-products-carousel')) {
        return $block_content;
    }

    if (! str_contains($block_content, 'product-details-tabs-section')) {
        return $block_content;
    }

    $product          = my_theme_single_product_get_current_product($instance);
    $related_products = my_theme_single_product_render_related_products_section($product);

    if ('' === $related_products) {
        return $block_content;
    }

    $related_section = '<section class="wp-block-group related-products-section">' . $related_products . '</section>';
    $updated_content = preg_replace(
        '/(<section\b[^>]*class="[^"]*\bproduct-details-tabs-section\b[^"]*"[^>]*>.*?<\/section>)/s',
        '$1' . $related_section,
        $block_content,
        1
    );

    return is_string($updated_content) ? $updated_content : $block_content;
}
add_filter('render_block', 'my_theme_append_single_product_related_products_to_main', 30, 3);

/**
 * Append connected products after the related-products section when a saved Site
 * Editor template does not yet contain the connected-products placeholder.
 *
 * @param string   $block_content Rendered block HTML.
 * @param array    $block         Parsed block data.
 * @param WP_Block $instance      Current block instance.
 * @return string
 */
function my_theme_append_single_product_connected_products_to_main($block_content, $block, $instance) {
    if ('core/group' !== ($block['blockName'] ?? '')) {
        return $block_content;
    }

    $class_name = $block['attrs']['className'] ?? '';

    if (! str_contains($class_name, 'single-product-page')) {
        return $block_content;
    }

    if (str_contains($block_content, 'data-connected-products-carousel')) {
        return $block_content;
    }

    if (! str_contains($block_content, 'related-products-section')) {
        return $block_content;
    }

    $product            = my_theme_single_product_get_current_product($instance);
    $connected_products = my_theme_single_product_render_related_products_section(
        $product,
        [
            'title'                     => __('محصولات مرتبط', 'my-theme'),
            'carousel_extra_attributes' => 'data-connected-products-carousel',
        ]
    );

    if ('' === $connected_products) {
        return $block_content;
    }

    $connected_section = '<section class="wp-block-group connected-products-section related-products-section">' . $connected_products . '</section>';
    $updated_content   = preg_replace(
        '/(<section\b[^>]*class="[^"]*\brelated-products-section\b[^"]*"[^>]*>.*?<\/section>)/s',
        '$1' . $connected_section,
        $block_content,
        1
    );

    return is_string($updated_content) ? $updated_content : $block_content;
}
add_filter('render_block', 'my_theme_append_single_product_connected_products_to_main', 40, 3);
