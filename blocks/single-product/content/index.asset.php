<?php
/**
 * Asset metadata for the single-product content block editor script.
 *
 * @package MyTheme
 */

return [
    'dependencies' => [
        'wp-block-editor',
        'wp-blocks',
        'wp-components',
        'wp-element',
        'wp-i18n',
        'wp-server-side-render',
        'wp-data',
        'my-theme-single-product-block-controls',
    ],
    'version' => filemtime(__DIR__ . '/index.js'),
];
