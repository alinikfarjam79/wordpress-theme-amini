<?php
/** FSE-editable latest articles section. */
if (! defined('ABSPATH')) { exit; }

function my_theme_latest_articles_query($attributes = []) {
    $ids = isset($attributes['postIds']) ? array_values(array_filter(array_map('absint', (array) $attributes['postIds']))) : [];
    $allowed_orderby = ['date', 'title', 'rand'];
    $orderby = isset($attributes['orderBy']) && in_array($attributes['orderBy'], $allowed_orderby, true) ? $attributes['orderBy'] : 'date';
    $args = [
        'post_type' => 'post', 'post_status' => 'publish', 'ignore_sticky_posts' => true,
        'posts_per_page' => $ids ? count($ids) : max(1, min(12, absint($attributes['postsCount'] ?? 3))),
        'orderby' => $ids ? 'post__in' : $orderby,
        'order' => ('ASC' === ($attributes['order'] ?? 'DESC')) ? 'ASC' : 'DESC',
        'no_found_rows' => true,
    ];
    if ($ids) { $args['post__in'] = $ids; }
    elseif (! empty($attributes['categoryId'])) { $args['cat'] = absint($attributes['categoryId']); }
    if (! $ids && ! empty($attributes['_relatedPostId'])) {
        $related_post_id = absint($attributes['_relatedPostId']);
        $args['post__not_in'] = [$related_post_id];
        $category_ids = wp_get_post_categories($related_post_id, ['fields'=>'ids']);
        if ($category_ids) { $args['category__in'] = array_values(array_map('absint', $category_ids)); unset($args['cat']); }
    }
    return new WP_Query($args);
}

function my_theme_latest_articles_arrow($size = 'small', $color = '#009E00') {
    $width = 'large' === $size ? 10 : 8; $height = 'large' === $size ? 16 : 12;
    return '<svg width="' . esc_attr($width) . '" height="' . esc_attr($height) . '" viewBox="0 0 8 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M3.34799 4.9036C2.92005 5.29944 2.92005 5.97595 3.34799 6.37179L6.73372 9.5036C7.16167 9.89944 7.16167 10.5759 6.73372 10.9718L6.69287 11.0096C6.30959 11.3641 5.71807 11.3641 5.33479 11.0096L0.320966 6.3718C-0.106978 5.97595 -0.106979 5.29944 0.320965 4.9036L5.33479 0.26581C5.71807 -0.0887244 6.30959 -0.0887254 6.69287 0.265809L6.73372 0.303595C7.16167 0.699443 7.16167 1.37595 6.73372 1.77179L3.34799 4.9036Z" fill="' . esc_attr($color) . '"/></svg>';
}

function my_theme_render_latest_articles_block($attributes = [], $block = null) {
    if (! empty($attributes['relatedToCurrentPost'])) {
        $related_post_id = 0;
        if ($block instanceof WP_Block && ! empty($block->context['postId'])) { $related_post_id = absint($block->context['postId']); }
        if (! $related_post_id && is_singular('post')) { $related_post_id = get_queried_object_id(); }
        if (! $related_post_id && function_exists('my_theme_article_preview_post')) { $preview_post = my_theme_article_preview_post($block); $related_post_id = $preview_post instanceof WP_Post ? $preview_post->ID : 0; }
        if ($related_post_id) { $attributes['_relatedPostId'] = $related_post_id; }
    }
    $title = sanitize_text_field($attributes['title'] ?? 'مقاله‌ها و اخبار');
    $view_lead = sanitize_text_field($attributes['viewAllLead'] ?? 'مشاهده');
    $view_text = sanitize_text_field($attributes['viewAllText'] ?? 'همه');
    $read_more = sanitize_text_field($attributes['readMoreText'] ?? 'بیشتر بخوانید');
    $blog_url = function_exists('my_theme_get_blog_list_url') ? my_theme_get_blog_list_url() : home_url('/blog/');
    $view_url = ! empty($attributes['viewAllUrl']) ? esc_url($attributes['viewAllUrl']) : $blog_url;
    $show_view = ! isset($attributes['showViewAll']) || (bool) $attributes['showViewAll'];
    $show_excerpt = ! isset($attributes['showExcerpt']) || (bool) $attributes['showExcerpt'];
    $show_read_more = ! isset($attributes['showReadMore']) || (bool) $attributes['showReadMore'];
    $show_pagination = ! isset($attributes['showPagination']) || (bool) $attributes['showPagination'];
    $has_header = '' !== $title || ($show_view && '' !== $view_text);
    $excerpt_length = max(5, min(50, absint($attributes['excerptLength'] ?? 16)));
    $styles = [];
    $colors = ['cardBackgroundColor'=>'--latest-article-card-background','cardTitleColor'=>'--latest-article-title-color','cardExcerptColor'=>'--latest-article-excerpt-color','cardLinkColor'=>'--latest-article-link-color'];
    foreach ($colors as $name => $variable) { if (! empty($attributes[$name]) && ($color = sanitize_hex_color($attributes[$name]))) { $styles[] = $variable . ':' . $color; } }
    $numbers = ['cardRadius'=>['--latest-article-card-radius',0,60,12],'imageRadius'=>['--latest-article-image-radius',0,60,10],'titleSize'=>['--latest-article-title-size',12,32,20],'excerptSize'=>['--latest-article-excerpt-size',9,22,12],'readMoreSize'=>['--latest-article-link-size',10,24,14]];
    foreach ($numbers as $name => $setting) { $value = max($setting[1], min($setting[2], absint($attributes[$name] ?? $setting[3]))); if ($value !== $setting[3]) { $styles[] = $setting[0] . ':' . $value . 'px'; } }
    $wrapper_args = ['class'=>'latest-articles-section']; if ($styles) { $wrapper_args['style'] = implode(';', $styles); }
    $wrapper = get_block_wrapper_attributes($wrapper_args);
    $query = my_theme_latest_articles_query($attributes);
    ob_start(); ?>
    <section <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
      <?php if ($has_header) : ?>
      <div class="latest-articles-header">
        <?php if ('' !== $title) : ?><h2><?php echo esc_html($title); ?></h2><?php endif; ?>
        <?php if ($show_view && '' !== $view_text) : ?><a href="<?php echo esc_url($view_url); ?>" class="latest-articles-btn"><span><?php if ('' !== $view_lead) : ?><span class="latest-articles-extra"><?php echo esc_html($view_lead); ?> </span><?php endif; ?><?php echo esc_html($view_text); ?></span><?php echo my_theme_latest_articles_arrow('large', '#ffffff'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a><?php endif; ?>
      </div>
      <?php endif; ?>
      <div class="swiper latestArticlesSwiper"><div class="swiper-wrapper latest-articles-grid">
        <?php if ($query->have_posts()) : while ($query->have_posts()) : $query->the_post(); $post = get_post(); $permalink = get_permalink($post); ?>
          <article class="swiper-slide article-card">
            <a href="<?php echo esc_url($permalink); ?>" class="article-image"><?php if (has_post_thumbnail($post)) { echo get_the_post_thumbnail($post, 'large'); } else { echo '<div class="article-placeholder"></div>'; } ?></a>
            <div class="article-content"><a class="article-content-title" href="<?php echo esc_url($permalink); ?>"><?php echo esc_html(get_the_title($post)); ?></a>
              <?php if ($show_excerpt) : ?><p><?php echo esc_html(wp_trim_words(get_the_excerpt($post), $excerpt_length, '...')); ?></p><?php endif; ?>
              <?php if ($show_read_more && '' !== $read_more) : ?><a href="<?php echo esc_url($permalink); ?>" class="article-read-more"><span><?php echo esc_html($read_more); ?></span><?php echo my_theme_latest_articles_arrow(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a><?php endif; ?>
            </div>
          </article>
        <?php endwhile; else : ?><p class="latest-articles-empty">مقاله‌ای پیدا نشد.</p><?php endif; wp_reset_postdata(); ?>
      </div><?php if ($show_pagination) : ?><div class="swiper-pagination latest-articles-dots latest-articles-pagination"></div><?php endif; ?></div>
    </section>
    <?php return trim(ob_get_clean());
}

function my_theme_replace_legacy_latest_articles($blocks, &$changed) {
    foreach ($blocks as &$block) {
        if ('core/shortcode' === ($block['blockName'] ?? '') && false !== strpos((string) ($block['innerHTML'] ?? ''), '[latest_articles]')) {
            $block = ['blockName'=>'my-theme/latest-articles','attrs'=>[],'innerBlocks'=>[],'innerHTML'=>'','innerContent'=>[]]; $changed = true; continue;
        }
        if (! empty($block['innerBlocks'])) { $block['innerBlocks'] = my_theme_replace_legacy_latest_articles($block['innerBlocks'], $changed); }
    }
    return $blocks;
}

function my_theme_migrate_legacy_latest_articles() {
    if (! is_admin() || wp_doing_ajax()) { return; }
    $templates = get_posts(['post_type'=>'wp_template','post_status'=>['publish','draft'],'posts_per_page'=>-1,'suppress_filters'=>false]);
    foreach ($templates as $template) { $content = (string) $template->post_content; if (false === strpos($content, '[latest_articles]')) { continue; } $changed = false; $blocks = my_theme_replace_legacy_latest_articles(parse_blocks($content), $changed); if (! $changed) { continue; } if ('' === (string) get_post_meta($template->ID, '_my_theme_latest_articles_backup', true)) { add_post_meta($template->ID, '_my_theme_latest_articles_backup', $content, true); } wp_update_post(['ID'=>$template->ID,'post_content'=>serialize_blocks($blocks)]); }
}
add_action('admin_init', 'my_theme_migrate_legacy_latest_articles', 12);
