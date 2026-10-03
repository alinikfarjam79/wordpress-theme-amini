<?php
/** FSE-editable bulk-order call-to-action block. */

if (! defined('ABSPATH')) { exit; }

function my_theme_render_bulk_order($attributes = [], $content = '') {
    $title       = isset($attributes['title']) ? sanitize_text_field($attributes['title']) : 'خرید عمده رنگ و ابزارآلات';
    $description = isset($attributes['description']) ? sanitize_textarea_field($attributes['description']) : 'با بهترین قیمت، مستقیم از نمایندگی برندهای معروف';
    $button_text = isset($attributes['buttonText']) ? sanitize_text_field($attributes['buttonText']) : 'مشاهده پیش فاکتور';
    $button_url  = isset($attributes['buttonUrl']) ? esc_url($attributes['buttonUrl']) : '#';
    $button_height = isset($attributes['buttonHeight']) ? absint($attributes['buttonHeight']) : 44;
    $button_height = max(32, min(80, $button_height));
    $image_id    = isset($attributes['imageId']) ? absint($attributes['imageId']) : 0;
    $image       = isset($attributes['imageUrl']) ? esc_url_raw($attributes['imageUrl']) : 'https://aminirang.ir/wp-content/uploads/2026/06/images-1-1.png';
    $image_alt   = isset($attributes['imageAlt']) ? sanitize_text_field($attributes['imageAlt']) : 'خرید عمده رنگ';
    if ($image_id) {
        $attachment = wp_get_attachment_image_url($image_id, 'large');
        if ($attachment) { $image = $attachment; }
    }
    $wrapper = get_block_wrapper_attributes([
        'class' => 'wp-block-group alignfull bulk-pattern is-layout-constrained wp-block-group-is-layout-constrained',
        'style' => '--bulk-order-button-height:' . $button_height . 'px;',
    ]);
    ob_start(); ?>
    <div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
        <div class="wp-block-group bulk-inner has-secondary-background-color has-background is-content-justification-space-between is-nowrap is-layout-flex wp-block-group-is-layout-flex">
            <?php if ('' !== trim((string) $content)) : ?>
                <?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Serialized block content. ?>
            <?php else : ?>
            <div class="wp-block-group bulk-content is-layout-constrained wp-block-group-is-layout-constrained">
                <?php if ('' !== $title) : ?><h2 class="wp-block-heading has-text-align-center"><?php echo esc_html($title); ?></h2><?php endif; ?>
                <?php if ('' !== $description) : ?><p class="has-text-align-center wp-block-paragraph"><?php echo nl2br(esc_html($description)); ?></p><?php endif; ?>
                <?php if ('' !== $button_text) : ?><div class="wp-block-buttons is-content-justification-center is-layout-flex wp-block-buttons-is-layout-flex"><div class="wp-block-button"><a class="wp-block-button__link latest-articles-btn wp-element-button" href="<?php echo esc_url($button_url ?: '#'); ?>"><?php echo esc_html($button_text); ?></a></div></div><?php endif; ?>
            </div>
            <?php if ($image) : ?><figure class="wp-block-image"><img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($image_alt); ?>"></figure><?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php return trim(ob_get_clean());
}

function my_theme_replace_legacy_bulk_order($blocks, &$changed) {
    foreach ($blocks as &$block) {
        $class = isset($block['attrs']['className']) ? (string) $block['attrs']['className'] : '';
        if ('core/group' === $block['blockName'] && preg_match('/(^|\s)bulk-pattern(\s|$)/', $class)) {
            $block = ['blockName' => 'my-theme/bulk-order', 'attrs' => [], 'innerBlocks' => [], 'innerHTML' => '', 'innerContent' => []];
            $changed = true;
            continue;
        }
        if (! empty($block['innerBlocks'])) { $block['innerBlocks'] = my_theme_replace_legacy_bulk_order($block['innerBlocks'], $changed); }
    }
    return $blocks;
}

function my_theme_migrate_legacy_bulk_order() {
    if (! is_admin() || wp_doing_ajax()) { return; }
    $templates = get_posts(['post_type' => 'wp_template', 'post_status' => ['publish', 'draft'], 'posts_per_page' => -1, 'suppress_filters' => false]);
    foreach ($templates as $template) {
        $content = (string) $template->post_content;
        if (false === strpos($content, 'bulk-pattern')) { continue; }
        $changed = false;
        $blocks = my_theme_replace_legacy_bulk_order(parse_blocks($content), $changed);
        if (! $changed) { continue; }
        if ('' === (string) get_post_meta($template->ID, '_my_theme_bulk_order_backup', true)) { add_post_meta($template->ID, '_my_theme_bulk_order_backup', $content, true); }
        wp_update_post(['ID' => $template->ID, 'post_content' => serialize_blocks($blocks)]);
    }
}
add_action('admin_init', 'my_theme_migrate_legacy_bulk_order', 13);
