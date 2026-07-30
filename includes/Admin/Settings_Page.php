<?php
namespace DynamicCTA\Admin;

use DynamicCTA\CTA_Resolver;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Settings_Page
 * Renders and handles Plugin Settings Page.
 */
class Settings_Page {

    /**
     * Initialize Settings hooks
     */
    public static function init_hooks(): void {
        add_action('admin_init', [self::class, 'register_settings']);
        add_action('admin_post_dynamic_cta_clear_cache', [self::class, 'handle_clear_cache']);
    }

    /**
     * Register WP Settings API options
     */
    public static function register_settings(): void {
        register_setting('dynamic_cta_settings_group', 'dynamic_cta_default_url', [
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default'           => 'https://jasawifi.com/iconnet/',
        ]);

        register_setting('dynamic_cta_settings_group', 'dynamic_cta_open_link', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '_self',
        ]);

        register_setting('dynamic_cta_settings_group', 'dynamic_cta_enable_cache', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'yes',
        ]);

        register_setting('dynamic_cta_settings_group', 'dynamic_cta_cache_lifetime', [
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 12,
        ]);
    }

    /**
     * Clear Cache Action Handler
     */
    public static function handle_clear_cache(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized permission.', 'dynamic-cta-elementor'));
        }

        check_admin_referer('dynamic_cta_clear_cache_nonce');

        CTA_Resolver::clear_cache();

        wp_redirect(add_query_arg([
            'page'    => 'dynamic-cta-settings',
            'cleared' => '1',
        ], admin_url('admin.php')));
        exit;
    }

    /**
     * Render Settings Form
     */
    public static function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'dynamic-cta-elementor'));
        }

        $default_url     = get_option('dynamic_cta_default_url', 'https://jasawifi.com/iconnet/');
        $open_link       = get_option('dynamic_cta_open_link', '_self');
        $enable_cache     = get_option('dynamic_cta_enable_cache', 'yes');
        $cache_lifetime = get_option('dynamic_cta_cache_lifetime', 12);
        ?>
        <div class="wrap dynamic-cta-wrap">
            <h1>
                <span class="dashicons dashicons-admin-settings"></span>
                <?php esc_html_e('Dynamic CTA Settings', 'dynamic-cta-elementor'); ?>
            </h1>

            <hr class="wp-header-end">

            <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated']): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e('Settings saved successfully.', 'dynamic-cta-elementor'); ?></p>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['cleared']) && $_GET['cleared'] === '1'): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e('Transients cache cleared successfully!', 'dynamic-cta-elementor'); ?></p>
                </div>
            <?php endif; ?>

            <div class="dcta-settings-grid">
                <div class="dcta-settings-card">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('dynamic_cta_settings_group');
                        do_settings_sections('dynamic_cta_settings_group');
                        ?>

                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row">
                                    <label for="dynamic_cta_default_url"><?php esc_html_e('Default URL', 'dynamic-cta-elementor'); ?></label>
                                </th>
                                <td>
                                    <input type="url" name="dynamic_cta_default_url" id="dynamic_cta_default_url" value="<?php echo esc_attr($default_url); ?>" class="large-text" required>
                                    <p class="description">
                                        <?php esc_html_e('Fallback URL to use when no area keyword matches post slug, category, or tag.', 'dynamic-cta-elementor'); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="dynamic_cta_open_link"><?php esc_html_e('Open Link Behavior', 'dynamic-cta-elementor'); ?></label>
                                </th>
                                <td>
                                    <select name="dynamic_cta_open_link" id="dynamic_cta_open_link">
                                        <option value="_self" <?php selected($open_link, '_self'); ?>><?php esc_html_e('Same Tab (_self)', 'dynamic-cta-elementor'); ?></option>
                                        <option value="_blank" <?php selected($open_link, '_blank'); ?>><?php esc_html_e('New Tab (_blank)', 'dynamic-cta-elementor'); ?></option>
                                    </select>
                                    <p class="description">
                                        <?php esc_html_e('Specify whether CTA links open in the same tab or a new tab.', 'dynamic-cta-elementor'); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="dynamic_cta_enable_cache"><?php esc_html_e('Enable Cache', 'dynamic-cta-elementor'); ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input type="radio" name="dynamic_cta_enable_cache" value="yes" <?php checked($enable_cache, 'yes'); ?>>
                                        <?php esc_html_e('Yes (Recommended for maximum performance)', 'dynamic-cta-elementor'); ?>
                                    </label>
                                    <br>
                                    <label>
                                        <input type="radio" name="dynamic_cta_enable_cache" value="no" <?php checked($enable_cache, 'no'); ?>>
                                        <?php esc_html_e('No (Disable Transients caching)', 'dynamic-cta-elementor'); ?>
                                    </label>
                                    <p class="description">
                                        <?php esc_html_e('Uses WordPress Transient API to prevent database queries on every page request.', 'dynamic-cta-elementor'); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="dynamic_cta_cache_lifetime"><?php esc_html_e('Cache Lifetime (Hours)', 'dynamic-cta-elementor'); ?></label>
                                </th>
                                <td>
                                    <input type="number" name="dynamic_cta_cache_lifetime" id="dynamic_cta_cache_lifetime" value="<?php echo esc_attr($cache_lifetime); ?>" min="1" max="168" class="small-text">
                                    <span><?php esc_html_e('hours', 'dynamic-cta-elementor'); ?></span>
                                    <p class="description">
                                        <?php esc_html_e('Default: 12 hours. Cache is automatically invalidated whenever Area Mapping table is updated.', 'dynamic-cta-elementor'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <?php submit_button(__('Save Settings', 'dynamic-cta-elementor')); ?>
                    </form>
                </div>

                <div class="dcta-settings-card dcta-cache-card">
                    <h3><span class="dashicons dashicons-trash"></span> <?php esc_html_e('Cache Management', 'dynamic-cta-elementor'); ?></h3>
                    <p><?php esc_html_e('If you manually updated database rows or want to flush cached resolved URLs immediately, click below.', 'dynamic-cta-elementor'); ?></p>
                    
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="dynamic_cta_clear_cache">
                        <?php wp_nonce_field('dynamic_cta_clear_cache_nonce'); ?>
                        <button type="submit" class="button button-secondary">
                            <span class="dashicons dashicons-dismiss"></span> <?php esc_html_e('Clear Transients Cache Now', 'dynamic-cta-elementor'); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
}
