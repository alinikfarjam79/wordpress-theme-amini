<?php
/**
 * About Us page content.
 *
 * @package MyTheme
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Determine whether the current request is an About Us page.
 *
 * @return bool
 */
function my_theme_is_about_page_request() {
    if (! is_page()) {
        return false;
    }

    $queried_object = get_queried_object();

    if ($queried_object instanceof WP_Post) {
        $about_slugs = [
            'about',
            'about-us',
            'aboutus',
            'درباره-ما',
        ];

        if (in_array($queried_object->post_name, $about_slugs, true)) {
            return true;
        }

        $template_slug = (string) get_page_template_slug($queried_object);

        if (
            false !== strpos($template_slug, 'page-about')
            || false !== strpos($template_slug, 'about-us')
        ) {
            return true;
        }
    }

    return is_page(['about', 'about-us', 'aboutus', 'درباره-ما']);
}

/**
 * Render the first About Us history section.
 *
 * @return string
 */
function my_theme_render_about_history_section() {
    $image_url = get_theme_file_uri('assets/images/about-history-placeholder.svg');

    ob_start();
    ?>
<section class="about-history-section" dir="rtl" aria-labelledby="about-history-title">
    <div class="about-history">
        <figure class="about-history__media">
            <img
                src="<?php echo esc_url($image_url); ?>"
                alt="<?php echo esc_attr__('نمایی از فروشگاه رنگ امینی', 'my-theme'); ?>"
                loading="eager"
                decoding="async"
            >
        </figure>

        <div class="about-history__content">
            <h1 id="about-history-title" class="about-history__title">
                <?php echo esc_html__('تاریخچه مجموعه رنگ امینی', 'my-theme'); ?>
            </h1>

            <div class="about-history__text">
                <p><?php echo esc_html__('داستان فروشگاه رنگ امینی از سال ۱۳۵۹ آغاز شد، زمانی که حاج سام امینی، بنیان‌گذار این مجموعه، فعالیت خود را در حوزه فروش رنگ و ابزار آغاز کرد.', 'my-theme'); ?></p>

                <p><?php echo esc_html__('با گذشت زمان، گسترش فعالیت‌ها و افزایش تنوع محصولات، فروشگاه رنگ امینی به یکی از مراکز تخصصی و شناخته‌شده فروش رنگ و ابزار در کرمانشاه تبدیل شد.', 'my-theme'); ?></p>

                <p><?php echo esc_html__('در طول این سال‌ها، تجربه، اعتماد مشتریان و دانش نسل‌های مختلف در کنار یکدیگر قرار گرفتند. امروز، مدیریت مجموعه در دست نسل دوم و سوم خانواده است، نسلی که در کنار حفظ میراث و ارزش‌های بنیان‌گذار مجموعه، نگاه تازه‌ای به آینده دارد.', 'my-theme'); ?></p>
            </div>
        </div>
    </div>
</section>
<?php
    return trim(ob_get_clean());
}

/**
 * Return the About gallery arrow SVG.
 *
 * @return string
 */
function my_theme_about_gallery_arrow_svg() {
    return '<svg width="10" height="16" viewBox="0 0 10 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M4.08141 6.88335C3.68454 7.27493 3.68454 7.91544 4.08141 8.30702L8.85472 13.0167C9.25159 13.4083 9.25159 14.0488 8.85472 14.4404L8.38663 14.9022C7.99722 15.2864 7.37135 15.2864 6.98194 14.9022L0.297628 8.30702C-0.0992453 7.91544 -0.0992453 7.27493 0.297628 6.88335L6.98193 0.288165C7.37135 -0.0960535 7.99722 -0.0960544 8.38663 0.288164L8.85472 0.750013C9.25159 1.14159 9.25159 1.78211 8.85472 2.17369L4.08141 6.88335Z" fill="#009E00"/></svg>';
}

/**
 * Render the About Us gallery slider section.
 *
 * @return string
 */
function my_theme_render_about_gallery_section() {
    $about_gallery_images = array_fill(
        0,
        5,
        'https://aminirang.ir/wp-content/uploads/2026/06/Photo.png'
    );

    ob_start();
    ?>
<section class="about-gallery-section" dir="rtl" aria-label="<?php echo esc_attr__('گالری تصاویر درباره ما', 'my-theme'); ?>">
    <div class="about-gallery-frame">
        <div class="about-gallery-slider swiper" data-about-gallery-slider>
            <div class="swiper-wrapper">
                <?php foreach ($about_gallery_images as $image_url) : ?>
                    <div class="swiper-slide about-gallery-slide">
                        <img src="<?php echo esc_url($image_url); ?>" alt="" loading="lazy" decoding="async">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <button
            class="about-gallery-arrow about-gallery-arrow--prev"
            type="button"
            aria-label="<?php echo esc_attr__('تصویر بعدی', 'my-theme'); ?>"
        >
            <?php echo my_theme_about_gallery_arrow_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </button>

        <button
            class="about-gallery-arrow about-gallery-arrow--next"
            type="button"
            aria-label="<?php echo esc_attr__('تصویر قبلی', 'my-theme'); ?>"
        >
            <?php echo my_theme_about_gallery_arrow_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </button>
    </div>
</section>
<?php
    return trim(ob_get_clean());
}

/**
 * Shortcode fallback for the About Us history section.
 *
 * @return string
 */
function my_theme_about_history_shortcode() {
    return my_theme_render_about_history_section();
}
add_shortcode('theme_about_history_section', 'my_theme_about_history_shortcode');

/**
 * Shortcode fallback for the About Us gallery section.
 *
 * @return string
 */
function my_theme_about_gallery_shortcode() {
    return my_theme_render_about_gallery_section();
}
add_shortcode('theme_about_gallery_section', 'my_theme_about_gallery_shortcode');

/**
 * Return the shared About feature icon SVG.
 *
 * @return string
 */
function my_theme_about_feature_icon_svg() {
    return '<svg width="56" height="56" viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><rect width="56" height="56" rx="28" fill="white"/><ellipse cx="26.7228" cy="26.5255" rx="8.17298" ry="7.97553" stroke="#009E00" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/><path d="M32.3418 32.4652L37.4499 37.45" stroke="#009E00" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/></svg>';
}

/**
 * Render the About Us features section.
 *
 * @return string
 */
function my_theme_render_about_features_section() {
    $features = [
        [
            'modifier'    => 'experience',
            'title'       => __('۴۷ سال تجربه', 'my-theme'),
            'description' => __('از سال ۱۳۵۹ در بازار رنگ و ابزار غرب کشور', 'my-theme'),
        ],
        [
            'modifier'    => 'consulting',
            'title'       => __('مشاوره تخصصی', 'my-theme'),
            'description' => __('کمک به شما برای انتخاب محصول مناسب', 'my-theme'),
        ],
        [
            'modifier'    => 'products',
            'title'       => __('تنوع محصولات', 'my-theme'),
            'description' => __('مجموعه‌ای گسترده از رنگ و ابزارهای مورد نیاز', 'my-theme'),
        ],
        [
            'modifier'    => 'pricing',
            'title'       => __('قیمت رقابتی', 'my-theme'),
            'description' => __('محصولات با کیفیت با قیمت‌های رقابتی', 'my-theme'),
        ],
    ];

    ob_start();
    ?>
<section class="about-features-section" dir="rtl" aria-labelledby="about-features-title">
    <div class="about-features-section__inner">
        <div class="about-features-section__layout">
            <div class="about-features-section__intro">
                <h2 id="about-features-title" class="about-features-section__title">
                    <?php echo esc_html__('آنچه در مجموعه رنگ امینی پیدا می‌کنید', 'my-theme'); ?>
                </h2>

                <p class="about-features-section__description">
                    <?php echo esc_html__('هر آنچه برای یک انتخاب مطمئن نیاز دارید', 'my-theme'); ?>
                </p>
            </div>

            <div class="about-features-section__grid">
                <?php foreach ($features as $feature) : ?>
                    <div class="about-feature-item about-feature-item--<?php echo esc_attr($feature['modifier']); ?>">
                        <div class="about-feature-item__icon">
                            <?php echo my_theme_about_feature_icon_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>

                        <div class="about-feature-item__content">
                            <p class="about-feature-item__title"><?php echo esc_html($feature['title']); ?></p>
                            <p class="about-feature-item__description"><?php echo esc_html($feature['description']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php
    return trim(ob_get_clean());
}

/**
 * Shortcode fallback for the About Us features section.
 *
 * @return string
 */
function my_theme_about_features_shortcode() {
    return my_theme_render_about_features_section();
}
add_shortcode('theme_about_features_section', 'my_theme_about_features_shortcode');

/**
 * Remove the legacy custom data attribute from saved About templates.
 *
 * Core Group blocks do not declare arbitrary data attributes in their saved
 * markup. Keeping the old attribute makes Gutenberg report the block as
 * containing unexpected or invalid content.
 *
 * @return void
 */
function my_theme_migrate_about_brands_group_markup() {
    if ('1' === get_option('my_theme_about_brands_group_markup_migrated')) {
        return;
    }

    $templates = get_posts([
        'post_type'      => ['wp_template', 'wp_template_part'],
        'post_status'    => ['publish', 'draft', 'private'],
        'posts_per_page' => -1,
        'no_found_rows'  => true,
    ]);

    foreach ($templates as $template) {
        if (
            false === strpos($template->post_content, 'about-brands-slider') ||
            false === strpos($template->post_content, 'data-about-brands-slider')
        ) {
            continue;
        }

        $updated_content = str_replace(
            ' data-about-brands-slider',
            '',
            $template->post_content
        );

        if ($updated_content === $template->post_content) {
            continue;
        }

        wp_update_post([
            'ID'           => $template->ID,
            'post_content' => $updated_content,
        ]);
    }

    update_option('my_theme_about_brands_group_markup_migrated', '1', false);
}
add_action('admin_init', 'my_theme_migrate_about_brands_group_markup');

/**
 * Register the dynamic block used by About page templates.
 *
 * @return void
 */
function my_theme_register_about_history_block() {
    if (! function_exists('register_block_type')) {
        return;
    }

    register_block_type(get_theme_file_path('blocks/about-history'));
    register_block_type(get_theme_file_path('blocks/about-gallery'));
    register_block_type(get_theme_file_path('blocks/about-features'));
}
add_action('init', 'my_theme_register_about_history_block');

/**
 * Add editor-only controls for managing the About brands slider items.
 *
 * @return void
 */
function my_theme_enqueue_about_brands_editor_script() {
    $script_path = get_theme_file_path('assets/js/about-brands-editor.js');
    $asset_path  = get_theme_file_path('assets/js/about-brands-editor.asset.php');

    if (! file_exists($script_path) || ! file_exists($asset_path)) {
        return;
    }

    $asset = require $asset_path;

    wp_enqueue_script(
        'my-theme-about-brands-editor',
        get_theme_file_uri('assets/js/about-brands-editor.js'),
        $asset['dependencies'],
        $asset['version'],
        true
    );
}
add_action('enqueue_block_editor_assets', 'my_theme_enqueue_about_brands_editor_script', 30);

/**
 * Enqueue About Us styles only on the About Us route.
 *
 * @return void
 */
function my_theme_enqueue_about_styles() {
    if (! my_theme_is_about_page_request()) {
        return;
    }

    $stylesheet_path = get_theme_file_path('assets/css/about.css');

    if (! file_exists($stylesheet_path)) {
        return;
    }

    wp_enqueue_style(
        'my-theme-about',
        get_theme_file_uri('assets/css/about.css'),
        ['theme-style', 'theme-header', 'theme-footer'],
        filemtime($stylesheet_path)
    );

    $script_path = get_theme_file_path('assets/js/about.js');

    if (! file_exists($script_path)) {
        return;
    }

    wp_enqueue_script(
        'my-theme-about',
        get_theme_file_uri('assets/js/about.js'),
        ['swiper-js'],
        filemtime($script_path),
        true
    );
}
add_action('wp_enqueue_scripts', 'my_theme_enqueue_about_styles', 25);
