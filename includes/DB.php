<?php
namespace DynamicCTA;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class DB
 * Handles Database initialization, schema upgrades, and table operations.
 */
class DB {

    /**
     * Get Table Name for Area Mappings
     *
     * @return string
     */
    public static function get_mappings_table(): string {
        global $wpdb;
        return $wpdb->prefix . 'dynamic_cta_mappings';
    }

    /**
     * Get Table Name for Click Statistics
     *
     * @return string
     */
    public static function get_clicks_table(): string {
        global $wpdb;
        return $wpdb->prefix . 'dynamic_cta_clicks';
    }

    /**
     * Plugin Activation Handler
     */
    public static function activate(): void {
        self::create_tables();
        self::init_default_options();
        CTA_Resolver::clear_cache();
    }

    /**
     * Plugin Deactivation Handler
     */
    public static function deactivate(): void {
        CTA_Resolver::clear_cache();
    }

    /**
     * Create Database Tables & Run Schema Migrations
     */
    public static function create_tables(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $mappings_table = self::get_mappings_table();
        $clicks_table = self::get_clicks_table();

        $sql_mappings = "CREATE TABLE {$mappings_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            keyword varchar(100) NOT NULL,
            area_name varchar(100) NOT NULL,
            source_url text DEFAULT NULL,
            destination_url text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY keyword (keyword)
        ) {$charset_collate};";

        $sql_clicks = "CREATE TABLE {$clicks_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            click_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            post_id bigint(20) DEFAULT 0 NOT NULL,
            post_title text DEFAULT NULL,
            area_name varchar(100) DEFAULT NULL,
            destination_url text DEFAULT NULL,
            referer text DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY click_date (click_date)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_mappings);
        dbDelta($sql_clicks);

        // Ensure source_url column exists if table was previously created
        $column = $wpdb->get_results("SHOW COLUMNS FROM {$mappings_table} LIKE 'source_url'");
        if (empty($column)) {
            $wpdb->query("ALTER TABLE {$mappings_table} ADD COLUMN source_url text DEFAULT NULL AFTER area_name");
        }
    }

    /**
     * Set default plugin options
     */
    public static function init_default_options(): void {
        add_option('dynamic_cta_default_url', 'https://jasawifi.com/iconnet/');
        add_option('dynamic_cta_open_link', '_self');
        add_option('dynamic_cta_enable_cache', 'yes');
        add_option('dynamic_cta_cache_lifetime', '12');
    }
}
