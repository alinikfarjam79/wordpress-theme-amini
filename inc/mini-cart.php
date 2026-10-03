<?php
/**
 * Theme mini-cart renderer and fragments.
 *
 * @package MyTheme
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Return the current WooCommerce cart item count safely.
 *
 * @return int
 */
function my_theme_get_mini_cart_count() {
    if (! function_exists('WC') || ! WC() || ! WC()->cart) {
        return 0;
    }

    return (int) WC()->cart->get_cart_contents_count();
}

/**
 * Render the compact theme mini-cart link.
 *
 * @return string
 */
function my_theme_render_mini_cart() {
    $count    = my_theme_get_mini_cart_count();
    $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');

    ob_start();
    ?>
<a class="theme-mini-cart" href="<?php echo esc_url($cart_url); ?>" aria-label="<?php echo esc_attr(sprintf(_n('%d item in cart', '%d items in cart', $count, 'my-theme'), $count)); ?>">
    <span class="theme-mini-cart__count"><?php echo esc_html(number_format_i18n($count)); ?></span>
    <span class="theme-mini-cart__icon" aria-hidden="true"></span>
</a>
<?php
    return trim(ob_get_clean());
}

/**
 * Refresh mini-cart counts after WooCommerce AJAX add-to-cart events.
 *
 * @param array<string,string> $fragments Existing fragments.
 * @return array<string,string>
 */
function my_theme_mini_cart_fragments($fragments) {
    $count_markup = '<span class="theme-mini-cart__count">' . esc_html(number_format_i18n(my_theme_get_mini_cart_count())) . '</span>';

    $fragments['span.theme-mini-cart__count']       = $count_markup;
    $fragments['span.wc-block-mini-cart__badge']    = $count_markup;
    $fragments['.theme-mini-cart__count']           = $count_markup;

    return $fragments;
}
add_filter('woocommerce_add_to_cart_fragments', 'my_theme_mini_cart_fragments');

/**
 * Replace WooCommerce's default mini-cart block in saved Header template parts
 * with the insertable theme mini-cart block.
 *
 * @return void
 */
function my_theme_migrate_header_mini_cart_block() {
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
        if (! $header_part instanceof WP_Post || false === strpos((string) $header_part->post_content, 'wp:woocommerce/mini-cart')) {
            continue;
        }

        $updated_content = preg_replace(
            '/<!--\s+wp:woocommerce\/mini-cart(?:\s+\{.*?\})?\s+\/-->/',
            '<!-- wp:my-theme/mini-cart /-->',
            (string) $header_part->post_content
        );

        if (! is_string($updated_content) || $updated_content === $header_part->post_content) {
            continue;
        }

        if ('' === (string) get_post_meta($header_part->ID, '_my_theme_header_minicart_backup', true)) {
            add_post_meta($header_part->ID, '_my_theme_header_minicart_backup', $header_part->post_content, true);
        }

        wp_update_post(
            [
                'ID'           => $header_part->ID,
                'post_content' => $updated_content,
            ]
        );
    }
}
add_action('admin_init', 'my_theme_migrate_header_mini_cart_block');

/**
 * Remove duplicate mini-cart blocks from saved Header template parts.
 *
 * The Header should contain one compact theme mini-cart only. This cleanup is
 * intentionally scoped to the `header` template part and keeps the first
 * theme mini-cart instance it finds.
 *
 * @return void
 */
function my_theme_deduplicate_header_mini_cart_blocks() {
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

    foreach ($header_parts as $header_part) {
        if (! $header_part instanceof WP_Post) {
            continue;
        }

        $content = (string) $header_part->post_content;

        if (false === strpos($content, 'wp:my-theme/mini-cart')) {
            continue;
        }

        // Remove any legacy WooCommerce mini-cart block once the theme block exists.
        $content = preg_replace(
            '/<!--\s+wp:woocommerce\/mini-cart(?:\s+\{.*?\})?\s+\/-->/',
            '',
            $content
        );

        // Keep the first theme mini-cart block and remove accidental duplicates.
        $seen = false;
        $content = preg_replace_callback(
            '/<!--\s+wp:my-theme\/mini-cart(?:\s+\{.*?\})?\s+\/-->/',
            static function ($matches) use (&$seen) {
                if (! $seen) {
                    $seen = true;
                    return '<!-- wp:my-theme/mini-cart /-->';
                }

                return '';
            },
            (string) $content
        );

        if (! is_string($content) || $content === $header_part->post_content) {
            continue;
        }

        if ('' === (string) get_post_meta($header_part->ID, '_my_theme_header_minicart_dedupe_backup', true)) {
            add_post_meta($header_part->ID, '_my_theme_header_minicart_dedupe_backup', $header_part->post_content, true);
        }

        wp_update_post(
            [
                'ID'           => $header_part->ID,
                'post_content' => $content,
            ]
        );
    }
}
add_action('admin_init', 'my_theme_deduplicate_header_mini_cart_blocks', 20);

