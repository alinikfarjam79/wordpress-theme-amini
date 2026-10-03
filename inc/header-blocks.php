<?php
/** Header block renderers and migration for legacy saved template parts. */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Render the Header menu directly from published pages.
 *
 * This menu intentionally does not reuse any saved wp_navigation entity, so a
 * Footer navigation can never leak into the Header. It can use published pages
 * automatically or a manually managed list saved in the Header block.
 *
 * @param array $attributes Block attributes.
 * @return string
 */
function my_theme_render_header_pages_menu($attributes = []) {
    $excluded_ids = [];
    $search_page  = get_page_by_path('search-results', OBJECT, 'page');

    if ($search_page instanceof WP_Post) {
        $excluded_ids[] = absint($search_page->ID);
    }

    $manual_mode = ! empty($attributes['manualMode']);
    $menu_items  = [];
    $inner_blocks = '';

    if ($manual_mode) {
        $saved_items = isset($attributes['items']) && is_array($attributes['items'])
            ? $attributes['items']
            : [];

        foreach ($saved_items as $item) {
            $label = isset($item['label']) ? sanitize_text_field((string) $item['label']) : '';
            $url   = isset($item['url']) ? esc_url_raw((string) $item['url']) : '';

            if ('' === $label || '' === $url) {
                continue;
            }

            $menu_items[] = [
                'label' => $label,
                'url'   => $url,
                'megaMenuEnabled' => ! empty($item['megaMenuEnabled']),
                'megaMenuColumns' => isset($item['megaMenuColumns']) ? absint($item['megaMenuColumns']) : 3,
                'megaMenuRows' => isset($item['megaMenuRows']) ? absint($item['megaMenuRows']) : 2,
                'megaMenuCells' => isset($item['megaMenuCells']) && is_array($item['megaMenuCells'])
                    ? $item['megaMenuCells']
                    : [],
            ];
        }
    } else {
        $auto_count = isset($attributes['autoCount']) ? absint($attributes['autoCount']) : 6;
        $auto_count = max(1, min(12, $auto_count));

        $pages = get_pages(
            [
                'number'      => $auto_count,
                'post_status' => 'publish',
                'sort_column' => 'menu_order,post_title',
                'sort_order'  => 'ASC',
                'exclude'     => implode(',', $excluded_ids),
            ]
        );

        foreach (array_values($pages) as $page) {
            if (! $page instanceof WP_Post) {
                continue;
            }

            $menu_items[] = [
                'label' => get_the_title($page),
                'url'   => get_permalink($page),
                'kind'  => 'post-type',
                'type'  => 'page',
                'id'    => absint($page->ID),
            ];
        }
    }

    foreach ($menu_items as $index => $item) {
        $link_attributes = [
            'label' => $item['label'],
            'url'   => $item['url'],
        ];

        foreach (['kind', 'type', 'id'] as $key) {
            if (isset($item[$key])) {
                $link_attributes[$key] = $item[$key];
            }
        }

        if (! empty($item['megaMenuEnabled'])) {
            $link_attributes['myThemeMegaMenuEnabled'] = true;
            $link_attributes['myThemeMegaMenuColumns'] = max(1, min(6, absint($item['megaMenuColumns'] ?? 3)));
            $link_attributes['myThemeMegaMenuRows'] = max(1, min(6, absint($item['megaMenuRows'] ?? 2)));
            $link_attributes['myThemeMegaMenuCells'] = isset($item['megaMenuCells']) && is_array($item['megaMenuCells'])
                ? $item['megaMenuCells']
                : [];
        }

        $inner_blocks .= '<!-- wp:navigation-link '
            . wp_json_encode($link_attributes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            . ' /-->';

        if (4 === $index && count($menu_items) > 5) {
            $inner_blocks .= '<!-- wp:my-theme/navigation-divider /-->';
        }
    }

    if ('' === $inner_blocks) {
        return '';
    }

    $navigation_attributes = [
        'overlayMenu' => 'mobile',
        'className'   => 'site-header-pages-navigation',
        'layout'      => [
            'type'           => 'flex',
            'justifyContent' => 'start',
        ],
        'style'       => [
            'spacing' => [
                'padding' => [
                    'bottom' => '30px',
                    'right'  => '50px',
                ],
            ],
        ],
    ];

    return do_blocks(
        '<!-- wp:navigation '
        . wp_json_encode($navigation_attributes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        . ' -->'
        . $inner_blocks
        . '<!-- /wp:navigation -->'
    );
}

/**
 * Register the dynamic Header pages menu.
 *
 * @return void
 */
function my_theme_register_header_pages_menu_block() {
    register_block_type(get_theme_file_path('blocks/header-pages-menu'));
}
add_action('init', 'my_theme_register_header_pages_menu_block');

/**
 * Replace the first legacy Navigation block in a saved Header template part.
 *
 * @param array $blocks  Parsed blocks.
 * @param bool  $changed Whether a replacement was made.
 * @return array
 */
function my_theme_replace_saved_header_navigation(array $blocks, &$changed) {
    foreach ($blocks as $index => $block) {
        if ('core/navigation' === ($block['blockName'] ?? '')) {
            $blocks[$index] = parse_blocks('<!-- wp:my-theme/header-pages-menu /-->')[0];
            $changed        = true;
            return $blocks;
        }

        if (empty($block['innerBlocks']) || ! is_array($block['innerBlocks'])) {
            continue;
        }

        $blocks[$index]['innerBlocks'] = my_theme_replace_saved_header_navigation(
            $block['innerBlocks'],
            $changed
        );

        if ($changed) {
            return $blocks;
        }
    }

    return $blocks;
}

/**
 * Disconnect database-backed Header parts from Footer/saved navigations.
 *
 * @return void
 */
function my_theme_migrate_header_to_pages_menu() {
    if (! is_admin() || wp_doing_ajax()) {
        return;
    }

    $migration_version = '1';

    if ($migration_version === (string) get_option('my_theme_header_pages_menu_migration', '')) {
        return;
    }

    $header_parts = get_posts(
        [
            'name'             => 'header',
            'post_type'        => 'wp_template_part',
            'post_status'      => ['publish', 'draft'],
            'posts_per_page'   => -1,
            'suppress_filters' => false,
        ]
    );

    foreach ($header_parts as $header_part) {
        if (! $header_part instanceof WP_Post) {
            continue;
        }

        $original = (string) $header_part->post_content;

        if (false !== strpos($original, 'wp:my-theme/header-pages-menu')) {
            continue;
        }

        $changed = false;
        $blocks  = my_theme_replace_saved_header_navigation(parse_blocks($original), $changed);

        if (! $changed) {
            continue;
        }

        if ('' === (string) get_post_meta($header_part->ID, '_my_theme_header_pages_menu_backup', true)) {
            add_post_meta($header_part->ID, '_my_theme_header_pages_menu_backup', $original, true);
        }

        wp_update_post(
            [
                'ID'           => $header_part->ID,
                'post_content' => serialize_blocks($blocks),
            ]
        );
    }

    update_option('my_theme_header_pages_menu_migration', $migration_version, false);
}
add_action('admin_init', 'my_theme_migrate_header_to_pages_menu', 30);

function my_theme_render_header_logo($attributes = []) {
    $media_id = isset($attributes['mediaId']) ? absint($attributes['mediaId']) : 0;
    $url      = isset($attributes['mediaUrl']) ? esc_url_raw($attributes['mediaUrl']) : '';
    $alt      = isset($attributes['alt']) ? sanitize_text_field($attributes['alt']) : '';

    if ($media_id) {
        $attachment_url = wp_get_attachment_image_url($media_id, 'full');
        if ($attachment_url) {
            $url = $attachment_url;
        }
        if ('' === $alt) {
            $alt = (string) get_post_meta($media_id, '_wp_attachment_image_alt', true);
        }
    }

    if ('' === $url) {
        $custom_logo_id = (int) get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $url = (string) wp_get_attachment_image_url($custom_logo_id, 'full');
        }
    }

    if ('' === $url) {
        $url = (string) get_option('theme_logo', '');
    }

    if ('' === $url) {
        return '<a class="theme-logo-link theme-logo-link--text" href="' . esc_url(home_url('/')) . '">' . esc_html(get_bloginfo('name')) . '</a>';
    }

    if ('' === $alt) {
        $alt = (string) get_bloginfo('name');
    }

    return '<a class="theme-logo-link" href="' . esc_url(home_url('/')) . '" aria-label="' . esc_attr(get_bloginfo('name')) . '"><img class="theme-logo-image" src="' . esc_url($url) . '" alt="' . esc_attr($alt) . '"></a>';
}

function my_theme_render_account_link($attributes = []) {
    $label = isset($attributes['label']) && '' !== trim((string) $attributes['label'])
        ? sanitize_text_field($attributes['label'])
        : 'ورود | ثبت نام';
    $url = isset($attributes['url']) ? esc_url_raw($attributes['url']) : '';

    if ('' === $url) {
        $url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');
    }

    $icon = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M8.622 2H4.207A2.207 2.207 0 0 0 2 4.207V19.66a2.207 2.207 0 0 0 2.207 2.207h4.415M14.14 6.415l-5.518 5.518 5.518 5.519m-5.518-5.519h13.244" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';

    return '<a class="theme-account-link" href="' . esc_url($url) . '">' . $icon . '<span>' . esc_html($label) . '</span></a>';
}

/**
 * Convert legacy shortcode/HTML header items in database-backed FSE headers.
 * A backup is retained once in post meta before the first write.
 */
function my_theme_migrate_legacy_header_items_to_blocks() {
    if (! is_admin() || wp_doing_ajax()) {
        return;
    }

    $header_parts = get_posts([
        'name' => 'header',
        'post_type' => 'wp_template_part',
        'post_status' => ['publish', 'draft'],
        'posts_per_page' => -1,
        'suppress_filters' => false,
    ]);

    foreach ($header_parts as $header_part) {
        if (! $header_part instanceof WP_Post) {
            continue;
        }

        $original = (string) $header_part->post_content;
        $content  = preg_replace(
            '/<!--\s+wp:shortcode\s+-->\s*\[theme_logo\]\s*<!--\s+\/wp:shortcode\s+-->/',
            '<!-- wp:my-theme/header-logo /-->',
            $original
        );

        // The old mobile logo is redundant: responsive CSS supplies the mobile artwork.
        $content = preg_replace(
            '/<!--\s+wp:html\s+-->\s*<div class="site-header__mobile-logo">.*?<\/div>\s*<!--\s+\/wp:html\s+-->/s',
            '',
            (string) $content
        );

        $content = preg_replace(
            '/<!--\s+wp:html\s+-->\s*<a\s+href="\/my-account".*?<\/a>\s*<!--\s+\/wp:html\s+-->/s',
            '<!-- wp:my-theme/account-link /-->',
            (string) $content
        );

        if (! is_string($content) || $content === $original) {
            continue;
        }

        if ('' === (string) get_post_meta($header_part->ID, '_my_theme_header_blocks_backup', true)) {
            add_post_meta($header_part->ID, '_my_theme_header_blocks_backup', $original, true);
        }

        wp_update_post(['ID' => $header_part->ID, 'post_content' => $content]);
    }
}
add_action('admin_init', 'my_theme_migrate_legacy_header_items_to_blocks', 5);

/**
 * Insert the independent divider after the fifth top-level navigation item.
 *
 * @param array $blocks Parsed blocks.
 * @param bool  $changed Whether content was changed.
 * @return array
 */
function my_theme_add_independent_navigation_divider(array $blocks, &$changed) {
    foreach ($blocks as $block) {
        if ('my-theme/navigation-divider' === ($block['blockName'] ?? '')) {
            return $blocks;
        }
    }

    $navigation_item_names = [
        'core/navigation-link',
        'core/navigation-submenu',
        'core/home-link',
        'core/page-list',
    ];
    $item_count = 0;

    foreach ($blocks as $index => $block) {
        if (in_array($block['blockName'] ?? '', $navigation_item_names, true)) {
            ++$item_count;
        }

        if (5 === $item_count) {
            $divider = parse_blocks('<!-- wp:my-theme/navigation-divider /-->')[0];
            array_splice($blocks, $index + 1, 0, [$divider]);
            $changed = true;
            return $blocks;
        }
    }

    foreach ($blocks as $index => $block) {
        if (empty($block['innerBlocks']) || ! is_array($block['innerBlocks'])) {
            continue;
        }

        $blocks[$index]['innerBlocks'] = my_theme_add_independent_navigation_divider(
            $block['innerBlocks'],
            $changed
        );

        if ($changed) {
            return $blocks;
        }
    }

    return $blocks;
}

/**
 * Collect Navigation entity IDs referenced by a Header template part.
 *
 * @param array $blocks Parsed blocks.
 * @return array
 */
function my_theme_get_header_navigation_refs(array $blocks) {
    $refs = [];

    foreach ($blocks as $block) {
        if ('core/navigation' === ($block['blockName'] ?? '') && ! empty($block['attrs']['ref'])) {
            $refs[] = absint($block['attrs']['ref']);
        }

        if (! empty($block['innerBlocks']) && is_array($block['innerBlocks'])) {
            $refs = array_merge($refs, my_theme_get_header_navigation_refs($block['innerBlocks']));
        }
    }

    return array_values(array_unique(array_filter($refs)));
}

/**
 * Upgrade saved Navigation entities and Header template parts once.
 *
 * The previous visual divider was tied to :nth-child(5). Converting it to a
 * real block makes its position stable when neighbouring links are moved or
 * removed in the Site Editor.
 */
function my_theme_migrate_navigation_divider_to_block() {
    if (! is_admin() || wp_doing_ajax()) {
        return;
    }

    $migration_version = '1';

    if ($migration_version === (string) get_option('my_theme_navigation_divider_migration', '')) {
        return;
    }

    $header_parts = get_posts([
        'name' => 'header',
        'post_type' => 'wp_template_part',
        'post_status' => ['publish', 'draft'],
        'posts_per_page' => -1,
        'suppress_filters' => false,
    ]);
    $header_navigation_refs = [];
    $divider_ready = false;

    foreach ($header_parts as $header_part) {
        if (! $header_part instanceof WP_Post) {
            continue;
        }

        $original = (string) $header_part->post_content;
        if (false !== strpos($original, 'wp:my-theme/navigation-divider')) {
            $divider_ready = true;
        }
        $parsed   = parse_blocks($original);
        $header_navigation_refs = array_merge(
            $header_navigation_refs,
            my_theme_get_header_navigation_refs($parsed)
        );

        $changed  = false;
        $blocks   = my_theme_add_independent_navigation_divider($parsed, $changed);

        if (! $changed) {
            continue;
        }

        $divider_ready = true;

        if ('' === (string) get_post_meta($header_part->ID, '_my_theme_navigation_divider_backup', true)) {
            add_post_meta($header_part->ID, '_my_theme_navigation_divider_backup', $original, true);
        }

        wp_update_post([
            'ID' => $header_part->ID,
            'post_content' => serialize_blocks($blocks),
        ]);
    }

    $header_navigation_refs = array_values(array_unique(array_filter($header_navigation_refs)));

    foreach ($header_navigation_refs as $navigation_id) {
        $navigation_post = get_post($navigation_id);

        if (! $navigation_post instanceof WP_Post || 'wp_navigation' !== $navigation_post->post_type) {
            continue;
        }

        $original = (string) $navigation_post->post_content;
        if (false !== strpos($original, 'wp:my-theme/navigation-divider')) {
            $divider_ready = true;
        }
        $changed  = false;
        $blocks   = my_theme_add_independent_navigation_divider(parse_blocks($original), $changed);

        if (! $changed) {
            continue;
        }

        $divider_ready = true;

        if ('' === (string) get_post_meta($navigation_post->ID, '_my_theme_navigation_divider_backup', true)) {
            add_post_meta($navigation_post->ID, '_my_theme_navigation_divider_backup', $original, true);
        }

        wp_update_post([
            'ID' => $navigation_post->ID,
            'post_content' => serialize_blocks($blocks),
        ]);
    }

    if ($divider_ready) {
        update_option('my_theme_navigation_divider_migration', $migration_version, false);
    }
}
add_action('admin_init', 'my_theme_migrate_navigation_divider_to_block', 20);

/**
 * Register private mega-menu attributes used only by generated Header links.
 */
function my_theme_add_header_mega_menu_attributes($args, $block_type) {
    if ('core/navigation-link' !== $block_type) {
        return $args;
    }

    $args['attributes']['myThemeMegaMenuEnabled'] = ['type' => 'boolean', 'default' => false];
    $args['attributes']['myThemeMegaMenuColumns'] = ['type' => 'number', 'default' => 3];
    $args['attributes']['myThemeMegaMenuRows'] = ['type' => 'number', 'default' => 2];
    $args['attributes']['myThemeMegaMenuCells'] = ['type' => 'array', 'default' => []];

    return $args;
}
add_filter('register_block_type_args', 'my_theme_add_header_mega_menu_attributes', 10, 2);

/**
 * Render a mega-menu panel only for links generated by Header Pages Menu.
 */
function my_theme_render_header_mega_menu_item($block_content, $block) {
    if (
        'core/navigation-link' !== ($block['blockName'] ?? '')
        || empty($block['attrs']['myThemeMegaMenuEnabled'])
    ) {
        return $block_content;
    }

    $columns = max(1, min(6, absint($block['attrs']['myThemeMegaMenuColumns'] ?? 3)));
    $rows = max(1, min(6, absint($block['attrs']['myThemeMegaMenuRows'] ?? 2)));
    $cell_count = $columns * $rows;
    $saved_cells = isset($block['attrs']['myThemeMegaMenuCells']) && is_array($block['attrs']['myThemeMegaMenuCells'])
        ? $block['attrs']['myThemeMegaMenuCells']
        : [];
    $cells_html = '';

    for ($index = 0; $index < $cell_count; ++$index) {
        $cell = isset($saved_cells[$index]) && is_array($saved_cells[$index])
            ? $saved_cells[$index]
            : [];
        $title = sanitize_text_field((string) ($cell['title'] ?? ''));
        $description = sanitize_text_field((string) ($cell['description'] ?? ''));
        $url = esc_url((string) ($cell['url'] ?? ''));
        $content = '';

        if ('' !== $title) {
            $content .= '<span class="theme-item-mega-menu__title">' . esc_html($title) . '</span>';
        }
        if ('' !== $description) {
            $content .= '<span class="theme-item-mega-menu__description">' . esc_html($description) . '</span>';
        }
        if ('' !== $url && '' !== $content) {
            $content = '<a class="theme-item-mega-menu__link" href="' . $url . '">' . $content . '</a>';
        }

        $cells_html .= '<div class="theme-item-mega-menu__cell">' . $content . '</div>';
    }

    $panel_width = ($columns * 220) + 64;
    $panel_html = sprintf(
        '<div class="theme-item-mega-menu" style="--theme-mega-columns:%1$d;--theme-mega-rows:%2$d;--theme-mega-width:%4$dpx" aria-hidden="true"><div class="theme-item-mega-menu__grid">%3$s</div></div>',
        $columns,
        $rows,
        $cells_html,
        $panel_width
    );

    if (class_exists('WP_HTML_Tag_Processor')) {
        $processor = new WP_HTML_Tag_Processor($block_content);
        if ($processor->next_tag(['class_name' => 'wp-block-navigation-item'])) {
            $processor->add_class('has-custom-mega-menu');
            $block_content = $processor->get_updated_html();
        }
    } else {
        $block_content = preg_replace(
            '/class="([^"]*wp-block-navigation-item[^"]*)"/',
            'class="$1 has-custom-mega-menu"',
            $block_content,
            1
        );
    }

    return preg_replace('/<\/li>\s*$/', $panel_html . '</li>', $block_content, 1);
}
add_filter('render_block_core/navigation-link', 'my_theme_render_header_mega_menu_item', 10, 2);

/**
 * Keep the divider inside one navigation list on the front end.
 *
 * Core Navigation renders links before and after a non-link inner block as
 * two separate UL elements. That makes the links after our divider start on a
 * second row. Merge that boundary into one list and render the divider as a
 * list item. The Site Editor structure remains unchanged.
 *
 * @param string $block_content Rendered block markup.
 * @param array  $block         Parsed block data.
 * @return string
 */
function my_theme_merge_header_navigation_lists($block_content, $block = []) {
    if (
        ! empty($block)
        && 'core/navigation' !== ($block['blockName'] ?? '')
    ) {
        return $block_content;
    }

    if (
        false === strpos($block_content, 'theme-navigation-divider')
        || false === strpos($block_content, 'wp-block-navigation__container')
    ) {
        return $block_content;
    }

    /*
     * Depending on the WordPress/Gutenberg version, a dynamic child block can
     * be rendered as either a SPAN or a DIV and block comments may remain
     * between the generated elements. Accept all of those forms so the
     * Navigation block can never leave two sibling lists around the divider.
     */
    $gap = '(?:\\s|<!--.*?-->)*';
    $pattern = '#</ul>'
        . $gap
        . '<(?P<divider_tag>span|div)\\b(?=[^>]*\\btheme-navigation-divider\\b)[^>]*>'
        . $gap
        . '</(?P=divider_tag)>'
        . $gap
        . '<ul\\b[^>]*\\bwp-block-navigation__container\\b[^>]*>#is';
    $divider = '<li class="wp-block-navigation-item wp-block-my-theme-navigation-divider theme-navigation-divider" aria-hidden="true"></li>';

    return (string) preg_replace($pattern, $divider, $block_content);
}
add_filter('render_block', 'my_theme_merge_header_navigation_lists', 9999, 2);
add_filter('render_block_core/navigation', 'my_theme_merge_header_navigation_lists', 9999, 2);
