<?php
/**
 * Settings Class
 *
 * @package SD_Performance_Security
 */

namespace SD_Performance_Security;

if (!defined('ABSPATH')) {
    exit;
}

class Settings {

    private array $tooltips;

    public function __construct() {
        $this->tooltips = $this->get_tooltips();

        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
    }

    /**
     * Get tooltips for all settings
     */
    private function get_tooltips(): array {
        return [
            'performance' => [
                'disable_emojis' => 'Removes WordPress emoji scripts and styles (~15kb). Modern browsers support emojis natively.',
                'disable_embeds' => 'Removes oEmbed JavaScript for embedding posts. Saves HTTP requests if you don\'t embed WP posts.',
                'remove_jquery_migrate' => 'Removes backwards-compatibility script for old jQuery plugins. Only affects frontend.',
                'disable_heartbeat_frontend' => 'Stops background AJAX requests on frontend. Reduces server load on high-traffic sites.',
                'heartbeat_dashboard_interval' => 'Reduces dashboard heartbeat frequency from 15s to 60s. Lowers admin-ajax.php load.',
                'heartbeat_editor_interval' => 'Reduces editor heartbeat from 15s to 60s. Autosave still works, just less frequently.',
                'remove_dashicons_frontend' => 'Removes Dashicons CSS for non-logged-in users. Saves ~46kb if you don\'t use them.',
                'remove_query_strings' => 'Removes ?ver= from CSS/JS URLs. Improves caching on some CDNs.',
                'defer_scripts' => 'Adds defer attribute to scripts. Can break inline scripts - test thoroughly.',
                'remove_duotone_svg' => 'Removes inline SVG filters for block editor duotone feature. Saves ~10kb.',
            ],
            'security' => [
                'disable_xmlrpc' => 'Disables remote publishing API. Reduces attack surface for brute-force attacks.',
                'security_headers' => 'Adds X-Content-Type-Options, X-Frame-Options, Referrer-Policy headers.',
                'hsts_header' => 'Forces HTTPS for 1 year. Only enable if SSL is permanently configured!',
                'permissions_policy' => 'Restricts browser features (camera, microphone, geolocation) for security.',
                'hide_wp_version' => 'Removes WordPress version from HTML source. Minor security improvement.',
                'generic_login_errors' => 'Shows generic error on failed login. Prevents username enumeration.',
                'block_user_enumeration' => 'Blocks /?author=1 redirects and /wp-json/wp/v2/users endpoint.',
                'restrict_rest_api' => 'Limits REST API to logged-in users only. May break some plugins/themes.',
            ],
            'cleanup' => [
                'remove_rsd_link' => 'Removes Really Simple Discovery link. Only needed for XML-RPC clients.',
                'remove_wlw_manifest' => 'Removes Windows Live Writer manifest. Obsolete since WLW is discontinued.',
                'remove_shortlink' => 'Removes ?p=123 shortlink from head. Saves a few bytes.',
                'remove_rest_api_links' => 'Removes REST API discovery links from HTML head and HTTP headers.',
                'remove_feed_links' => 'Removes RSS/Atom feed links from head. Enable if you don\'t use feeds.',
                'remove_adjacent_posts' => 'Removes prev/next post links from head. Saves 2 database queries.',
                'remove_dns_prefetch' => 'Removes s.w.org DNS prefetch. Not needed if emojis are disabled.',
                'disable_comments' => 'Completely disables comments, pings, and comment REST endpoints.',
            ],
        ];
    }

    /**
     * Add admin menu page
     */
    public function add_menu_page(): void {
        add_options_page(
            'SD Performance & Security',
            'SD Performance & Security',
            'manage_options',
            'sd-performance-security',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register settings
     */
    public function register_settings(): void {
        register_setting('sdps_options_group', 'sdps_options', [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize_options'],
            'default' => get_default_options(),
        ]);
    }

    /**
     * Sanitize options
     */
    public function sanitize_options(array $input): array {
        $defaults = get_default_options();
        $sanitized = [];

        foreach ($defaults as $section => $options) {
            foreach ($options as $key => $default) {
                if (is_bool($default)) {
                    $sanitized[$section][$key] = !empty($input[$section][$key]);
                } elseif (is_int($default)) {
                    $sanitized[$section][$key] = absint($input[$section][$key] ?? $default);
                } else {
                    $sanitized[$section][$key] = sanitize_text_field($input[$section][$key] ?? $default);
                }
            }
        }

        return $sanitized;
    }

    /**
     * Enqueue admin styles
     */
    public function enqueue_admin_styles(string $hook): void {
        if ($hook !== 'settings_page_sd-performance-security') {
            return;
        }

        wp_add_inline_style('wp-admin', $this->get_admin_css());
    }

    /**
     * Get admin CSS
     */
    private function get_admin_css(): string {
        return '
            .sdps-settings { max-width: 800px; }
            .sdps-section { background: #fff; border: 1px solid #c3c4c7; padding: 20px; margin-bottom: 20px; }
            .sdps-section h2 { margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #c3c4c7; }
            .sdps-option { display: flex; align-items: flex-start; padding: 12px 0; border-bottom: 1px solid #f0f0f1; }
            .sdps-option:last-child { border-bottom: none; }
            .sdps-option-label { flex: 0 0 250px; font-weight: 500; }
            .sdps-option-control { flex: 0 0 60px; }
            .sdps-option-tooltip { flex: 1; color: #646970; font-size: 13px; padding-left: 15px; }
            .sdps-info-box { background: #f0f6fc; border-left: 4px solid #72aee6; padding: 12px 15px; margin-top: 20px; }
            .sdps-info-box h3 { margin: 0 0 10px; }
            .sdps-info-box code { background: #e7e8ea; padding: 2px 6px; }
            .sdps-select { width: 80px; }
        ';
    }

    /**
     * Render settings page
     */
    public function render_settings_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        require_once SDPS_PLUGIN_DIR . 'admin/settings-page.php';
    }

    /**
     * Get tooltip for a setting
     */
    public function get_tooltip(string $section, string $key): string {
        return $this->tooltips[$section][$key] ?? '';
    }

    /**
     * Render a checkbox option
     */
    public function render_checkbox(string $section, string $key, string $label): void {
        $options = get_options();
        $checked = !empty($options[$section][$key]);
        $tooltip = $this->get_tooltip($section, $key);
        $name = "sdps_options[{$section}][{$key}]";

        ?>
        <div class="sdps-option">
            <div class="sdps-option-label">
                <label for="<?php echo esc_attr($name); ?>"><?php echo esc_html($label); ?></label>
            </div>
            <div class="sdps-option-control">
                <input type="checkbox"
                       id="<?php echo esc_attr($name); ?>"
                       name="<?php echo esc_attr($name); ?>"
                       value="1"
                       <?php checked($checked); ?>>
            </div>
            <div class="sdps-option-tooltip">
                <?php echo esc_html($tooltip); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render a select option for intervals
     */
    public function render_interval_select(string $section, string $key, string $label): void {
        $options = get_options();
        $value = $options[$section][$key] ?? 60;
        $tooltip = $this->get_tooltip($section, $key);
        $name = "sdps_options[{$section}][{$key}]";

        $intervals = [
            15 => '15s',
            30 => '30s',
            60 => '60s',
            120 => '120s',
        ];

        ?>
        <div class="sdps-option">
            <div class="sdps-option-label">
                <label for="<?php echo esc_attr($name); ?>"><?php echo esc_html($label); ?></label>
            </div>
            <div class="sdps-option-control">
                <select id="<?php echo esc_attr($name); ?>"
                        name="<?php echo esc_attr($name); ?>"
                        class="sdps-select">
                    <?php foreach ($intervals as $interval => $label_text) : ?>
                        <option value="<?php echo esc_attr($interval); ?>" <?php selected($value, $interval); ?>>
                            <?php echo esc_html($label_text); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sdps-option-tooltip">
                <?php echo esc_html($tooltip); ?>
            </div>
        </div>
        <?php
    }
}
