<?php
/** FSE-editable brand slider block and legacy migration. */

if (! defined('ABSPATH')) {
    exit;
}

function my_theme_brand_slider_registered_items() {
    if (! post_type_exists('brands')) {
        return [];
    }

    $query = new WP_Query([
        'post_type'              => 'brands',
        'posts_per_page'         => -1,
        'post_status'            => 'publish',
        'orderby'                => 'menu_order title',
        'order'                  => 'ASC',
        'no_found_rows'          => true,
        'update_post_meta_cache' => true,
        'update_post_term_cache' => false,
    ]);
    $items = [];
    foreach ($query->posts as $brand) {
        $image_id = get_post_thumbnail_id($brand->ID);
        $url      = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';
        $alt      = $image_id ? (string) get_post_meta($image_id, '_wp_attachment_image_alt', true) : '';
        $items[]  = [
            'imageId'  => (int) $image_id,
            'imageUrl' => $url ?: '',
            'alt'      => $alt ?: get_the_title($brand),
            'url'      => get_permalink($brand),
            'name'     => get_the_title($brand),
        ];
    }
    wp_reset_postdata();
    return $items;
}

function my_theme_render_brand_slider($attributes = []) {
    $title          = isset($attributes['title']) ? sanitize_text_field($attributes['title']) : 'برندهای معتبر موجود';
    $use_registered = ! isset($attributes['useRegisteredBrands']) || (bool) $attributes['useRegisteredBrands'];
    $items          = $use_registered ? my_theme_brand_slider_registered_items() : (isset($attributes['brands']) && is_array($attributes['brands']) ? $attributes['brands'] : []);
    $items          = array_values(array_filter($items, 'is_array'));
    $item_count     = count($items);
    $navigation     = ! isset($attributes['showNavigation']) || (bool) $attributes['showNavigation'];
    $loop           = ! empty($attributes['loop']);
    $autoplay       = ! empty($attributes['autoplay']);
    $delay          = isset($attributes['delay']) ? max(1000, min(10000, absint($attributes['delay']))) : 3000;
    $link_brands    = ! empty($attributes['linkBrands']);
    $desktop_gap    = isset($attributes['desktopGap']) ? max(0, min(120, absint($attributes['desktopGap']))) : 68;
    $tablet_gap     = isset($attributes['tabletGap']) ? max(0, min(120, absint($attributes['tabletGap']))) : 24;
    $mobile_gap     = isset($attributes['mobileGap']) ? max(0, min(120, absint($attributes['mobileGap']))) : 16;

    $wrapper_attributes = get_block_wrapper_attributes([
        'class' => 'brand-slider-section',
        'dir'   => 'rtl',
    ]);

    ob_start(); ?>
    <div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
        <?php if ('' !== $title) : ?><h2 class="wp-block-heading has-text-align-center brand-section-title has-xl-font-size"><?php echo esc_html($title); ?></h2><?php endif; ?>
        <div class="brand-slider-wrap<?php echo $item_count < 3 ? ' brand-slider-wrap--few' : ''; ?>" data-item-count="<?php echo esc_attr($item_count); ?>" data-loop="<?php echo $loop ? 'true' : 'false'; ?>" data-autoplay="<?php echo $autoplay ? 'true' : 'false'; ?>" data-delay="<?php echo esc_attr($delay); ?>" data-desktop-gap="<?php echo esc_attr($desktop_gap); ?>" data-tablet-gap="<?php echo esc_attr($tablet_gap); ?>" data-mobile-gap="<?php echo esc_attr($mobile_gap); ?>">
            <div class="swiper brandSwiper"><div class="swiper-wrapper">
                <?php if ($items) : foreach ($items as $item) :
                    $image_id  = isset($item['imageId']) ? absint($item['imageId']) : 0;
                    $image_url = isset($item['imageUrl']) ? esc_url_raw($item['imageUrl']) : '';
                    if ($image_id) {
                        $attachment_url = wp_get_attachment_image_url($image_id, 'medium');
                        if ($attachment_url) { $image_url = $attachment_url; }
                    }
                    $alt  = isset($item['alt']) ? sanitize_text_field($item['alt']) : '';
                    $url  = isset($item['url']) ? esc_url($item['url']) : '';
                    $name = isset($item['name']) ? sanitize_text_field($item['name']) : $alt;
                    ?>
                    <div class="swiper-slide brand-item">
                        <?php if ($link_brands && $url) : ?><a class="brand-item__link" href="<?php echo esc_url($url); ?>" aria-label="<?php echo esc_attr($name); ?>"><?php endif; ?>
                        <?php if ($image_url) : ?><img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($alt); ?>" loading="lazy"><?php else : ?><span class="brand-empty"><?php echo esc_html($name ?: 'بدون لوگو'); ?></span><?php endif; ?>
                        <?php if ($link_brands && $url) : ?></a><?php endif; ?>
                    </div>
                <?php endforeach; else : ?>
                    <div class="swiper-slide brand-empty-slide"><p class="brand-empty-message">هیچ برندی ثبت نشده است.</p></div>
                <?php endif; ?>
            </div></div>
            <?php if ($navigation && $item_count > 1) : ?><button type="button" class="swiper-button-next brand-next" aria-label="برندهای قبلی"></button><button type="button" class="swiper-button-prev brand-prev" aria-label="برندهای بعدی"></button><?php endif; ?>
        </div>
    </div>
    <?php return trim(ob_get_clean());
}

function my_theme_migrate_legacy_brand_slider() {
    if (! is_admin() || wp_doing_ajax()) { return; }
    $templates = get_posts(['post_type' => 'wp_template', 'post_status' => ['publish', 'draft'], 'posts_per_page' => -1, 'suppress_filters' => false]);
    foreach ($templates as $template) {
        $content = (string) $template->post_content;
        if (false === strpos($content, 'brand_slider_shortcode')) { continue; }
        $replacement = preg_replace('/<!-- wp:group[^>]*-->\s*<div class="wp-block-group">\s*<!-- wp:heading[^>]*brand-section-title.*?<!-- \/wp:group -->/s', '<!-- wp:my-theme/brand-slider /-->', $content, 1);
        if (! is_string($replacement) || $replacement === $content) { continue; }
        if ('' === (string) get_post_meta($template->ID, '_my_theme_brand_slider_backup', true)) {
            add_post_meta($template->ID, '_my_theme_brand_slider_backup', $content, true);
        }
        wp_update_post(['ID' => $template->ID, 'post_content' => $replacement]);
    }
}
add_action('admin_init', 'my_theme_migrate_legacy_brand_slider', 10);
