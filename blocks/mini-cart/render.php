<?php
/**
 * Render the theme mini-cart block.
 *
 * @package MyTheme
 */

if (! defined('ABSPATH')) {
    exit;
}

if (function_exists('my_theme_render_mini_cart')) {
    echo my_theme_render_mini_cart(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
