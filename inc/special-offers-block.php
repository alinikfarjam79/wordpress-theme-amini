<?php
/** FSE-editable green special-offers slider. */

if (! defined('ABSPATH')) { exit; }

function my_theme_special_offers_query($attributes = []) {
    $limit = isset($attributes['productsCount']) ? max(1, min(24, absint($attributes['productsCount']))) : 10;
    $ids   = isset($attributes['productIds']) ? array_values(array_unique(array_filter(array_map('absint', (array) $attributes['productIds'])))) : [];
    $args  = [
        'post_type'           => 'product',
        'posts_per_page'      => $ids ? count($ids) : $limit,
        'post_status'         => 'publish',
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    ];
    if ($ids) {
        $args['post__in'] = $ids;
        $args['orderby']  = 'post__in';
    } elseif (! empty($attributes['onlyOnSale']) && function_exists('wc_get_product_ids_on_sale')) {
        $sale_ids = array_values(array_filter(array_map('absint', wc_get_product_ids_on_sale())));
        $args['post__in'] = $sale_ids ?: [0];
    }
    return new WP_Query($args);
}

function my_theme_special_offers_arrow_svg() {
    return '<svg width="8" height="12" viewBox="0 0 8 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M3.34799 4.9036C2.92005 5.29944 2.92005 5.97595 3.34799 6.37179L6.73372 9.5036C7.16167 9.89944 7.16167 10.5759 6.73372 10.9718L6.69287 11.0096C6.30959 11.3641 5.71807 11.3641 5.33479 11.0096L0.320966 6.3718C-0.106978 5.97595 -0.106979 5.29944 0.320965 4.9036L5.33479 0.26581C5.71807 -0.0887244 6.30959 -0.0887254 6.69287 0.265809L6.73372 0.303595C7.16167 0.699443 7.16167 1.37595 6.73372 1.77179L3.34799 4.9036Z" fill="#009E00"></path></svg>';
}

function my_theme_render_special_offers($attributes = []) {
    $title       = isset($attributes['title']) ? sanitize_textarea_field($attributes['title']) : "تخفیف‌های\nویژه";
    $button_lead = isset($attributes['buttonLead']) ? sanitize_text_field($attributes['buttonLead']) : 'مشاهده';
    $button_text = isset($attributes['buttonText']) ? sanitize_text_field($attributes['buttonText']) : 'همه';
    $button_url  = isset($attributes['buttonUrl']) ? esc_url($attributes['buttonUrl']) : '';
    $navigation  = ! isset($attributes['showNavigation']) || (bool) $attributes['showNavigation'];
    $slides      = isset($attributes['slidesPerView']) ? max(0, min(6, absint($attributes['slidesPerView']))) : 0;
    $gap         = isset($attributes['spaceBetween']) ? max(0, min(80, absint($attributes['spaceBetween']))) : 15;
    $item_styles = [];
    $color_attributes = [
        'itemBackgroundColor' => '--special-item-background',
        'itemBorderColor'     => '--special-item-border-color',
        'itemTitleColor'      => '--special-item-title-color',
        'itemPriceColor'      => '--special-item-price-color',
        'itemButtonColor'     => '--special-item-button-color',
        'specialBadgeBackgroundColor'  => '--special-badge-background',
        'specialBadgeTextColor'        => '--special-badge-text-color',
        'discountBadgeBackgroundColor' => '--special-discount-background',
        'discountBadgeTextColor'       => '--special-discount-text-color',
    ];
    foreach ($color_attributes as $attribute_name => $variable_name) {
        if (! empty($attributes[$attribute_name])) {
            $color = sanitize_hex_color($attributes[$attribute_name]);
            if ($color) { $item_styles[] = $variable_name . ':' . $color; }
        }
    }
    $number_attributes = [
        'itemBorderWidth'  => ['--special-item-border-width', 0, 10, 0],
        'itemBorderRadius' => ['--special-item-radius', 0, 60, 15],
        'itemPadding'      => ['--special-item-padding', 0, 40, 16],
        'itemHeight'       => ['--special-item-height', 240, 440, 300],
        'itemImageHeight'  => ['--special-item-image-height', 70, 220, 130],
        'itemTitleSize'    => ['--special-item-title-size', 10, 28, 14],
        'itemPriceSize'    => ['--special-item-price-size', 12, 36, 20],
        'specialBadgeFontSize'  => ['--special-badge-font-size', 10, 28, 14],
        'specialBadgeRadius'    => ['--special-badge-radius', 0, 999, 999],
        'discountBadgeFontSize' => ['--special-discount-font-size', 10, 28, 16],
        'discountBadgeRadius'   => ['--special-discount-radius', 0, 999, 999],
    ];
    foreach ($number_attributes as $attribute_name => $setting) {
        $value = isset($attributes[$attribute_name]) ? absint($attributes[$attribute_name]) : $setting[3];
        $item_styles[] = $setting[0] . ':' . max($setting[1], min($setting[2], $value)) . 'px';
    }
    if ('' === $button_url) { $button_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/'); }
    $query   = my_theme_special_offers_query($attributes);
    $wrapper_args = ['class' => 'wp-block-group alignfull green-section is-layout-constrained wp-block-group-is-layout-constrained'];
    if ($item_styles) { $wrapper_args['style'] = implode(';', $item_styles); }
    $wrapper = get_block_wrapper_attributes($wrapper_args);
    ob_start(); ?>
    <div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
        <div class="wp-block-group green-wrapper is-nowrap is-vertical-align-center is-layout-flex wp-block-group-is-layout-flex">
            <div class="wp-block-group green-right is-layout-constrained wp-block-group-is-layout-constrained">
                <?php if ('' !== $title) : ?><h2 class="wp-block-heading"><?php echo nl2br(esc_html($title)); ?></h2><?php endif; ?>
                <?php if ('' !== $button_text) : ?><div class="wp-block-buttons green-offers-button is-content-justification-center is-layout-flex wp-block-buttons-is-layout-flex"><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url($button_url); ?>"><?php if ('' !== $button_lead) : ?><span class="green-offers-extra"><?php echo esc_html($button_lead); ?> </span><?php endif; ?><?php echo esc_html($button_text); ?> <?php echo my_theme_special_offers_arrow_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a></div></div><?php endif; ?>
            </div>
            <div class="swiper greenProductSwiper" dir="rtl"<?php echo $slides ? ' data-slides-per-view="' . esc_attr($slides) . '"' : ''; ?> data-space-between="<?php echo esc_attr($gap); ?>"><div class="swiper-wrapper">
                <?php if ($query->have_posts()) : while ($query->have_posts()) : $query->the_post(); $product = function_exists('wc_get_product') ? wc_get_product(get_the_ID()) : null; if (! $product || ! function_exists('my_theme_render_green_product_card')) { continue; }
                    echo my_theme_render_green_product_card($product, [
                        'show_promo_header'      => false,
                        'show_sale_image_badge'  => ! isset($attributes['showSpecialBadge']) || (bool) $attributes['showSpecialBadge'],
                        'show_sale_footer_badge' => ! isset($attributes['showDiscountBadge']) || (bool) $attributes['showDiscountBadge'],
                        'sale_label_text'        => isset($attributes['specialBadgeText']) ? sanitize_text_field($attributes['specialBadgeText']) : __('تخفیف ویژه', 'my-theme'),
                        'show_stock_status'      => true,
                    ]); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                endwhile; else : ?><div class="swiper-slide product-card green-product-card"><p class="green-empty-message">هیچ محصولی ثبت نشده است.</p></div><?php endif; wp_reset_postdata(); ?>
            </div></div>
            <?php if ($navigation && $query->post_count > 1) : ?><div class="swiper-button-prev green-prev" role="button" tabindex="0" aria-label="محصول قبلی"></div><div class="swiper-button-next green-next" role="button" tabindex="0" aria-label="محصول بعدی"></div><?php endif; ?>
        </div>
    </div>
    <?php return trim(ob_get_clean());
}

function my_theme_replace_legacy_special_offers($blocks, &$changed) {
    foreach ($blocks as &$block) {
        $class = isset($block['attrs']['className']) ? (string) $block['attrs']['className'] : '';
        if ('core/group' === $block['blockName'] && preg_match('/(^|\s)green-section(\s|$)/', $class)) {
            $block = ['blockName' => 'my-theme/special-offers', 'attrs' => [], 'innerBlocks' => [], 'innerHTML' => '', 'innerContent' => []];
            $changed = true;
            continue;
        }
        if (! empty($block['innerBlocks'])) { $block['innerBlocks'] = my_theme_replace_legacy_special_offers($block['innerBlocks'], $changed); }
    }
    return $blocks;
}

function my_theme_migrate_legacy_special_offers() {
    if (! is_admin() || wp_doing_ajax()) { return; }
    $templates = get_posts(['post_type' => 'wp_template', 'post_status' => ['publish', 'draft'], 'posts_per_page' => -1, 'suppress_filters' => false]);
    foreach ($templates as $template) {
        $content = (string) $template->post_content;
        if (false === strpos($content, 'green-section')) { continue; }
        $changed = false;
        $blocks = my_theme_replace_legacy_special_offers(parse_blocks($content), $changed);
        if (! $changed) { continue; }
        if ('' === (string) get_post_meta($template->ID, '_my_theme_special_offers_backup', true)) { add_post_meta($template->ID, '_my_theme_special_offers_backup', $content, true); }
        wp_update_post(['ID' => $template->ID, 'post_content' => serialize_blocks($blocks)]);
    }
}
add_action('admin_init', 'my_theme_migrate_legacy_special_offers', 12);
