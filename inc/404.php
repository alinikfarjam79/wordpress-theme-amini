<?php
/**
 * Custom 404 page content.
 *
 * @package MyTheme
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Render the 404 page content.
 *
 * @return string
 */
function my_theme_render_error_404_content() {
    $illustration_url = get_theme_file_uri('assets/images/404-illustration.svg');
    $home_url         = home_url('/');

    ob_start();
    ?>
<main class="wp-block-group error-404-page" dir="rtl">
    <section class="error-404" aria-labelledby="error-404-title">
        <div class="error-404__hero">
            <div class="error-404__visual" aria-hidden="true">
                <img class="error-404__illustration" src="<?php echo esc_url($illustration_url); ?>" alt="" loading="eager" decoding="async">
            </div>

            <div class="error-404__overlay-content">
                <h1 id="error-404-title" class="error-404__title">
                    <?php echo esc_html__('صفحه‌ای که به دنبالش بودید پیدا نشد!', 'my-theme'); ?>
                </h1>

                <p class="error-404__description">
                    <?php echo esc_html__('برای پیدا کردن محصول مورد نظرتان از نوار جستجو کمک بگیرید یا به صفحه اصلی سایت بازگردید.', 'my-theme'); ?>
                </p>

                <div class="error-404__actions">
                    <div class="error-404__search">
                        <?php
                        if (function_exists('my_theme_render_combined_search_form')) {
                            echo my_theme_render_combined_search_form( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                [
                                    'id'          => 'error-404-search-input',
                                    'extra_class' => 'error-404__search-form',
                                    'placeholder' => __('جستجو در سایت', 'my-theme'),
                                    'value'       => '',
                                    'width'       => '226px',
                                ]
                            );
                        }
                        ?>
                    </div>

                    <a class="error-404__home-button" href="<?php echo esc_url($home_url); ?>">
                        <span><?php echo esc_html__('بازگشت به صفحه اصلی', 'my-theme'); ?></span>
                        <?php
                        if (function_exists('my_theme_search_results_view_all_arrow_svg')) {
                            echo my_theme_search_results_view_all_arrow_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        }
                        ?>
                    </a>
                </div>
            </div>
        </div>
    </section>
</main>
<?php
    return trim(ob_get_clean());
}

/**
 * Shortcode fallback for the 404 content.
 *
 * @return string
 */
function my_theme_error_404_shortcode() {
    return my_theme_render_error_404_content();
}
add_shortcode('theme_error_404', 'my_theme_error_404_shortcode');

/**
 * Register the dynamic block used by templates/404.html.
 *
 * @return void
 */
function my_theme_register_error_404_block() {
    if (! function_exists('register_block_type')) {
        return;
    }

    register_block_type(
        'my-theme/error-404-content',
        [
            'render_callback' => 'my_theme_error_404_shortcode',
            'api_version'     => 2,
        ]
    );
}
add_action('init', 'my_theme_register_error_404_block');
