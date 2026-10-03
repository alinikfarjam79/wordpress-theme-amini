<?php
/** Gutenberg/FSE blocks for the single article template. */
if (! defined('ABSPATH')) { exit; }

function my_theme_register_single_article_style() {
    $path = get_theme_file_path('assets/css/single-article.css');
    if (! file_exists($path) || wp_style_is('my-theme-single-article', 'registered')) { return; }
    wp_register_style('my-theme-single-article', get_theme_file_uri('assets/css/single-article.css'), [], filemtime($path));
}
add_action('init', 'my_theme_register_single_article_style', 4);

function my_theme_article_preview_post($block = null) {
    $post_id = 0;
    if ($block instanceof WP_Block && ! empty($block->context['postId'])) { $post_id = absint($block->context['postId']); }
    if (! $post_id && is_singular('post')) { $post_id = get_queried_object_id(); }
    $post = $post_id ? get_post($post_id) : null;
    if ($post instanceof WP_Post && 'post' === $post->post_type) { return $post; }
    $posts = get_posts(['post_type'=>'post','post_status'=>'publish','posts_per_page'=>1,'orderby'=>'date','order'=>'DESC']);
    return $posts ? $posts[0] : null;
}

function my_theme_article_breadcrumb_styles($attributes) {
    $styles = [];
    $font_sizes = ['sm'=>'14px','base'=>'16px','md'=>'18px'];
    if (! empty($attributes['fontSize']) && isset($font_sizes[$attributes['fontSize']])) { $styles[] = '--product-breadcrumb-font-size:' . $font_sizes[$attributes['fontSize']]; }
    if (! empty($attributes['fontWeight']) && in_array((string) $attributes['fontWeight'], ['400','500','700'], true)) { $styles[] = '--product-breadcrumb-font-weight:' . $attributes['fontWeight']; }
    foreach (['inactiveColor'=>'--product-breadcrumb-inactive-color','activeColor'=>'--product-breadcrumb-active-color'] as $key=>$variable) { if (! empty($attributes[$key])) { $styles[] = $variable . ':' . sanitize_text_field($attributes[$key]); } }
    return implode(';', $styles);
}

function my_theme_render_article_breadcrumb($attributes = [], $block = null) {
    $post = my_theme_article_preview_post($block);
    if (! $post instanceof WP_Post) { return '<nav class="single-product-breadcrumb"><ol class="woocommerce-breadcrumb"><li class="single-product-breadcrumb__item">مقاله‌ای برای پیش‌نمایش وجود ندارد.</li></ol></nav>'; }
    $categories = get_the_category($post->ID); $category = $categories ? $categories[0] : null;
    $blog_url = function_exists('my_theme_get_blog_list_url') ? my_theme_get_blog_list_url() : home_url('/blog/');
    $separator = ! empty($attributes['separator']) ? sanitize_text_field($attributes['separator']) : '/';
    $nav = get_block_wrapper_attributes(['class'=>'single-product-breadcrumb','style'=>my_theme_article_breadcrumb_styles($attributes)]);
    ob_start(); ?>
    <nav <?php echo $nav; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php echo esc_attr__('Breadcrumb', 'my-theme'); ?>"><ol class="woocommerce-breadcrumb">
      <li class="single-product-breadcrumb__item"><a href="<?php echo esc_url(home_url('/')); ?>">خانه</a></li>
      <li class="single-product-breadcrumb__separator" aria-hidden="true"><?php echo esc_html($separator); ?></li>
      <li class="single-product-breadcrumb__item"><a href="<?php echo esc_url($blog_url); ?>">مقالات و اخبار</a></li>
      <?php if ($category instanceof WP_Term) : ?><li class="single-product-breadcrumb__separator" aria-hidden="true"><?php echo esc_html($separator); ?></li><li class="single-product-breadcrumb__item"><a href="<?php echo esc_url(get_category_link($category->term_id)); ?>"><?php echo esc_html($category->name); ?></a></li><?php endif; ?>
      <li class="single-product-breadcrumb__separator" aria-hidden="true"><?php echo esc_html($separator); ?></li>
      <li class="single-product-breadcrumb__item" aria-current="page"><?php echo esc_html(get_the_title($post)); ?></li>
    </ol></nav>
    <?php return trim(ob_get_clean());
}

function my_theme_render_article_categories($attributes = [], $block = null) {
    $post = my_theme_article_preview_post($block); if (! $post instanceof WP_Post) { return ''; }
    $categories = get_the_category($post->ID); if (! $categories) { return ''; }
    $styles = [];
    foreach (['backgroundColor'=>'--article-badge-background','textColor'=>'--article-badge-color'] as $key=>$variable) { if (! empty($attributes[$key])) { $styles[] = $variable . ':' . sanitize_text_field($attributes[$key]); } }
    $font_size = max(10, min(24, absint($attributes['fontSize'] ?? 14))); $radius = max(0, min(32, absint($attributes['borderRadius'] ?? 4)));
    $styles[] = '--article-badge-font-size:' . $font_size . 'px'; $styles[] = '--article-badge-radius:' . $radius . 'px';
    $wrapper = get_block_wrapper_attributes(['class'=>'single-article-categories','style'=>implode(';', $styles)]);
    ob_start(); ?><div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php foreach ($categories as $category) : ?><a class="single-article-category-badge" href="<?php echo esc_url(get_category_link($category->term_id)); ?>"><?php echo esc_html($category->name); ?></a><?php endforeach; ?></div><?php return trim(ob_get_clean());
}

function my_theme_article_persian_digits($value) {
    return strtr((string) $value, ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']);
}

function my_theme_article_gregorian_to_jalali($year, $month, $day) {
    $g_days = [0,31,59,90,120,151,181,212,243,273,304,334];
    $year2 = $month > 2 ? $year + 1 : $year;
    $days = 355666 + (365 * $year) + intdiv($year2 + 3, 4) - intdiv($year2 + 99, 100) + intdiv($year2 + 399, 400) + $day + $g_days[$month - 1];
    $jalali_year = -1595 + (33 * intdiv($days, 12053));
    $days %= 12053;
    $jalali_year += 4 * intdiv($days, 1461);
    $days %= 1461;
    if ($days > 365) { $jalali_year += intdiv($days - 1, 365); $days = ($days - 1) % 365; }
    if ($days < 186) { $jalali_month = 1 + intdiv($days, 31); $jalali_day = 1 + ($days % 31); }
    else { $jalali_month = 7 + intdiv($days - 186, 30); $jalali_day = 1 + (($days - 186) % 30); }
    return [$jalali_year, $jalali_month, $jalali_day];
}

function my_theme_article_jalali_date($post) {
    $timestamp = function_exists('get_post_timestamp') ? get_post_timestamp($post) : strtotime($post->post_date);
    $year = (int) wp_date('Y', $timestamp); $month = (int) wp_date('n', $timestamp); $day = (int) wp_date('j', $timestamp);
    [$jy, $jm, $jd] = my_theme_article_gregorian_to_jalali($year, $month, $day);
    $months = [1=>'فروردین',2=>'اردیبهشت',3=>'خرداد',4=>'تیر',5=>'مرداد',6=>'شهریور',7=>'مهر',8=>'آبان',9=>'آذر',10=>'دی',11=>'بهمن',12=>'اسفند'];
    return my_theme_article_persian_digits($jd) . ' ' . $months[$jm] . ' ' . my_theme_article_persian_digits($jy);
}

function my_theme_article_reading_minutes($post, $words_per_minute = 200) {
    $content = trim(wp_strip_all_tags(strip_shortcodes((string) $post->post_content)));
    if ('' === $content) { return 1; }
    $words = preg_split('/\s+/u', $content, -1, PREG_SPLIT_NO_EMPTY);
    return max(1, (int) ceil(count($words) / max(100, min(400, absint($words_per_minute)))));
}

function my_theme_render_article_meta($attributes = [], $block = null) {
    $post = my_theme_article_preview_post($block); if (! $post instanceof WP_Post) { return ''; }
    $author_id = (int) $post->post_author;
    $author_name = get_the_author_meta('display_name', $author_id);
    $minutes = my_theme_article_reading_minutes($post, $attributes['wordsPerMinute'] ?? 200);
    $reading_label = sanitize_text_field($attributes['readingLabel'] ?? 'زمان مطالعه:');
    $minute_label = sanitize_text_field($attributes['minuteLabel'] ?? 'دقیقه');
    $date_label = sanitize_text_field($attributes['dateLabel'] ?? 'تاریخ انتشار:');
    $show_avatar = ! isset($attributes['showAvatar']) || (bool) $attributes['showAvatar'];
    $wrapper = get_block_wrapper_attributes(['class'=>'single-article-meta']);
    ob_start(); ?><div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
      <div class="single-article-meta__author"><?php if ($show_avatar) { echo get_avatar($author_id, 28, '', $author_name, ['class'=>'single-article-meta__author-avatar']); } ?><span><?php echo esc_html($author_name); ?></span></div>
      <div class="single-article-meta__reading-time"><span><?php echo esc_html(trim($reading_label . ' ' . my_theme_article_persian_digits($minutes) . ' ' . $minute_label)); ?></span></div>
      <div class="single-article-meta__date"><span><?php echo esc_html(trim($date_label . ' ' . my_theme_article_jalali_date($post))); ?></span></div>
    </div><?php return trim(ob_get_clean());
}

function my_theme_article_collect_toc_items($blocks, &$items, $include_numbered = true) {
    foreach ((array) $blocks as $content_block) {
        $name = $content_block['blockName'] ?? '';
        $text = trim(wp_strip_all_tags((string) ($content_block['innerHTML'] ?? '')));
        $is_heading = 'core/heading' === $name && in_array(absint($content_block['attrs']['level'] ?? 2), [2,3,4], true);
        $is_numbered = $include_numbered && 'core/paragraph' === $name && preg_match('/^[۰-۹٠-٩0-9]+\s*[\.\x{06D4}\)\-:]\s*/u', $text);
        if (($is_heading || $is_numbered) && '' !== $text) {
            $anchor = ! empty($content_block['attrs']['anchor']) ? sanitize_title($content_block['attrs']['anchor']) : 'article-section-' . (count($items) + 1);
            $items[] = ['text'=>$text,'anchor'=>$anchor];
        }
        if (! empty($content_block['innerBlocks'])) { my_theme_article_collect_toc_items($content_block['innerBlocks'], $items, $include_numbered); }
    }
}

function my_theme_render_article_toc($attributes = [], $block = null) {
    $post = my_theme_article_preview_post($block); if (! $post instanceof WP_Post) { return ''; }
    $items = []; my_theme_article_collect_toc_items(parse_blocks($post->post_content), $items, ! isset($attributes['includeNumberedParagraphs']) || (bool) $attributes['includeNumberedParagraphs']);
    $title = sanitize_text_field($attributes['title'] ?? 'در این مقاله می‌خوانید:');
    $wrapper = get_block_wrapper_attributes(['class'=>'single-article-toc']);
    ob_start(); ?><aside <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-article-toc>
      <?php if ('' !== $title) : ?><h2 class="single-article-toc__title"><?php echo esc_html($title); ?></h2><?php endif; ?>
      <ul class="single-article-toc__list"><?php foreach ($items as $index=>$item) : ?><li class="single-article-toc__item"><a class="single-article-toc__link<?php echo 0 === $index ? ' is-active' : ''; ?>" href="#<?php echo esc_attr($item['anchor']); ?>" data-toc-index="<?php echo esc_attr($index); ?>"><span class="single-article-toc__link-text"><?php echo esc_html($item['text']); ?></span></a></li><?php endforeach; ?></ul>
    </aside><?php return trim(ob_get_clean());
}

function my_theme_article_comment_star_svg() {
    return '<svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path fill="currentColor" d="m10 1.7 2.55 5.17 5.7.83-4.13 4.02.98 5.68L10 14.72 4.9 17.4l.98-5.68L1.75 7.7l5.7-.83L10 1.7Z"/></svg>';
}

function my_theme_render_article_comments($attributes = [], $block = null) {
    $post = my_theme_article_preview_post($block);
    if (! $post instanceof WP_Post) { return ''; }
    $wrapper = get_block_wrapper_attributes(['class'=>'single-article-comments']);
    if (! comments_open($post->ID) && ! is_admin()) {
        return '<section ' . $wrapper . '><p class="single-article-comments__closed">' . esc_html__('امکان ثبت دیدگاه برای این مقاله بسته است.', 'my-theme') . '</p></section>';
    }
    $commenter = wp_get_current_commenter();
    $user = wp_get_current_user();
    $name = is_user_logged_in() ? $user->display_name : ($commenter['comment_author'] ?? '');
    $phone = is_user_logged_in() ? get_user_meta(get_current_user_id(), 'billing_phone', true) : '';
    $stars = '';
    for ($rating = 1; $rating <= 5; ++$rating) {
        $stars .= '<button type="button" class="product-review-rating-star" data-article-rating-star="' . $rating . '" aria-pressed="false" aria-label="' . esc_attr(sprintf(__('%s ستاره', 'my-theme'), my_theme_article_persian_digits($rating))) . '">' . my_theme_article_comment_star_svg() . '</button>';
    }
    $required = get_option('require_name_email', 1) ? ' required' : '';
    $fields = [];
    if (! is_user_logged_in()) {
        $fields['author'] = '<div class="product-review-form__identity-fields"><p class="comment-form-author product-review-form__field product-review-form__name-field"><label class="product-review-form__label" for="author">' . esc_html__('نام و نام خانوادگی*', 'my-theme') . '</label><input id="author" name="author" type="text" value="' . esc_attr($name) . '"' . $required . ' autocomplete="name"></p>';
        $fields['email'] = '<p class="comment-form-email product-review-form__field"><label class="product-review-form__label" for="email">' . esc_html__('ایمیل*', 'my-theme') . '</label><input id="email" name="email" type="email" value="' . esc_attr($commenter['comment_author_email'] ?? '') . '"' . $required . ' autocomplete="email"></p></div>';
    }
    $comment_field = '<p class="product-review-form__intro">' . esc_html__('دیدگاه خود را با ما به اشتراک بگذارید.', 'my-theme') . '</p>' .
        '<div class="product-review-form__rating-row"><label class="product-review-form__label" for="article-rating">' . esc_html__('امتیاز شما*', 'my-theme') . '</label><span class="product-review-rating-stars" role="radiogroup" aria-label="' . esc_attr__('امتیاز شما', 'my-theme') . '">' . $stars . '</span><select name="article_rating" id="article-rating" class="product-review-rating-select" required><option value="">' . esc_html__('انتخاب امتیاز', 'my-theme') . '</option><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option></select></div>' .
        (is_user_logged_in() ? '<p class="comment-form-author product-review-form__field product-review-form__name-field"><label class="product-review-form__label" for="article-author">' . esc_html__('نام و نام خانوادگی*', 'my-theme') . '</label><input id="article-author" name="my_theme_article_author_name" type="text" value="' . esc_attr($name) . '" required autocomplete="name"></p>' : '') .
        '<p class="comment-form-phone product-review-form__field product-review-form__phone-field"><label class="product-review-form__label" for="article-review-phone">' . esc_html__('شماره موبایل*', 'my-theme') . '</label><input id="article-review-phone" name="my_theme_article_phone" type="tel" inputmode="tel" value="' . esc_attr($phone) . '" required autocomplete="tel"></p>' .
        '<p class="comment-form-comment product-review-form__field product-review-form__comment-field"><label class="product-review-form__label" for="comment">' . esc_html__('دیدگاه*', 'my-theme') . '</label><textarea id="comment" name="comment" cols="45" rows="8" required></textarea></p>';
    $submit_field = '<div class="product-review-form__bottom-row"><label class="product-review-form__anonymous-row" for="article-review-hide-author"><input id="article-review-hide-author" class="product-review-form__anonymous-checkbox" name="article_review_hide_author" type="checkbox" value="1"><span class="product-review-form__anonymous-text">' . esc_html__('عدم نمایش نام شما در دیدگاه', 'my-theme') . '</span></label><p class="form-submit">%1$s</p></div>%2$s';
    ob_start();
    echo '<section ' . $wrapper . '><div id="review_form_wrapper"><div id="review_form">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    comment_form(['title_reply'=>'','title_reply_before'=>'','title_reply_after'=>'','comment_notes_before'=>'','comment_notes_after'=>'','logged_in_as'=>'','fields'=>$fields,'comment_field'=>$comment_field,'submit_field'=>$submit_field,'class_form'=>'comment-form product-review-form','label_submit'=>__('ثبت دیدگاه', 'my-theme'),'class_submit'=>'submit product-review-form__submit'], $post->ID);
    echo '</div></div></section>';
    return trim(ob_get_clean());
}

function my_theme_validate_article_comment_fields($commentdata) {
    if (empty($commentdata['comment_post_ID']) || 'post' !== get_post_type((int) $commentdata['comment_post_ID'])) { return $commentdata; }
    $phone = isset($_POST['my_theme_article_phone']) ? sanitize_text_field(wp_unslash($_POST['my_theme_article_phone'])) : '';
    $rating = isset($_POST['article_rating']) ? absint($_POST['article_rating']) : 0;
    if ('' === trim($phone)) { wp_die(esc_html__('لطفاً شماره موبایل را وارد کنید.', 'my-theme'), esc_html__('خطا در ثبت دیدگاه', 'my-theme'), ['response'=>400]); }
    if ($rating < 1 || $rating > 5) { wp_die(esc_html__('لطفاً امتیاز خود را انتخاب کنید.', 'my-theme'), esc_html__('خطا در ثبت دیدگاه', 'my-theme'), ['response'=>400]); }
    if (is_user_logged_in() && isset($_POST['my_theme_article_author_name'])) {
        $author = sanitize_text_field(wp_unslash($_POST['my_theme_article_author_name']));
        if ('' === trim($author)) { wp_die(esc_html__('لطفاً نام و نام خانوادگی را وارد کنید.', 'my-theme'), esc_html__('خطا در ثبت دیدگاه', 'my-theme'), ['response'=>400]); }
        $commentdata['comment_author'] = $author;
    }
    if (! empty($_POST['article_review_hide_author'])) { $commentdata['comment_author'] = __('کاربر ناشناس', 'my-theme'); }
    return $commentdata;
}
add_filter('preprocess_comment', 'my_theme_validate_article_comment_fields');

function my_theme_save_article_comment_fields($comment_id) {
    $comment = get_comment($comment_id);
    if (! $comment instanceof WP_Comment || 'post' !== get_post_type((int) $comment->comment_post_ID)) { return; }
    if (isset($_POST['my_theme_article_phone'])) { update_comment_meta($comment_id, '_my_theme_article_phone', sanitize_text_field(wp_unslash($_POST['my_theme_article_phone']))); }
    if (isset($_POST['article_rating'])) { update_comment_meta($comment_id, '_my_theme_article_rating', max(1, min(5, absint($_POST['article_rating'])))); }
}
add_action('comment_post', 'my_theme_save_article_comment_fields');

function my_theme_fix_saved_single_article_template_class() {
    if (! is_admin() || wp_doing_ajax()) { return; }
    $templates = get_posts(['post_type'=>'wp_template','post_status'=>['publish','draft'],'posts_per_page'=>-1,'suppress_filters'=>false]);
    foreach ($templates as $template) {
        $content = (string) $template->post_content;
        if (false === strpos($content, 'single-article-page') || false === strpos($content, 'single-product-page')) { continue; }
        $updated = preg_replace('/\bsingle-product-page\s*/', '', $content);
        if (! is_string($updated) || $updated === $content) { continue; }
        if ('' === (string) get_post_meta($template->ID, '_my_theme_article_header_class_backup', true)) { add_post_meta($template->ID, '_my_theme_article_header_class_backup', $content, true); }
        wp_update_post(['ID'=>$template->ID,'post_content'=>$updated]);
    }
}
add_action('admin_init', 'my_theme_fix_saved_single_article_template_class', 13);

function my_theme_add_featured_image_to_saved_article_template() {
    if (! is_admin() || wp_doing_ajax()) { return; }
    $templates = get_posts(['post_type'=>'wp_template','post_status'=>['publish','draft'],'posts_per_page'=>-1,'suppress_filters'=>false]);
    $image_block = '<!-- wp:post-featured-image {"className":"single-article-featured-image"} /-->';
    foreach ($templates as $template) {
        $content = (string) $template->post_content;
        if (false === strpos($content, 'single-article-page') || false === strpos($content, '<!-- wp:theme/article-meta /-->') || false !== strpos($content, 'single-article-featured-image')) { continue; }
        $updated = str_replace('<!-- wp:theme/article-meta /-->', '<!-- wp:theme/article-meta /-->' . "\n" . $image_block, $content);
        if ($updated === $content) { continue; }
        if ('' === (string) get_post_meta($template->ID, '_my_theme_article_featured_image_backup', true)) { add_post_meta($template->ID, '_my_theme_article_featured_image_backup', $content, true); }
        wp_update_post(['ID'=>$template->ID,'post_content'=>$updated]);
    }
}
add_action('admin_init', 'my_theme_add_featured_image_to_saved_article_template', 14);

function my_theme_add_body_layout_to_saved_article_template() {
    if (! is_admin() || wp_doing_ajax()) { return; }
    $templates = get_posts(['post_type'=>'wp_template','post_status'=>['publish','draft'],'posts_per_page'=>-1,'suppress_filters'=>false]);
    $image_block = '<!-- wp:post-featured-image {"className":"single-article-featured-image"} /-->';
    $body_blocks = "\n<!-- wp:group {\"className\":\"single-article-body-layout\",\"layout\":{\"type\":\"default\"}} -->\n<div class=\"wp-block-group single-article-body-layout\">\n<!-- wp:post-content {\"className\":\"single-article-body-content\",\"layout\":{\"type\":\"default\"}} /-->\n<!-- wp:theme/article-toc /-->\n</div>\n<!-- /wp:group -->";
    foreach ($templates as $template) {
        $content = (string) $template->post_content;
        if (false === strpos($content, 'single-article-page') || false === strpos($content, $image_block) || false !== strpos($content, 'single-article-body-layout')) { continue; }
        $updated = str_replace($image_block, $image_block . $body_blocks, $content); if ($updated === $content) { continue; }
        if ('' === (string) get_post_meta($template->ID, '_my_theme_article_body_layout_backup', true)) { add_post_meta($template->ID, '_my_theme_article_body_layout_backup', $content, true); }
        wp_update_post(['ID'=>$template->ID,'post_content'=>$updated]);
    }
}
add_action('admin_init', 'my_theme_add_body_layout_to_saved_article_template', 15);

function my_theme_add_comments_to_saved_article_template() {
    if (wp_doing_ajax()) { return; }
    $templates = get_posts(['post_type'=>'wp_template','post_status'=>['publish','draft'],'posts_per_page'=>-1,'suppress_filters'=>false]);
    foreach ($templates as $template) {
        $content = (string) $template->post_content;
        if (false === strpos($content, 'single-article-page') || false === strpos($content, 'single-article-body-layout')) { continue; }
        if (false !== strpos($content, 'single-article-content-column') && false !== strpos($content, 'theme/article-comments')) { continue; }
        $without_comments = str_replace('<!-- wp:theme/article-comments /-->', '', $content);
        $pattern = '/(<!-- wp:post-content \{[^\n]*"className":"single-article-body-content"[^\n]*\} \/-->)/';
        $replacement = '<!-- wp:group {"className":"single-article-content-column","layout":{"type":"default"}} -->' . "\n" .
            '<div class="wp-block-group single-article-content-column">' . "\n" . '$1' . "\n" .
            '<!-- wp:theme/article-comments /-->' . "\n" . '</div>' . "\n" . '<!-- /wp:group -->';
        if (false !== strpos($without_comments, 'single-article-content-column')) {
            $updated = preg_replace('/(<!-- wp:post-content \{[^\n]*"className":"single-article-body-content"[^\n]*\} \/-->)/', '$1' . "\n<!-- wp:theme/article-comments /-->", $without_comments, 1);
        } else {
            $updated = preg_replace($pattern, $replacement, $without_comments, 1);
        }
        if (! is_string($updated) || $updated === $content || false === strpos($updated, 'theme/article-comments')) { continue; }
        if ('' === (string) get_post_meta($template->ID, '_my_theme_article_comments_backup', true)) { add_post_meta($template->ID, '_my_theme_article_comments_backup', $content, true); }
        wp_update_post(['ID'=>$template->ID,'post_content'=>$updated]);
    }
}
add_action('init', 'my_theme_add_comments_to_saved_article_template', 20);

function my_theme_article_comments_render_fallback($block_content, $block) {
    $class_name = (string) ($block['attrs']['className'] ?? '');
    if ('core/group' !== ($block['blockName'] ?? '') || false === strpos($class_name, 'single-article-content-column')) { return $block_content; }
    if (false !== strpos($block_content, 'single-article-comments')) { return $block_content; }
    $comment_blocks = parse_blocks('<!-- wp:theme/article-comments /-->');
    if (empty($comment_blocks[0])) { return $block_content; }
    $comments = render_block($comment_blocks[0]);
    if ('' === trim($comments)) { return $block_content; }
    $updated = preg_replace('/<\/div>\s*$/', $comments . '</div>', $block_content, 1);
    return is_string($updated) && $updated !== $block_content ? $updated : $block_content . $comments;
}
add_filter('render_block', 'my_theme_article_comments_render_fallback', 20, 2);

function my_theme_append_related_articles_block(&$blocks) {
    foreach ($blocks as &$template_block) {
        $class_name = (string) ($template_block['attrs']['className'] ?? '');
        if ('core/group' === ($template_block['blockName'] ?? '') && false !== strpos($class_name, 'single-article-intro')) {
            $related = parse_blocks('<!-- wp:my-theme/latest-articles {"title":"مقالات مرتبط","relatedToCurrentPost":true,"showViewAll":false,"postsCount":3,"className":"single-article-related"} /-->');
            if (empty($related[0])) { return false; }
            $template_block['innerBlocks'][] = $related[0];
            $inner_content = (array) ($template_block['innerContent'] ?? []);
            if ($inner_content) { array_splice($inner_content, max(0, count($inner_content) - 1), 0, [null]); }
            else { $inner_content = [null]; }
            $template_block['innerContent'] = $inner_content;
            return true;
        }
        if (! empty($template_block['innerBlocks']) && my_theme_append_related_articles_block($template_block['innerBlocks'])) { return true; }
    }
    unset($template_block);
    return false;
}

function my_theme_add_related_articles_to_saved_template() {
    if (wp_doing_ajax()) { return; }
    $templates = get_posts(['post_type'=>'wp_template','post_status'=>['publish','draft'],'posts_per_page'=>-1,'suppress_filters'=>false]);
    foreach ($templates as $template) {
        $content = (string) $template->post_content;
        if (false === strpos($content, 'single-article-page') || false === strpos($content, 'single-article-intro') || false !== strpos($content, 'single-article-related')) { continue; }
        $blocks = parse_blocks($content);
        if (! my_theme_append_related_articles_block($blocks)) { continue; }
        $updated = serialize_blocks($blocks);
        if ('' === $updated || $updated === $content) { continue; }
        if ('' === (string) get_post_meta($template->ID, '_my_theme_article_related_backup', true)) { add_post_meta($template->ID, '_my_theme_article_related_backup', $content, true); }
        wp_update_post(['ID'=>$template->ID,'post_content'=>$updated]);
    }
}
add_action('init', 'my_theme_add_related_articles_to_saved_template', 22);

function my_theme_article_related_render_fallback($block_content, $block) {
    $class_name = (string) ($block['attrs']['className'] ?? '');
    if ('core/group' !== ($block['blockName'] ?? '') || false === strpos($class_name, 'single-article-intro') || false !== strpos($block_content, 'single-article-related')) { return $block_content; }
    $related_blocks = parse_blocks('<!-- wp:my-theme/latest-articles {"title":"مقالات مرتبط","relatedToCurrentPost":true,"showViewAll":false,"postsCount":3,"className":"single-article-related"} /-->');
    if (empty($related_blocks[0])) { return $block_content; }
    $related = render_block($related_blocks[0]);
    if ('' === trim($related)) { return $block_content; }
    $updated = preg_replace('/<\/div>\s*$/', $related . '</div>', $block_content, 1);
    return is_string($updated) && $updated !== $block_content ? $updated : $block_content . $related;
}
add_filter('render_block', 'my_theme_article_related_render_fallback', 21, 2);
