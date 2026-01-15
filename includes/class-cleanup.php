<?php
/**
 * Cleanup Class
 *
 * @package SD_Performance_Security
 */

namespace SD_Performance_Security;

if (!defined('ABSPATH')) {
    exit;
}

class Cleanup {

    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks based on settings
     */
    private function init_hooks(): void {
        // RSD Link
        if (is_enabled('cleanup', 'remove_rsd_link')) {
            remove_action('wp_head', 'rsd_link');
        }

        // WLW Manifest
        if (is_enabled('cleanup', 'remove_wlw_manifest')) {
            remove_action('wp_head', 'wlwmanifest_link');
        }

        // Shortlink
        if (is_enabled('cleanup', 'remove_shortlink')) {
            remove_action('wp_head', 'wp_shortlink_wp_head');
            remove_action('template_redirect', 'wp_shortlink_header', 11);
        }

        // REST API Links
        if (is_enabled('cleanup', 'remove_rest_api_links')) {
            remove_action('wp_head', 'rest_output_link_wp_head');
            remove_action('template_redirect', 'rest_output_link_header', 11);
        }

        // Feed Links
        if (is_enabled('cleanup', 'remove_feed_links')) {
            add_action('after_setup_theme', [$this, 'remove_feed_links']);
        }

        // Adjacent Posts
        if (is_enabled('cleanup', 'remove_adjacent_posts')) {
            remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10);
        }

        // DNS Prefetch
        if (is_enabled('cleanup', 'remove_dns_prefetch')) {
            remove_action('wp_head', 'wp_resource_hints', 2);
        }

        // Comments
        if (is_enabled('cleanup', 'disable_comments')) {
            $this->disable_comments();
        }
    }

    /**
     * Remove feed links
     */
    public function remove_feed_links(): void {
        remove_action('wp_head', 'feed_links', 2);
        remove_action('wp_head', 'feed_links_extra', 3);
    }

    /**
     * Completely disable comments
     */
    private function disable_comments(): void {
        // Close comments on all post types
        add_filter('comments_open', '__return_false', 20);
        add_filter('pings_open', '__return_false', 20);

        // Hide existing comments
        add_filter('comments_array', '__return_empty_array', 10);

        // Remove from admin bar
        add_action('admin_bar_menu', [$this, 'remove_comments_admin_bar'], 999);

        // Remove meta boxes
        add_action('admin_menu', [$this, 'remove_comments_meta_boxes']);

        // Remove from admin menu
        add_action('admin_menu', [$this, 'remove_comments_admin_menu']);

        // Block comment REST endpoints
        add_filter('rest_endpoints', [$this, 'remove_comment_endpoints']);

        // Disable comment feeds
        add_action('do_feed_rss2_comments', [$this, 'disable_comment_feed'], 1);
        add_action('do_feed_atom_comments', [$this, 'disable_comment_feed'], 1);

        // Remove X-Pingback header
        add_filter('wp_headers', [$this, 'remove_pingback_header']);

        // Redirect comment pages
        add_action('template_redirect', [$this, 'redirect_comment_pages']);
    }

    /**
     * Remove comments from admin bar
     */
    public function remove_comments_admin_bar(\WP_Admin_Bar $wp_admin_bar): void {
        $wp_admin_bar->remove_node('comments');
    }

    /**
     * Remove comments meta boxes
     */
    public function remove_comments_meta_boxes(): void {
        $post_types = get_post_types(['public' => true]);

        foreach ($post_types as $post_type) {
            remove_meta_box('commentsdiv', $post_type, 'normal');
            remove_meta_box('commentstatusdiv', $post_type, 'normal');
            remove_meta_box('trackbacksdiv', $post_type, 'normal');
        }
    }

    /**
     * Remove comments from admin menu
     */
    public function remove_comments_admin_menu(): void {
        remove_menu_page('edit-comments.php');
        remove_submenu_page('options-general.php', 'options-discussion.php');
    }

    /**
     * Remove comment REST endpoints
     */
    public function remove_comment_endpoints(array $endpoints): array {
        foreach ($endpoints as $route => $endpoint) {
            if (strpos($route, '/comments') !== false) {
                unset($endpoints[$route]);
            }
        }
        return $endpoints;
    }

    /**
     * Disable comment feeds
     */
    public function disable_comment_feed(): void {
        wp_safe_redirect(home_url(), 301);
        exit;
    }

    /**
     * Remove X-Pingback header
     */
    public function remove_pingback_header(array $headers): array {
        unset($headers['X-Pingback']);
        return $headers;
    }

    /**
     * Redirect comment pages to home
     */
    public function redirect_comment_pages(): void {
        if (is_comment_feed()) {
            wp_safe_redirect(home_url(), 301);
            exit;
        }
    }
}
