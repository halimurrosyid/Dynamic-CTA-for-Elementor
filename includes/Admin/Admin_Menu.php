<?php
namespace DynamicCTA\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Admin_Menu
 * Registers Top-level Admin Menu, Submenus, Plugin Action Links, Enqueues Admin Assets, and handles Admin AJAX routing.
 */
class Admin_Menu {

    /**
     * Initialize Admin Menu Hooks
     */
    public function init(): void {
        add_action('admin_menu', [$this, 'register_menu_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_filter('plugin_action_links_' . DYNAMIC_CTA_BASENAME, [$this, 'add_plugin_action_links']);
    }

    /**
     * Add direct Settings & Area Mapping links on WP Plugins page list
     *
     * @param array $links
     * @return array
     */
    public function add_plugin_action_links(array $links): array {
        $mapping_link  = '<a href="' . esc_url(admin_url('admin.php?page=dynamic-cta')) . '"><strong>' . esc_html__('Area Mapping', 'dynamic-cta-elementor') . '</strong></a>';
        $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=dynamic-cta-settings')) . '">' . esc_html__('Settings', 'dynamic-cta-elementor') . '</a>';
        
        array_unshift($links, $mapping_link, $settings_link);
        return $links;
    }

    /**
     * Register Admin Pages
     */
    public function register_menu_pages(): void {
        add_menu_page(
            __('Dynamic CTA for Elementor', 'dynamic-cta-elementor'),
            __('Dynamic CTA', 'dynamic-cta-elementor'),
            'manage_options',
            'dynamic-cta',
            [Area_Mapping_Page::class, 'render'],
            'dashicons-location-alt',
            30
        );

        add_submenu_page(
            'dynamic-cta',
            __('Area Mapping', 'dynamic-cta-elementor'),
            __('Area Mapping', 'dynamic-cta-elementor'),
            'manage_options',
            'dynamic-cta',
            [Area_Mapping_Page::class, 'render']
        );

        add_submenu_page(
            'dynamic-cta',
            __('Settings', 'dynamic-cta-elementor'),
            __('Settings', 'dynamic-cta-elementor'),
            'manage_options',
            'dynamic-cta-settings',
            [Settings_Page::class, 'render']
        );

        add_submenu_page(
            'dynamic-cta',
            __('Click Statistics', 'dynamic-cta-elementor'),
            __('Click Statistics', 'dynamic-cta-elementor'),
            'manage_options',
            'dynamic-cta-stats',
            [Stats_Page::class, 'render']
        );
    }

    /**
     * Enqueue Admin CSS & JS
     *
     * @param string $hook
     */
    public function enqueue_admin_assets(string $hook): void {
        if (!str_contains($hook, 'dynamic-cta')) {
            return;
        }

        wp_enqueue_style(
            'dynamic-cta-admin-css',
            DYNAMIC_CTA_URL . 'assets/css/admin.css',
            [],
            DYNAMIC_CTA_VERSION
        );

        wp_enqueue_script(
            'dynamic-cta-admin-js',
            DYNAMIC_CTA_URL . 'assets/js/admin.js',
            ['jquery'],
            DYNAMIC_CTA_VERSION,
            true
        );

        wp_localize_script('dynamic-cta-admin-js', 'dynamic_cta_admin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('dynamic_cta_admin_nonce'),
            'strings'  => [
                'confirm_delete' => __('Are you sure you want to delete this mapping?', 'dynamic-cta-elementor'),
                'confirm_clear_stats' => __('Are you sure you want to clear all click log statistics?', 'dynamic-cta-elementor'),
                'scanning' => __('Scanning posts permalinks, please wait...', 'dynamic-cta-elementor'),
            ],
        ]);
    }
}
