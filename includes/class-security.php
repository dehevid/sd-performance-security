<?php
/**
 * Security Class
 *
 * @package SD_Performance_Security
 */

namespace SD_Performance_Security;

if (!defined('ABSPATH')) {
    exit;
}

class Security {

    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks based on settings
     */
    private function init_hooks(): void {
        // XML-RPC
        if (is_enabled('security', 'disable_xmlrpc')) {
            add_filter('xmlrpc_enabled', '__return_false');
            add_filter('wp_headers', [$this, 'remove_xmlrpc_headers']);
        }

        // Security headers
        if (is_enabled('security', 'security_headers')) {
            add_action('send_headers', [$this, 'add_security_headers']);
        }

        // HSTS
        if (is_enabled('security', 'hsts_header')) {
            add_action('send_headers', [$this, 'add_hsts_header']);
        }

        // Permissions Policy
        if (is_enabled('security', 'permissions_policy')) {
            add_action('send_headers', [$this, 'add_permissions_policy']);
        }

        // Hide WP version
        if (is_enabled('security', 'hide_wp_version')) {
            remove_action('wp_head', 'wp_generator');
            add_filter('the_generator', '__return_empty_string');
            add_filter('style_loader_src', [$this, 'remove_version_from_assets'], 10);
            add_filter('script_loader_src', [$this, 'remove_version_from_assets'], 10);
        }

        // Generic login errors
        if (is_enabled('security', 'generic_login_errors')) {
            add_filter('login_errors', [$this, 'generic_login_error']);
        }

        // Block user enumeration
        if (is_enabled('security', 'block_user_enumeration')) {
            add_action('template_redirect', [$this, 'block_author_archives']);
            add_filter('rest_endpoints', [$this, 'block_users_rest_endpoint']);
            add_filter('redirect_canonical', [$this, 'block_author_query'], 10, 2);
        }

        // Restrict REST API
        if (is_enabled('security', 'restrict_rest_api')) {
            add_filter('rest_authentication_errors', [$this, 'restrict_rest_api']);
        }
    }

    /**
     * Remove X-Pingback header
     */
    public function remove_xmlrpc_headers(array $headers): array {
        unset($headers['X-Pingback']);
        return $headers;
    }

    /**
     * Add security headers
     */
    public function add_security_headers(): void {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    /**
     * Add HSTS header
     */
    public function add_hsts_header(): void {
        if (headers_sent() || !is_ssl()) {
            return;
        }

        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }

    /**
     * Add Permissions Policy header
     */
    public function add_permissions_policy(): void {
        if (headers_sent()) {
            return;
        }

        header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), usb=(), magnetometer=(), gyroscope=(), accelerometer=()');
    }

    /**
     * Remove version from assets (additional hiding)
     */
    public function remove_version_from_assets(string $src): string {
        // Only remove WordPress version, not plugin versions
        global $wp_version;

        if (strpos($src, "ver={$wp_version}") !== false) {
            $src = remove_query_arg('ver', $src);
        }

        return $src;
    }

    /**
     * Generic login error message
     */
    public function generic_login_error(): string {
        return 'Invalid credentials.';
    }

    /**
     * Block author archives redirect
     */
    public function block_author_archives(): void {
        if (is_author()) {
            wp_safe_redirect(home_url(), 301);
            exit;
        }
    }

    /**
     * Block ?author=1 query
     */
    public function block_author_query(?string $redirect_url, string $requested_url): ?string {
        if (preg_match('/\?author=\d+/i', $requested_url)) {
            return home_url();
        }
        return $redirect_url;
    }

    /**
     * Block /wp-json/wp/v2/users endpoint for non-logged-in users
     */
    public function block_users_rest_endpoint(array $endpoints): array {
        if (!is_user_logged_in()) {
            unset($endpoints['/wp/v2/users']);
            unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
        }
        return $endpoints;
    }

    /**
     * Restrict REST API to logged-in users only
     */
    public function restrict_rest_api(\WP_Error|null|true $result): \WP_Error|null|true {
        if ($result !== null) {
            return $result;
        }

        if (!is_user_logged_in()) {
            return new \WP_Error(
                'rest_not_logged_in',
                'REST API access restricted.',
                ['status' => 401]
            );
        }

        return $result;
    }
}
