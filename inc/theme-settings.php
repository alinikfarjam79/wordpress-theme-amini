<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Theme setup
 */
function my_theme_setup() {
    add_theme_support('wp-block-styles');
    add_theme_support('editor-styles');
    add_theme_support('rtl');
    add_theme_support('title-tag');

    add_editor_style([
        'style.css',
        'assets/css/header.css',
        'assets/css/home.css',
        'assets/css/footer.css',
        'assets/css/editor.css',
        'assets/css/contact.css',
        'assets/css/contact-editor.css',
    ]);

    add_theme_support('custom-logo', [
        'flex-height' => true,
        'flex-width'  => true,
    ]);

    add_theme_support('woocommerce', [
        'thumbnail_image_width' => 300,
        'single_image_width'    => 600,
        'product_grid'          => [
            'default_rows' => 4,
            'min_columns'  => 2,
            'max_columns'  => 4,
        ],
    ]);
}
add_action('after_setup_theme', 'my_theme_setup');

/**
 * SVG upload support
 */
function my_theme_allow_svg_upload( $mimes ) {
    $mimes['svg'] = 'image/svg+xml';
    $mimes['svgz'] = 'image/svg+xml';

    return $mimes;
}
add_filter( 'upload_mimes', 'my_theme_allow_svg_upload' );

function my_theme_fix_svg_display( $response, $attachment, $meta ) {
    if ( $response['type'] === 'image' && $response['subtype'] === 'svg+xml' && method_exists( $attachment, 'get_source' ) ) {
        $response['sizes'] = array(
            'full' => array(
                'url' => $response['url'],
            )
        );
    }

    return $response;
}
add_filter( 'wp_prepare_attachment_for_js', 'my_theme_fix_svg_display', 10, 3 );

/**
 * Theme settings page
 */
function my_theme_add_settings_page() {

    add_menu_page(
        'Theme Settings',
        'Theme Settings',
        'manage_options',
        'theme-settings',
        'theme_settings_page',
        'dashicons-admin-customizer',
        60
    );

}
add_action('admin_menu', 'my_theme_add_settings_page');

function theme_logo_shortcode() {

    $logo = get_option('theme_logo');

    if ($logo) {
        return '<a class="theme-logo-link" href="' . esc_url(home_url('/')) . '" aria-label="' . esc_attr(get_bloginfo('name')) . '"><img class="theme-logo-image" src="' . esc_url($logo) . '" alt="' . esc_attr(get_bloginfo('name')) . '"></a>';
    }

    return '';
}
add_shortcode('theme_logo', 'theme_logo_shortcode');

function my_theme_register_settings() {

    register_setting('theme_settings_group', 'theme_logo');

}
add_action('admin_init', 'my_theme_register_settings');

function theme_settings_page() {
?>
<div class="wrap">
    <h1>Theme Settings</h1>

    <form method="post" action="options.php">
        <?php settings_fields('theme_settings_group'); ?>

        <table class="form-table">

            <tr>
                <th>Site Logo</th>
                <td>
                    <input type="text" name="theme_logo" value="<?php echo esc_attr(get_option('theme_logo')); ?>"
                        style="width:300px;" />

                    <p>Paste image URL or use Media Library link</p>
                </td>
            </tr>

        </table>

        <?php submit_button(); ?>
    </form>
</div>
<?php
}
