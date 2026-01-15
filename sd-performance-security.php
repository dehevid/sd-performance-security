<?php
/**
 * Plugin Name: SD Performance & Security
 * Plugin URI: https://github.com/sonderr/sd-performance-security
 * Description: Lightweight performance and security hardening for WordPress. Removes bloat, adds security headers, and optimizes loading.
 * Version: 1.0.0
 * Author: Sonderr
 * Author URI: https://sonderr.de
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.0
 * Requires PHP: 8.3
 */

namespace SD_Performance_Security;

if (!defined('ABSPATH')) {
    exit;
}

define('SDPS_VERSION', '1.0.0');
define('SDPS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SDPS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SDPS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Get default options
 */
function get_default_options(): array {
    return [
        'performance' => [
            'disable_emojis' => true,
            'disable_embeds' => true,
            'remove_jquery_migrate' => true,
            'disable_heartbeat_frontend' => false,
            'heartbeat_dashboard_interval' => 60,
            'heartbeat_editor_interval' => 60,
            'remove_dashicons_frontend' => true,
            'remove_query_strings' => true,
            'defer_scripts' => false,
            'remove_duotone_svg' => true,
        ],
        'security' => [
            'disable_xmlrpc' => true,
            'security_headers' => true,
            'hsts_header' => false,
            'permissions_policy' => true,
            'hide_wp_version' => true,
            'generic_login_errors' => true,
            'block_user_enumeration' => true,
            'restrict_rest_api' => false,
        ],
        'cleanup' => [
            'remove_rsd_link' => true,
            'remove_wlw_manifest' => true,
            'remove_shortlink' => true,
            'remove_rest_api_links' => true,
            'remove_feed_links' => true,
            'remove_adjacent_posts' => true,
            'remove_dns_prefetch' => true,
            'disable_comments' => false,
        ],
    ];
}

/**
 * Get plugin options with defaults
 */
function get_options(): array {
    $defaults = get_default_options();
    $options = get_option('sdps_options', []);

    return array_replace_recursive($defaults, $options);
}

/**
 * Check if a specific option is enabled
 */
function is_enabled(string $section, string $key): bool {
    $options = get_options();
    return !empty($options[$section][$key]);
}

/**
 * Get a specific option value
 */
function get_option_value(string $section, string $key, mixed $default = null): mixed {
    $options = get_options();
    return $options[$section][$key] ?? $default;
}

// Load classes
require_once SDPS_PLUGIN_DIR . 'includes/class-settings.php';
require_once SDPS_PLUGIN_DIR . 'includes/class-performance.php';
require_once SDPS_PLUGIN_DIR . 'includes/class-security.php';
require_once SDPS_PLUGIN_DIR . 'includes/class-cleanup.php';

/**
 * Initialize plugin
 */
function init(): void {
    // Initialize settings (always load in admin)
    if (is_admin()) {
        new Settings();
    }

    // Initialize modules
    new Performance();
    new Security();
    new Cleanup();
}
add_action('plugins_loaded', __NAMESPACE__ . '\init');

/**
 * Activation hook - set default options
 */
function activate(): void {
    if (!get_option('sdps_options')) {
        update_option('sdps_options', get_default_options());
    }
}
register_activation_hook(__FILE__, __NAMESPACE__ . '\activate');

/**
 * Add settings link on plugins page
 */
function add_settings_link(array $links): array {
    $settings_link = '<a href="' . admin_url('options-general.php?page=sd-performance-security') . '">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . SDPS_PLUGIN_BASENAME, __NAMESPACE__ . '\add_settings_link');
