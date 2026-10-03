<?php
/**
 * Contact page installation.
 *
 * @package MyTheme
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Create the public Contact page and connect it to the FSE template.
 *
 * The routine is idempotent: reactivating or updating the theme will not
 * create duplicate pages.
 *
 * @return int Contact page ID, or zero when creation failed.
 */
function my_theme_ensure_contact_page() {
    $contact_page = get_page_by_path('contact', OBJECT, 'page');

    if ($contact_page instanceof WP_Post) {
        update_post_meta($contact_page->ID, '_wp_page_template', 'page-contact');
        update_option('my_theme_contact_page_id', (int) $contact_page->ID, false);

        return (int) $contact_page->ID;
    }

    $contact_page_id = wp_insert_post(
        [
            'post_title'   => __('تماس با ما', 'my-theme'),
            'post_name'    => 'contact',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '',
            'meta_input'   => [
                '_wp_page_template' => 'page-contact',
            ],
        ],
        true
    );

    if (is_wp_error($contact_page_id)) {
        return 0;
    }

    update_option('my_theme_contact_page_id', (int) $contact_page_id, false);
    flush_rewrite_rules(false);

    return (int) $contact_page_id;
}

/**
 * Ensure the page exists immediately after activating the theme.
 */
function my_theme_create_contact_page_on_activation() {
    my_theme_ensure_contact_page();
}
add_action('after_switch_theme', 'my_theme_create_contact_page_on_activation');

/**
 * Cover in-place theme updates where after_switch_theme is not fired.
 */
function my_theme_maybe_create_contact_page_after_update() {
    $stored_page_id = (int) get_option('my_theme_contact_page_id', 0);

    if ($stored_page_id > 0 && 'page' === get_post_type($stored_page_id)) {
        return;
    }

    my_theme_ensure_contact_page();
}
add_action('admin_init', 'my_theme_maybe_create_contact_page_after_update');
