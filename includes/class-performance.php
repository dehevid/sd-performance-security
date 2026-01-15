<?php
/**
 * Performance Class
 *
 * @package SD_Performance_Security
 */

namespace SD_Performance_Security;

if (!defined('ABSPATH')) {
    exit;
}

class Performance {

    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks based on settings
     */
    private function init_hooks(): void {
        // Emojis
        if (is_enabled('performance', 'disable_emojis')) {
            add_action('init', [$this, 'disable_emojis']);
            add_filter('tiny_mce_plugins', [$this, 'disable_emojis_tinymce']);
            add_filter('wp_resource_hints', [$this, 'remove_emoji_dns_prefetch'], 10, 2);
        }

        // Embeds
        if (is_enabled('performance', 'disable_embeds')) {
            add_action('init', [$this, 'disable_embeds'], 9999);
            add_action('wp_footer', [$this, 'dequeue_embed_script']);
        }

        // jQuery Migrate
        if (is_enabled('performance', 'remove_jquery_migrate')) {
            add_action('wp_default_scripts', [$this, 'remove_jquery_migrate']);
        }

        // Heartbeat
        if (is_enabled('performance', 'disable_heartbeat_frontend')) {
            add_action('init', [$this, 'disable_heartbeat_frontend']);
        }

        // Heartbeat intervals
        add_filter('heartbeat_settings', [$this, 'modify_heartbeat_settings']);

        // Dashicons
        if (is_enabled('performance', 'remove_dashicons_frontend')) {
            add_action('wp_enqueue_scripts', [$this, 'remove_dashicons_frontend']);
        }

        // Query strings
        if (is_enabled('performance', 'remove_query_strings')) {
            add_filter('style_loader_src', [$this, 'remove_query_strings'], 10);
            add_filter('script_loader_src', [$this, 'remove_query_strings'], 10);
        }

        // Defer scripts
        if (is_enabled('performance', 'defer_scripts')) {
            add_filter('script_loader_tag', [$this, 'defer_scripts'], 10, 3);
        }

        // Duotone SVG
        if (is_enabled('performance', 'remove_duotone_svg')) {
            add_action('wp_enqueue_scripts', [$this, 'remove_duotone_svg'], 100);
        }

        // Separate block assets (always enabled)
        add_filter('should_load_separate_core_block_assets', '__return_true');
    }

    /**
     * Disable emoji scripts and styles
     */
    public function disable_emojis(): void {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        add_filter('emoji_svg_url', '__return_false');
    }

    /**
     * Disable emojis in TinyMCE editor
     */
    public function disable_emojis_tinymce(array $plugins): array {
        if (is_array($plugins)) {
            return array_diff($plugins, ['wpemoji']);
        }
        return $plugins;
    }

    /**
     * Remove emoji DNS prefetch
     */
    public function remove_emoji_dns_prefetch(array $urls, string $relation_type): array {
        if ($relation_type === 'dns-prefetch') {
            $emoji_svg_url = apply_filters('emoji_svg_url', 'https://s.w.org/images/core/emoji/');
            $urls = array_diff($urls, [$emoji_svg_url]);
        }
        return $urls;
    }

    /**
     * Disable WordPress embeds
     */
    public function disable_embeds(): void {
        remove_action('rest_api_init', 'wp_oembed_register_route');
        add_filter('embed_oembed_discover', '__return_false');
        remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
        remove_action('wp_head', 'wp_oembed_add_host_js');
    }

    /**
     * Dequeue embed script
     */
    public function dequeue_embed_script(): void {
        wp_dequeue_script('wp-embed');
    }

    /**
     * Remove jQuery Migrate
     */
    public function remove_jquery_migrate(\WP_Scripts $scripts): void {
        if (!is_admin() && isset($scripts->registered['jquery'])) {
            $script = $scripts->registered['jquery'];
            if ($script->deps) {
                $script->deps = array_diff($script->deps, ['jquery-migrate']);
            }
        }
    }

    /**
     * Disable heartbeat on frontend
     */
    public function disable_heartbeat_frontend(): void {
        if (!is_admin()) {
            wp_deregister_script('heartbeat');
        }
    }

    /**
     * Modify heartbeat settings
     */
    public function modify_heartbeat_settings(array $settings): array {
        global $pagenow;

        // Dashboard interval
        if ($pagenow === 'index.php') {
            $settings['interval'] = get_option_value('performance', 'heartbeat_dashboard_interval', 60);
        }

        // Post editor interval
        if ($pagenow === 'post.php' || $pagenow === 'post-new.php') {
            $settings['interval'] = get_option_value('performance', 'heartbeat_editor_interval', 60);
        }

        return $settings;
    }

    /**
     * Remove Dashicons on frontend for non-logged-in users
     */
    public function remove_dashicons_frontend(): void {
        if (!is_user_logged_in()) {
            wp_dequeue_style('dashicons');
            wp_deregister_style('dashicons');
        }
    }

    /**
     * Remove query strings from static resources
     */
    public function remove_query_strings(string $src): string {
        if (strpos($src, '?ver=') !== false) {
            $src = remove_query_arg('ver', $src);
        }
        return $src;
    }

    /**
     * Defer non-critical JavaScript
     */
    public function defer_scripts(string $tag, string $handle, string $src): string {
        // Don't defer in admin
        if (is_admin()) {
            return $tag;
        }

        // Scripts that should NOT be deferred
        $exclude = ['jquery', 'jquery-core'];

        if (in_array($handle, $exclude, true)) {
            return $tag;
        }

        // Skip if already has defer or async
        if (strpos($tag, 'defer') !== false || strpos($tag, 'async') !== false) {
            return $tag;
        }

        return str_replace(' src=', ' defer src=', $tag);
    }

    /**
     * Remove duotone SVG filters
     */
    public function remove_duotone_svg(): void {
        remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
        remove_action('in_admin_header', 'wp_global_styles_render_svg_filters');
    }
}
