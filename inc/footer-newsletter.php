<?php

if (! defined('ABSPATH')) {
    exit;
}

function my_theme_newsletter_register_post_type() {
    register_post_type('newsletter_signup', [
        'labels' => [
            'name'          => 'درخواست‌های خبرنامه',
            'singular_name' => 'درخواست خبرنامه',
            'menu_name'     => 'درخواست‌های خبرنامه',
            'all_items'     => 'همه درخواست‌ها',
            'search_items'  => 'جستجوی درخواست‌ها',
            'not_found'     => 'درخواستی ثبت نشده است.',
        ],
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_rest'        => false,
        'capabilities'        => [
            'edit_post'              => 'manage_options',
            'read_post'              => 'manage_options',
            'delete_post'            => 'manage_options',
            'edit_posts'             => 'manage_options',
            'edit_others_posts'      => 'manage_options',
            'publish_posts'          => 'manage_options',
            'read_private_posts'     => 'manage_options',
            'delete_posts'           => 'manage_options',
            'delete_private_posts'   => 'manage_options',
            'delete_published_posts' => 'manage_options',
            'delete_others_posts'    => 'manage_options',
            'edit_private_posts'     => 'manage_options',
            'edit_published_posts'   => 'manage_options',
            'create_posts'           => 'do_not_allow',
        ],
        'map_meta_cap'        => false,
        'supports'            => ['title'],
        'menu_icon'           => 'dashicons-email-alt2',
        'menu_position'       => 57,
        'exclude_from_search' => true,
    ]);
}
add_action('init', 'my_theme_newsletter_register_post_type');

function my_theme_newsletter_normalize_digits($value) {
    return strtr((string) $value, [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ]);
}

function my_theme_newsletter_normalize_phone($value) {
    $value = my_theme_newsletter_normalize_digits($value);
    $value = preg_replace('/[^0-9+]/', '', $value);

    if (0 === strpos($value, '0098')) {
        $value = '0' . substr($value, 4);
    } elseif (0 === strpos($value, '+98')) {
        $value = '0' . substr($value, 3);
    } elseif (0 === strpos($value, '98') && 12 === strlen($value)) {
        $value = '0' . substr($value, 2);
    }

    return $value;
}

function my_theme_newsletter_submit() {
    check_ajax_referer('my_theme_newsletter_submit', 'nonce');

    $name  = isset($_POST['full_name']) ? sanitize_text_field(wp_unslash($_POST['full_name'])) : '';
    $phone = isset($_POST['phone']) ? my_theme_newsletter_normalize_phone(wp_unslash($_POST['phone'])) : '';

    $name_length = function_exists('mb_strlen') ? mb_strlen($name) : strlen($name);
    if ($name_length < 3 || $name_length > 100) {
        wp_send_json_error(['message' => 'لطفاً نام و نام خانوادگی معتبر وارد کنید.'], 422);
    }

    if (! preg_match('/^0[0-9]{9,10}$/', $phone)) {
        wp_send_json_error(['message' => 'لطفاً شماره تماس معتبر وارد کنید.'], 422);
    }

    $ip_hash = hash('sha256', (isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '') . wp_salt());
    $rate_key = 'my_theme_newsletter_rate_' . substr($ip_hash, 0, 24);
    if (get_transient($rate_key)) {
        wp_send_json_error(['message' => 'لطفاً کمی صبر کنید و دوباره تلاش کنید.'], 429);
    }
    set_transient($rate_key, 1, 10);

    $existing = get_posts([
        'post_type'      => 'newsletter_signup',
        'post_status'    => ['publish', 'trash'],
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_key'       => '_newsletter_phone',
        'meta_value'     => $phone,
        'no_found_rows'  => true,
    ]);

    if ($existing) {
        wp_send_json_error(['message' => 'این شماره تماس قبلاً ثبت شده است.'], 409);
    }

    $post_id = wp_insert_post([
        'post_type'   => 'newsletter_signup',
        'post_status' => 'publish',
        'post_title'  => $name,
    ], true);

    if (is_wp_error($post_id)) {
        wp_send_json_error(['message' => 'ثبت اطلاعات انجام نشد. دوباره تلاش کنید.'], 500);
    }

    update_post_meta($post_id, '_newsletter_phone', $phone);
    update_post_meta($post_id, '_newsletter_ip_hash', $ip_hash);

    wp_send_json_success(['message' => 'اطلاعات شما با موفقیت ثبت شد.']);
}
add_action('wp_ajax_my_theme_newsletter_submit', 'my_theme_newsletter_submit');
add_action('wp_ajax_nopriv_my_theme_newsletter_submit', 'my_theme_newsletter_submit');

function my_theme_newsletter_columns($columns) {
    return [
        'cb'               => $columns['cb'],
        'title'            => 'نام و نام خانوادگی',
        'newsletter_phone' => 'شماره تماس',
        'date'             => 'تاریخ ثبت',
    ];
}
add_filter('manage_newsletter_signup_posts_columns', 'my_theme_newsletter_columns');

function my_theme_newsletter_column_content($column, $post_id) {
    if ('newsletter_phone' === $column) {
        echo '<span dir="ltr">' . esc_html(get_post_meta($post_id, '_newsletter_phone', true)) . '</span>';
    }
}
add_action('manage_newsletter_signup_posts_custom_column', 'my_theme_newsletter_column_content', 10, 2);

function my_theme_newsletter_row_actions($actions) {
    unset($actions['edit'], $actions['inline hide-if-no-js'], $actions['view']);
    return $actions;
}
add_filter('post_row_actions', function ($actions, $post) {
    return 'newsletter_signup' === $post->post_type ? my_theme_newsletter_row_actions($actions) : $actions;
}, 10, 2);

function my_theme_newsletter_export_button() {
    $screen = get_current_screen();
    if (! $screen || 'edit-newsletter_signup' !== $screen->id || ! current_user_can('manage_options')) {
        return;
    }

    $url = wp_nonce_url(
        admin_url('admin-post.php?action=my_theme_newsletter_export'),
        'my_theme_newsletter_export'
    );
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var heading = document.querySelector('.wrap .wp-heading-inline');
            if (!heading) return;
            var link = document.createElement('a');
            link.href = <?php echo wp_json_encode($url); ?>;
            link.className = 'page-title-action';
            link.textContent = 'خروجی CSV';
            heading.insertAdjacentElement('afterend', link);
        });
    </script>
    <?php
}
add_action('admin_footer-edit.php', 'my_theme_newsletter_export_button');

function my_theme_newsletter_export() {
    if (! current_user_can('manage_options')) {
        wp_die('شما اجازه انجام این عملیات را ندارید.');
    }
    check_admin_referer('my_theme_newsletter_export');

    $items = get_posts([
        'post_type'      => 'newsletter_signup',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

    nocache_headers();
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename=newsletter-signups-' . gmdate('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, ['نام و نام خانوادگی', 'شماره تماس', 'تاریخ ثبت']);

    foreach ($items as $item) {
        fputcsv($output, [
            $item->post_title,
            get_post_meta($item->ID, '_newsletter_phone', true),
            get_date_from_gmt($item->post_date_gmt, 'Y-m-d H:i:s'),
        ]);
    }

    fclose($output);
    exit;
}
add_action('admin_post_my_theme_newsletter_export', 'my_theme_newsletter_export');
