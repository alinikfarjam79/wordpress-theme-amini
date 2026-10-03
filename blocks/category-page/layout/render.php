<?php
/**
 * Product category layout block render callback.
 *
 * @package MyTheme
 */

if (! defined('ABSPATH')) {
    exit;
}

$wrapper_attributes = get_block_wrapper_attributes(
    [
        'class' => 'product-category-layout',
    ]
);
?>

<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <?php echo $content ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</section>
