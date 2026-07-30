<?php
namespace DynamicCTA\Elementor;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Dynamic_Tag_URL
 * Elementor Dynamic Tag providing Dynamic CTA URL for all link fields.
 */
class Dynamic_Tag_URL extends \Elementor\Core\DynamicTags\Data_Tag {

    /**
     * Dynamic Tag Name / Slug
     *
     * @return string
     */
    public function get_name(): string {
        return 'dynamic-cta-url';
    }

    /**
     * Dynamic Tag Title in Elementor UI
     *
     * @return string
     */
    public function get_title(): string {
        return esc_html__('Dynamic CTA URL', 'dynamic-cta-elementor');
    }

    /**
     * Group in Dynamic Tag dropdown
     *
     * @return array
     */
    public function get_group(): array {
        return ['dynamic-cta'];
    }

    /**
     * Supported Categories (URL field category)
     *
     * @return array
     */
    public function get_categories(): array {
        return [ \Elementor\Modules\DynamicTags\Module::URL_CATEGORY ];
    }

    /**
     * Return computed dynamic URL value
     *
     * @param array $options
     * @return string
     */
    public function get_value(array $options = []): string {
        return \DynamicCTA\CTA_Resolver::get_cta_url();
    }
}
