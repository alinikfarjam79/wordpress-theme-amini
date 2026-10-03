<?php
/**
 * Repairs legacy Footer template-part markup that Gutenberg marks invalid.
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Keep legacy database-backed Footer parts compatible with current core blocks.
 *
 * The replacements are deliberately narrow so user-edited content and links
 * remain untouched. A one-time backup is stored before the first update.
 */
function my_theme_repair_saved_footer_block_markup() {
    if (! is_admin() || wp_doing_ajax()) {
        return;
    }

    $footer_parts = get_posts(
        [
            'name'             => 'footer',
            'post_type'        => 'wp_template_part',
            'post_status'      => ['publish', 'draft'],
            'posts_per_page'   => -1,
            'suppress_filters' => false,
        ]
    );

    foreach ($footer_parts as $footer_part) {
        if (! $footer_part instanceof WP_Post) {
            continue;
        }

        $original = (string) $footer_part->post_content;
        $content  = str_replace(
            '<!-- wp:group {"className":"footer-main","layout":{"type":"constrained"}} -->',
            '<!-- wp:group {"className":"footer-main","style":{"spacing":{"margin":{"top":"30px"}}},"layout":{"type":"constrained"}} -->',
            $original
        );

        $content = preg_replace(
            '/<div class="wp-block-group footer-main" style="margin-top:\s*30px;?">/',
            '<div class="wp-block-group footer-main" style="margin-top:30px">',
            (string) $content
        );

        $content = preg_replace(
            '/(<h4)(?![^>]*\bwp-block-heading\b)([^>]*>)/',
            '$1 class="wp-block-heading"$2',
            (string) $content
        );

        $content = preg_replace(
            '/<!--\s+wp:list\s+-->\s*<ul(?![^>]*\bwp-block-list\b)([^>]*)>/',
            '<!-- wp:list -->' . "\n" . '<ul class="wp-block-list"$1>',
            (string) $content
        );

        /*
         * Footer link columns used to be plain List blocks. Convert them to
         * Navigation blocks so every new item gets WordPress' native URL/page
         * picker while preserving existing labels and href values.
         */
        $content = preg_replace_callback(
            '/<!--\s+wp:list(?:\s+\{.*?\})?\s+-->\s*<ul[^>]*>(.*?)<\/ul>\s*<!--\s+\/wp:list\s+-->/is',
            'my_theme_convert_footer_list_to_navigation',
            (string) $content
        );

        /*
         * Plain descriptive comments between Gutenberg block delimiters are
         * parsed as loose HTML and make the parent Group fail validation in
         * the Site Editor. They are authoring notes only, so removing them
         * does not alter content or presentation.
         */
        $content = preg_replace(
            '/^[\t ]*<!--\s*(?:NEWSLETTER BOX|FOOTER MAIN|BRAND COLUMN|CONTACT COLUMN|PRODUCTS COLUMN|FAQ COLUMN|COPYRIGHT)\s*-->[\t ]*\R?/mi',
            '',
            (string) $content
        );

        if (! is_string($content) || $content === $original) {
            continue;
        }

        if ('' === (string) get_post_meta($footer_part->ID, '_my_theme_footer_blocks_backup', true)) {
            add_post_meta($footer_part->ID, '_my_theme_footer_blocks_backup', $original, true);
        }

        wp_update_post(
            [
                'ID'           => $footer_part->ID,
                'post_content' => $content,
            ]
        );
    }
}
add_action('admin_init', 'my_theme_repair_saved_footer_block_markup', 5);

/**
 * Give every saved Footer navigation its own wp_navigation entity.
 *
 * Gutenberg can attach an unassigned Navigation block to an existing menu.
 * When that happens in the Footer, editing it also edits the Header menu.
 * Cloning each Footer navigation once keeps its current items while breaking
 * that shared reference permanently.
 */
function my_theme_detach_footer_navigation_blocks(array $blocks, &$changed, $footer_part_id) {
    static $navigation_index = 0;

    foreach ($blocks as $index => $block) {
        if ('core/navigation' === ($block['blockName'] ?? '')) {
            $navigation_index++;
            $navigation_content = '';
            $navigation_ref     = isset($block['attrs']['ref']) ? absint($block['attrs']['ref']) : 0;

            if ($navigation_ref) {
                $navigation_post = get_post($navigation_ref);

                if ($navigation_post instanceof WP_Post && 'wp_navigation' === $navigation_post->post_type) {
                    $navigation_content = (string) $navigation_post->post_content;
                }
            }

            if ('' === trim($navigation_content) && ! empty($block['innerBlocks'])) {
                $navigation_content = serialize_blocks($block['innerBlocks']);
            }

            if ('' !== trim($navigation_content)) {
                $new_navigation_id = wp_insert_post(
                    [
                        'post_type'    => 'wp_navigation',
                        'post_status'  => 'publish',
                        'post_title'   => sprintf(
                            'Footer Navigation %d-%d',
                            absint($footer_part_id),
                            $navigation_index
                        ),
                        'post_content' => $navigation_content,
                    ],
                    true
                );

                if (! is_wp_error($new_navigation_id) && $new_navigation_id) {
                    add_post_meta($new_navigation_id, '_my_theme_footer_navigation_owner', absint($footer_part_id), true);
                    $blocks[$index]['attrs']['ref'] = absint($new_navigation_id);
                    $blocks[$index]['innerBlocks']  = [];
                    $blocks[$index]['innerHTML']    = '';
                    $blocks[$index]['innerContent'] = [];
                    $changed = true;
                }
            }
        }

        if (! empty($blocks[$index]['innerBlocks'])) {
            $blocks[$index]['innerBlocks'] = my_theme_detach_footer_navigation_blocks(
                $blocks[$index]['innerBlocks'],
                $changed,
                $footer_part_id
            );
        }
    }

    return $blocks;
}

/**
 * One-time migration that separates Footer menus from the Header menu.
 */
function my_theme_migrate_footer_navigation_ownership() {
    $migration_version = '2';

    if ($migration_version === (string) get_option('my_theme_footer_navigation_ownership_migration', '')) {
        return;
    }

    $footer_parts = get_posts(
        [
            'name'             => 'footer',
            'post_type'        => 'wp_template_part',
            'post_status'      => ['publish', 'draft'],
            'posts_per_page'   => -1,
            'suppress_filters' => false,
        ]
    );

    foreach ($footer_parts as $footer_part) {
        if (! $footer_part instanceof WP_Post) {
            continue;
        }

        $original = (string) $footer_part->post_content;
        $changed  = false;
        $blocks   = my_theme_detach_footer_navigation_blocks(
            parse_blocks($original),
            $changed,
            $footer_part->ID
        );

        if (! $changed) {
            continue;
        }

        if ('' === (string) get_post_meta($footer_part->ID, '_my_theme_footer_navigation_ownership_backup', true)) {
            add_post_meta($footer_part->ID, '_my_theme_footer_navigation_ownership_backup', $original, true);
        }

        wp_update_post(
            [
                'ID'           => $footer_part->ID,
                'post_content' => serialize_blocks($blocks),
            ]
        );
    }

    update_option('my_theme_footer_navigation_ownership_migration', $migration_version, false);
}
add_action('admin_init', 'my_theme_migrate_footer_navigation_ownership', 10);

/**
 * Convert one legacy footer List block into an editable Navigation block.
 *
 * @param array $matches Regex matches containing the list contents.
 * @return string
 */
function my_theme_convert_footer_list_to_navigation($matches) {
    $list_html = isset($matches[1]) ? (string) $matches[1] : '';

    if ('' === trim($list_html)) {
        return (string) $matches[0];
    }

    preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $list_html, $items);

    if (empty($items[1])) {
        return (string) $matches[0];
    }

    $navigation = '<!-- wp:navigation {"overlayMenu":"never","className":"footer-links__navigation","layout":{"type":"flex","orientation":"vertical"}} -->' . "\n";

    foreach ($items[1] as $item_html) {
        $url   = '#';
        $label = wp_strip_all_tags((string) $item_html);

        if (preg_match('/<a\b[^>]*\bhref=(["\'])(.*?)\1[^>]*>(.*?)<\/a>/is', (string) $item_html, $link)) {
            $url   = html_entity_decode((string) $link[2], ENT_QUOTES, get_bloginfo('charset'));
            $label = wp_strip_all_tags((string) $link[3]);
        }

        $label = trim($label);

        if ('' === $label) {
            continue;
        }

        $attributes = [
            'label' => $label,
            'url'   => $url,
            'kind'  => 'custom',
        ];

        $navigation .= '<!-- wp:navigation-link ' . wp_json_encode($attributes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ' /-->' . "\n";
    }

    $navigation .= '<!-- /wp:navigation -->';

    return $navigation;
}
