<?php

add_action('after_setup_theme', function () {
    add_theme_support('wp-block-styles');
    add_theme_support('editor-styles');
    add_theme_support('title-tag');
});

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
add_action('after_setup_theme', function () {

    add_theme_support('custom-logo', [
        'flex-height' => true,
        'flex-width'  => true,
    ]);

});