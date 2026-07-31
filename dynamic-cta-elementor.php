<?php
/**
 * Plugin Name:       Dynamic CTA for Elementor
 * Plugin URI:        https://github.com/halimurrosyid/Dynamic-CTA-for-Elementor
 * Description:       Universal area-based CTA link migration plugin for Elementor. Dynamically changes image, button, and popup CTA URLs based on post slug, URL path segments, categories, and tags with Destination Sitemap Importer.
 * Version:           1.1.3
 * Author:            Mujaddid Halimurrosyid
 * Author URI:        https://indahweb.com/
 * Text Domain:       dynamic-cta-elementor
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      8.1
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define Plugin Constants
define('DYNAMIC_CTA_VERSION', '1.1.3');
define('DYNAMIC_CTA_FILE', __FILE__);
define('DYNAMIC_CTA_PATH', plugin_dir_path(__FILE__));
define('DYNAMIC_CTA_URL', plugin_dir_url(__FILE__));
define('DYNAMIC_CTA_BASENAME', plugin_basename(__FILE__));

// Require Autoloader
require_once DYNAMIC_CTA_PATH . 'includes/Autoloader.php';

// Register Autoloader
\DynamicCTA\Autoloader::register();

// Activation / Deactivation hooks
register_activation_hook(__FILE__, ['\DynamicCTA\DB', 'activate']);
register_deactivation_hook(__FILE__, ['\DynamicCTA\DB', 'deactivate']);

// Initialize Plugin
function dynamic_cta_init(): \DynamicCTA\Plugin {
    return \DynamicCTA\Plugin::get_instance();
}

dynamic_cta_init();
