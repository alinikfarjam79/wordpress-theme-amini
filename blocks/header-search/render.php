<?php
/**
 * Render the shared header search block.
 *
 * @package MyTheme
 */

if (! defined('ABSPATH')) {
    exit;
}

if (function_exists('my_theme_render_combined_search_form')) {
    echo my_theme_render_combined_search_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
