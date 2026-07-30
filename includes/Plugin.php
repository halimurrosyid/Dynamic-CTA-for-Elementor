<?php
namespace DynamicCTA;

use DynamicCTA\Admin\Admin_Menu;
use DynamicCTA\Admin\Area_Mapping_Page;
use DynamicCTA\Admin\Settings_Page;
use DynamicCTA\Admin\Stats_Page;
use DynamicCTA\Admin\GitHub_Updater;
use DynamicCTA\Elementor\Elementor_Integration;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Plugin
 * Main Singleton Plugin Controller
 */
class Plugin {

    /**
     * Singleton Instance
     *
     * @var Plugin|null
     */
    private static ?Plugin $instance = null;

    /**
     * Get Singleton Instance
     *
     * @return Plugin
     */
    public static function get_instance(): Plugin {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_components();
        $this->init_hooks();
    }

    /**
     * Initialize Sub-Components
     */
    private function init_components(): void {
        // Init Admin & GitHub Automatic Updater
        if (is_admin()) {
            (new Admin_Menu())->init();
            Area_Mapping_Page::init_hooks();
            Settings_Page::init_hooks();
            Stats_Page::init_hooks();
            new GitHub_Updater(DYNAMIC_CTA_FILE);
        }

        // Init Elementor Integration
        (new Elementor_Integration())->init();
    }

    /**
     * Initialize Hooks
     */
    private function init_hooks(): void {
        add_action('init', [$this, 'load_textdomain']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);

        // Optional Shortcode support [dynamic_cta_url]
        add_shortcode('dynamic_cta_url', [$this, 'shortcode_dynamic_cta_url']);

        // Click Tracking AJAX Endpoints
        add_action('wp_ajax_dynamic_cta_record_click', [$this, 'ajax_record_click']);
        add_action('wp_ajax_nopriv_dynamic_cta_record_click', [$this, 'ajax_record_click']);
    }

    /**
     * Load Plugin Textdomain
     */
    public function load_textdomain(): void {
        load_plugin_textdomain(
            'dynamic-cta-elementor',
            false,
            dirname(DYNAMIC_CTA_BASENAME) . '/languages/'
        );
    }

    /**
     * Optional Shortcode callback [dynamic_cta_url]
     *
     * @return string
     */
    public function shortcode_dynamic_cta_url(): string {
        return CTA_Resolver::get_cta_url();
    }

    /**
     * Enqueue Frontend Tracker JavaScript
     */
    public function enqueue_frontend_assets(): void {
        wp_enqueue_script(
            'dynamic-cta-tracker',
            DYNAMIC_CTA_URL . 'assets/js/tracker.js',
            [],
            DYNAMIC_CTA_VERSION,
            true
        );

        $target_attr = get_option('dynamic_cta_open_link', '_self');

        wp_localize_script('dynamic-cta-tracker', 'dynamic_cta_params', [
            'ajax_url'    => admin_url('admin-ajax.php'),
            'post_id'     => get_queried_object_id() ? get_queried_object_id() : get_the_ID(),
            'open_link'   => $target_attr,
            'default_url' => get_option('dynamic_cta_default_url', 'https://jasawifi.com/iconnet/'),
        ]);
    }

    /**
     * AJAX Record Click Endpoint
     */
    public function ajax_record_click(): void {
        $post_id         = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        $area_name       = isset($_POST['area_name']) ? sanitize_text_field($_POST['area_name']) : '';
        $destination_url = isset($_POST['destination_url']) ? esc_url_raw($_POST['destination_url']) : '';
        $referer         = isset($_POST['referer']) ? esc_url_raw($_POST['referer']) : '';

        Stats::record_click([
            'post_id'         => $post_id,
            'area_name'       => $area_name,
            'destination_url' => $destination_url,
            'referer'         => $referer,
        ]);

        wp_send_json_success(['status' => 'recorded']);
    }
}
