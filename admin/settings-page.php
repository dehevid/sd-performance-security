<?php
/**
 * Settings Page Template
 *
 * @package SD_Performance_Security
 */

namespace SD_Performance_Security;

if (!defined('ABSPATH')) {
    exit;
}

$settings = new Settings();
?>

<div class="wrap sdps-settings">
    <h1>SD Performance & Security</h1>

    <form method="post" action="options.php">
        <?php settings_fields('sdps_options_group'); ?>

        <!-- Performance Section -->
        <div class="sdps-section">
            <h2>Performance</h2>

            <?php
            $settings->render_checkbox('performance', 'disable_emojis', 'Disable Emojis');
            $settings->render_checkbox('performance', 'disable_embeds', 'Disable Embeds');
            $settings->render_checkbox('performance', 'remove_jquery_migrate', 'Remove jQuery Migrate');
            $settings->render_checkbox('performance', 'disable_heartbeat_frontend', 'Disable Heartbeat (Frontend)');
            $settings->render_interval_select('performance', 'heartbeat_dashboard_interval', 'Heartbeat Dashboard Interval');
            $settings->render_interval_select('performance', 'heartbeat_editor_interval', 'Heartbeat Editor Interval');
            $settings->render_checkbox('performance', 'remove_dashicons_frontend', 'Remove Dashicons (Frontend)');
            $settings->render_checkbox('performance', 'remove_query_strings', 'Remove Query Strings');
            $settings->render_checkbox('performance', 'defer_scripts', 'Defer Scripts');
            $settings->render_checkbox('performance', 'remove_duotone_svg', 'Remove Duotone SVG');
            ?>
        </div>

        <!-- Security Section -->
        <div class="sdps-section">
            <h2>Security</h2>

            <?php
            $settings->render_checkbox('security', 'disable_xmlrpc', 'Disable XML-RPC');
            $settings->render_checkbox('security', 'security_headers', 'Security Headers');
            $settings->render_checkbox('security', 'hsts_header', 'HSTS Header');
            $settings->render_checkbox('security', 'permissions_policy', 'Permissions Policy');
            $settings->render_checkbox('security', 'hide_wp_version', 'Hide WP Version');
            $settings->render_checkbox('security', 'generic_login_errors', 'Generic Login Errors');
            $settings->render_checkbox('security', 'block_user_enumeration', 'Block User Enumeration');
            $settings->render_checkbox('security', 'restrict_rest_api', 'Restrict REST API');
            ?>
        </div>

        <!-- Cleanup Section -->
        <div class="sdps-section">
            <h2>Cleanup</h2>

            <?php
            $settings->render_checkbox('cleanup', 'remove_rsd_link', 'Remove RSD Link');
            $settings->render_checkbox('cleanup', 'remove_wlw_manifest', 'Remove WLW Manifest');
            $settings->render_checkbox('cleanup', 'remove_shortlink', 'Remove Shortlink');
            $settings->render_checkbox('cleanup', 'remove_rest_api_links', 'Remove REST API Links');
            $settings->render_checkbox('cleanup', 'remove_feed_links', 'Remove Feed Links');
            $settings->render_checkbox('cleanup', 'remove_adjacent_posts', 'Remove Adjacent Posts');
            $settings->render_checkbox('cleanup', 'remove_dns_prefetch', 'Remove DNS Prefetch');
            $settings->render_checkbox('cleanup', 'disable_comments', 'Disable Comments');
            ?>
        </div>

        <!-- Info Section -->
        <div class="sdps-section">
            <h2>Additional Recommendations</h2>

            <div class="sdps-info-box">
                <h3>wp-config.php Settings</h3>
                <p>The following settings cannot be configured via plugin and must be added to your <code>wp-config.php</code> file:</p>
                <pre style="background: #f6f7f7; padding: 10px; overflow-x: auto;">
// Limit post revisions (reduces database bloat)
define('WP_POST_REVISIONS', 5);

// Disable file editing in admin (security)
define('DISALLOW_FILE_EDIT', true);

// Disable automatic updates (if desired)
define('AUTOMATIC_UPDATER_DISABLED', true);

// Disable debug mode in production
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);</pre>
            </div>
        </div>

        <?php submit_button('Save Settings'); ?>
    </form>
</div>
