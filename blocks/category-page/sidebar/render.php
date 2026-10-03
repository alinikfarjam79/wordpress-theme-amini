<?php
/**
 * Product category sidebar block render callback.
 *
 * @package MyTheme
 */

if (! defined('ABSPATH')) {
    exit;
}

$wrapper_attributes = get_block_wrapper_attributes(
    [
        'class' => 'product-category-layout__sidebar',
    ]
);
?>

<aside <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <div class="product-category-filter-sticky">
        <?php echo $content ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>
</aside>
