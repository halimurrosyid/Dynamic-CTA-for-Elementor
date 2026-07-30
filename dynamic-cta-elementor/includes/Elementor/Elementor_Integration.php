<?php
namespace DynamicCTA\Elementor;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Elementor_Integration
 * Hooks into Elementor to register Dynamic Tag Groups and Dynamic Tags.
 */
class Elementor_Integration {

    /**
     * Initialize Elementor Hooks
     */
    public function init(): void {
        add_action('elementor/dynamic_tags/register', [$this, 'register_dynamic_tags']);
    }

    /**
     * Register Dynamic Tags and Tag Group
     *
     * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager
     */
    public function register_dynamic_tags($dynamic_tags_manager): void {
        // Register Dynamic CTA Category Group
        $dynamic_tags_manager->register_group('dynamic-cta', [
            'title' => esc_html__('Dynamic CTA', 'dynamic-cta-elementor'),
        ]);

        // Register Dynamic Tag
        $dynamic_tags_manager->register(new Dynamic_Tag_URL());
    }
}
