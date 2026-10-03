<?php
/** Hero slider block renderer and legacy-template migration. */
if (! defined('ABSPATH')) { exit; }

function my_theme_default_hero_slides() {
    $image = 'https://aminirang.ir/wp-content/uploads/2026/06/55990528c1973b6799f0dcc9e8cce141e2830ebc.webp';
    return [
        ['imageId' => 0, 'imageUrl' => $image, 'imageAlt' => '', 'title' => 'رنگ ساختمان در ده رنگ متنوع موجود شد', 'buttonText' => 'مشاهده رنگ‌ها', 'buttonUrl' => '#', 'contentPosition' => 'right', 'mobileFocalPoint' => ['x' => 0.5, 'y' => 0.5]],
        ['imageId' => 0, 'imageUrl' => $image, 'imageAlt' => '', 'title' => 'ابزار و رنگ مورد نیازتان را سریع‌تر پیدا کنید', 'buttonText' => 'مشاهده محصولات', 'buttonUrl' => '#', 'contentPosition' => 'right', 'mobileFocalPoint' => ['x' => 0.5, 'y' => 0.5]],
    ];
}

function my_theme_render_hero_slider($attributes = []) {
    $slides = isset($attributes['slides']) && is_array($attributes['slides']) ? $attributes['slides'] : my_theme_default_hero_slides();
    if (empty($slides)) { return ''; }
    $autoplay = ! isset($attributes['autoplay']) || (bool) $attributes['autoplay'];
    $delay = isset($attributes['delay']) ? max(1000, min(10000, absint($attributes['delay']))) : 3000;
    $loop = ! isset($attributes['loop']) || (bool) $attributes['loop'];
    $navigation = ! isset($attributes['showNavigation']) || (bool) $attributes['showNavigation'];
    $pagination = ! isset($attributes['showPagination']) || (bool) $attributes['showPagination'];

    ob_start(); ?>
<div class="wp-block-my-theme-hero-slider hero-slider alignfull">
    <div class="swiper heroSwiper" data-autoplay="<?php echo $autoplay ? 'true' : 'false'; ?>" data-delay="<?php echo esc_attr($delay); ?>" data-loop="<?php echo $loop ? 'true' : 'false'; ?>">
        <div class="swiper-wrapper">
            <?php foreach ($slides as $slide) :
                $image_id = isset($slide['imageId']) ? absint($slide['imageId']) : 0;
                $image_url = isset($slide['imageUrl']) ? esc_url_raw($slide['imageUrl']) : '';
                if ($image_id) {
                    $attachment_url = wp_get_attachment_image_url($image_id, 'full');
                    if ($attachment_url) { $image_url = $attachment_url; }
                }
                $title = isset($slide['title']) ? sanitize_text_field($slide['title']) : '';
                $button_text = isset($slide['buttonText']) ? sanitize_text_field($slide['buttonText']) : '';
                $button_url = isset($slide['buttonUrl']) ? esc_url($slide['buttonUrl']) : '';
                $content_position = isset($slide['contentPosition']) && 'left' === $slide['contentPosition'] ? 'left' : 'right';
                $mobile_focal_point = isset($slide['mobileFocalPoint']) && is_array($slide['mobileFocalPoint']) ? $slide['mobileFocalPoint'] : [];
                $mobile_focal_x = isset($mobile_focal_point['x']) && is_numeric($mobile_focal_point['x']) ? (float) $mobile_focal_point['x'] : 0.5;
                $mobile_focal_y = isset($mobile_focal_point['y']) && is_numeric($mobile_focal_point['y']) ? (float) $mobile_focal_point['y'] : 0.5;
                $mobile_focal_x = max(0, min(1, $mobile_focal_x));
                $mobile_focal_y = max(0, min(1, $mobile_focal_y));
                $slide_style = sprintf(
                    '--hero-mobile-position-x:%1$s%%;--hero-mobile-position-y:%2$s%%;',
                    round($mobile_focal_x * 100, 2),
                    round($mobile_focal_y * 100, 2)
                );
                if ($image_url) {
                    $slide_style = "--hero-slide-image:url('" . $image_url . "');" . $slide_style;
                }
                ?>
                <div class="swiper-slide hero-slide--content-<?php echo esc_attr($content_position); ?>" style="<?php echo esc_attr($slide_style); ?>">
                    <div class="hero-slide-card">
                        <?php if ('' !== $title) : ?><h1><?php echo esc_html($title); ?></h1><?php endif; ?>
                        <?php if ('' !== $button_text) : ?>
                            <div class="wp-block-button hero-slide-button"><a class="wp-block-button__link wp-element-button" href="<?php echo $button_url ?: '#'; ?>"><?php echo esc_html($button_text); ?></a></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if ($navigation) : ?><div class="swiper-button-next"></div><div class="swiper-button-prev"></div><?php endif; ?>
        <?php if ($pagination) : ?><div class="swiper-pagination"></div><?php endif; ?>
    </div>
</div>
<?php return trim(ob_get_clean());
}

function my_theme_replace_legacy_hero_slider_blocks($blocks, &$changed) {
    foreach ($blocks as &$block) {
        $class_name = isset($block['attrs']['className']) ? (string) $block['attrs']['className'] : '';
        if ('core/group' === $block['blockName'] && preg_match('/(^|\s)hero-slider(\s|$)/', $class_name)) {
            $block = ['blockName' => 'my-theme/hero-slider', 'attrs' => [], 'innerBlocks' => [], 'innerHTML' => '', 'innerContent' => []];
            $changed = true;
            continue;
        }
        if (! empty($block['innerBlocks'])) {
            $block['innerBlocks'] = my_theme_replace_legacy_hero_slider_blocks($block['innerBlocks'], $changed);
        }
    }
    return $blocks;
}

function my_theme_migrate_legacy_hero_slider() {
    if (! is_admin() || wp_doing_ajax()) { return; }
    $templates = get_posts(['post_type' => ['wp_template', 'wp_template_part'], 'post_status' => ['publish', 'draft'], 'posts_per_page' => -1, 'suppress_filters' => false]);
    foreach ($templates as $template) {
        if (! $template instanceof WP_Post || false === strpos((string) $template->post_content, 'hero-slider')) { continue; }
        $changed = false;
        $blocks = my_theme_replace_legacy_hero_slider_blocks(parse_blocks($template->post_content), $changed);
        if (! $changed) { continue; }
        if ('' === (string) get_post_meta($template->ID, '_my_theme_hero_slider_backup', true)) {
            add_post_meta($template->ID, '_my_theme_hero_slider_backup', $template->post_content, true);
        }
        wp_update_post(['ID' => $template->ID, 'post_content' => serialize_blocks($blocks)]);
    }
}
add_action('admin_init', 'my_theme_migrate_legacy_hero_slider', 8);
