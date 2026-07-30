<?php
/**
 * Dynamic CTA for Elementor Uninstall
 *
 * Fired when the plugin is uninstalled via WordPress Admin.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Drop custom database tables
$mappings_table = $wpdb->prefix . 'dynamic_cta_mappings';
$clicks_table   = $wpdb->prefix . 'dynamic_cta_clicks';

$wpdb->query("DROP TABLE IF EXISTS {$mappings_table}");
$wpdb->query("DROP TABLE IF EXISTS {$clicks_table}");

// Delete options
delete_option('dynamic_cta_default_url');
delete_option('dynamic_cta_open_link');
delete_option('dynamic_cta_enable_cache');
delete_option('dynamic_cta_cache_lifetime');

// Delete transients
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_dcta_%' OR option_name LIKE '_transient_timeout_dcta_%'");
