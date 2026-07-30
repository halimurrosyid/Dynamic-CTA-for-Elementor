<?php
namespace DynamicCTA;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Autoloader
 * PSR-4 Autoloader for DynamicCTA namespace.
 */
class Autoloader {
    /**
     * Register autoloader with spl_autoload_register.
     */
    public static function register(): void {
        spl_autoload_register([__CLASS__, 'autoload']);
    }

    /**
     * Autoload class files.
     *
     * @param string $class
     */
    public static function autoload(string $class): void {
        $prefix = 'DynamicCTA\\';
        $base_dir = DYNAMIC_CTA_PATH . 'includes/';

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }
}
