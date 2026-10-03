<?php
/** Editable Why Amini and Testimonials blocks. */

if (! defined('ABSPATH')) { exit; }

function my_theme_why_icon_svg() {
    return '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><rect width="32" height="32" rx="16" fill="white"></rect><ellipse cx="15.1892" cy="15.36" rx="5.18919" ry="5.18919" stroke="#009E00" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"></ellipse><path d="M18.7567 19.2246L22 22.4679" stroke="#009E00" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"></path></svg>';
}

function my_theme_default_why_items() {
    return ['۴۷ سال تجربه فروش', 'مشاوره تخصصی', 'تنوع محصولات', 'قیمت مناسب'];
}

function my_theme_render_why_amini($attributes = []) {
    $title    = isset($attributes['title']) ? sanitize_text_field($attributes['title']) : 'چرا رنگ امینی؟';
    $items    = isset($attributes['items']) && is_array($attributes['items']) ? $attributes['items'] : my_theme_default_why_items();
    $image_id = isset($attributes['imageId']) ? absint($attributes['imageId']) : 0;
    $image    = isset($attributes['imageUrl']) ? esc_url_raw($attributes['imageUrl']) : 'https://aminirang.ir/wp-content/uploads/2026/06/Photo.png';
    $alt      = isset($attributes['imageAlt']) ? sanitize_text_field($attributes['imageAlt']) : 'مشاوره و فروش رنگ ساختمانی';
    if ($image_id) {
        $attachment = wp_get_attachment_image_url($image_id, 'large');
        if ($attachment) { $image = $attachment; }
    }
    $wrapper = get_block_wrapper_attributes(['class' => 'wp-block-group why-box is-nowrap is-layout-flex wp-block-group-is-layout-flex']);
    ob_start(); ?>
    <div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
        <div class="wp-block-group why-text is-layout-constrained wp-block-group-is-layout-constrained">
            <?php if ('' !== $title) : ?><h2 class="wp-block-heading"><?php echo esc_html($title); ?></h2><?php endif; ?>
            <?php if ($items) : ?><ul class="why-list-items wp-block-list">
                <?php foreach ($items as $item) : $text = is_array($item) && isset($item['text']) ? $item['text'] : $item; if ('' === trim((string) $text)) { continue; } ?>
                    <li class="why-list"><?php echo my_theme_why_icon_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html(sanitize_text_field((string) $text)); ?></li>
                <?php endforeach; ?>
            </ul><?php endif; ?>
        </div>
        <?php if ($image) : ?><figure class="wp-block-image size-large why-image"><img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($alt); ?>"></figure><?php endif; ?>
    </div>
    <?php return trim(ob_get_clean());
}

function my_theme_default_testimonials() {
    return [
        ['imageId' => 0, 'imageUrl' => 'https://i.pravatar.cc/100?img=12', 'imageAlt' => '', 'name' => 'آقای محمدی', 'role' => 'نقاش', 'text' => 'قبل از خرید به من مشاوره دادند و خرید به اندازه و با قیمت خوبی داشتم و مجدد خرید خواهم کرد.'],
        ['imageId' => 0, 'imageUrl' => 'https://i.pravatar.cc/100?img=32', 'imageAlt' => '', 'name' => 'آقای رضایی', 'role' => 'مشتری', 'text' => 'قبل از خرید به من مشاوره دادند و خرید به اندازه و با قیمت خوبی داشتم و مجدد خرید خواهم کرد.'],
    ];
}

function my_theme_render_testimonials($attributes = []) {
    $title      = isset($attributes['title']) ? sanitize_text_field($attributes['title']) : 'نظرات مشتریان ما';
    $items      = isset($attributes['testimonials']) && is_array($attributes['testimonials']) ? $attributes['testimonials'] : my_theme_default_testimonials();
    $items      = array_values(array_filter($items, 'is_array'));
    $navigation = ! isset($attributes['showNavigation']) || (bool) $attributes['showNavigation'];
    $loop       = ! empty($attributes['loop']);
    $autoplay   = ! empty($attributes['autoplay']);
    $delay      = isset($attributes['delay']) ? max(1000, min(10000, absint($attributes['delay']))) : 4000;
    $wrapper    = get_block_wrapper_attributes(['class' => 'wp-block-group testimonials-section is-layout-constrained wp-block-group-is-layout-constrained']);
    ob_start(); ?>
    <div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
        <?php if ('' !== $title) : ?><h2 class="wp-block-heading has-text-align-center section-title"><?php echo esc_html($title); ?></h2><?php endif; ?>
        <?php if ($items) : ?>
        <div class="wp-block-group testimonial-slider-wrap is-layout-constrained wp-block-group-is-layout-constrained">
            <div class="wp-block-group swiper testimonialSwiper is-layout-constrained wp-block-group-is-layout-constrained" data-loop="<?php echo $loop ? 'true' : 'false'; ?>" data-autoplay="<?php echo $autoplay ? 'true' : 'false'; ?>" data-delay="<?php echo esc_attr($delay); ?>">
                <div class="wp-block-group swiper-wrapper is-layout-constrained wp-block-group-is-layout-constrained">
                    <?php foreach ($items as $item) :
                        $image_id = isset($item['imageId']) ? absint($item['imageId']) : 0;
                        $image    = isset($item['imageUrl']) ? esc_url_raw($item['imageUrl']) : '';
                        if ($image_id) { $attachment = wp_get_attachment_image_url($image_id, 'thumbnail'); if ($attachment) { $image = $attachment; } }
                        $alt  = isset($item['imageAlt']) ? sanitize_text_field($item['imageAlt']) : '';
                        $name = isset($item['name']) ? sanitize_text_field($item['name']) : '';
                        $role = isset($item['role']) ? sanitize_text_field($item['role']) : '';
                        $text = isset($item['text']) ? sanitize_textarea_field($item['text']) : '';
                        ?>
                        <div class="wp-block-group swiper-slide is-layout-constrained wp-block-group-is-layout-constrained"><div class="wp-block-group testimonial-card is-nowrap is-layout-flex wp-block-group-is-layout-flex">
                            <div class="wp-block-group testimonial-user is-layout-constrained wp-block-group-is-layout-constrained">
                                <?php if ($image) : ?><figure class="wp-block-image size-thumbnail is-resized"><img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($alt); ?>" style="width:94px;height:94px"></figure><?php endif; ?>
                                <div class="testimonial-user-title"><?php if ($name) : ?><p class="wp-block-paragraph testimonial-user-name"><strong><?php echo esc_html($name); ?></strong></p><?php endif; ?><?php if ($role) : ?><p class="wp-block-paragraph testimonial-user-role"><?php echo esc_html($role); ?></p><?php endif; ?></div>
                            </div>
                            <p class="testimonial-text wp-block-paragraph"><?php echo esc_html($text); ?></p>
                        </div></div>
                    <?php endforeach; ?>
                </div>
                <?php if ($navigation && count($items) > 1) : ?><div class="swiper-button-prev testimonial-prev" role="button" tabindex="0" aria-label="نظر قبلی"></div><div class="swiper-button-next testimonial-next" role="button" tabindex="0" aria-label="نظر بعدی"></div><?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php return trim(ob_get_clean());
}

function my_theme_replace_legacy_trust_blocks($blocks, &$changed) {
    foreach ($blocks as &$block) {
        $class = isset($block['attrs']['className']) ? (string) $block['attrs']['className'] : '';
        if ('core/group' === $block['blockName'] && preg_match('/(^|\s)main-wrapper(\s|$)/', $class)) {
            $serialized = serialize_blocks($block['innerBlocks']);
            if (false !== strpos($serialized, 'why-box') || false !== strpos($serialized, 'testimonialSwiper')) {
                $block['innerBlocks'] = [
                    ['blockName' => 'my-theme/why-amini', 'attrs' => [], 'innerBlocks' => [], 'innerHTML' => '', 'innerContent' => []],
                    ['blockName' => 'my-theme/testimonials', 'attrs' => [], 'innerBlocks' => [], 'innerHTML' => '', 'innerContent' => []],
                ];
                $block['innerHTML'] = '';
                $block['innerContent'] = [null, null];
                $changed = true;
            }
            continue;
        }
        if (! empty($block['innerBlocks'])) { $block['innerBlocks'] = my_theme_replace_legacy_trust_blocks($block['innerBlocks'], $changed); }
    }
    return $blocks;
}

function my_theme_migrate_legacy_trust_blocks() {
    if (! is_admin() || wp_doing_ajax()) { return; }
    $templates = get_posts(['post_type' => 'wp_template', 'post_status' => ['publish', 'draft'], 'posts_per_page' => -1, 'suppress_filters' => false]);
    foreach ($templates as $template) {
        $content = (string) $template->post_content;
        if (false === strpos($content, 'main-wrapper') || (false === strpos($content, 'why-box') && false === strpos($content, 'testimonialSwiper'))) { continue; }
        $changed = false;
        $blocks = my_theme_replace_legacy_trust_blocks(parse_blocks($content), $changed);
        if (! $changed) { continue; }
        if ('' === (string) get_post_meta($template->ID, '_my_theme_trust_blocks_backup', true)) { add_post_meta($template->ID, '_my_theme_trust_blocks_backup', $content, true); }
        wp_update_post(['ID' => $template->ID, 'post_content' => serialize_blocks($blocks)]);
    }
}
add_action('admin_init', 'my_theme_migrate_legacy_trust_blocks', 11);
