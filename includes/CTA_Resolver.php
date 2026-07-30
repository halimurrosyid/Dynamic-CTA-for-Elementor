<?php
namespace DynamicCTA;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class CTA_Resolver
 * High-performance Multi-Layer Smart Resolver for dynamic CTA destination URLs with Redis/Memcached & Elementor Preview support.
 */
class CTA_Resolver {

    /**
     * Resolves the Dynamic CTA URL for a given post or current page request.
     *
     * @param int|null $post_id
     * @return string
     */
    public static function get_cta_url(?int $post_id = null): string {
        $default_url = get_option('dynamic_cta_default_url', 'https://jasawifi.com/iconnet/');
        if (empty($default_url)) {
            $default_url = 'https://jasawifi.com/iconnet/';
        }
        $base_url = rtrim($default_url, '/') . '/';

        // Elementor Editor Live Preview Mode
        if (class_exists('\Elementor\Plugin') && \Elementor\Plugin::$instance->editor->is_edit_mode()) {
            return esc_url_raw($base_url . 'area-sample/');
        }

        $enable_cache = get_option('dynamic_cta_enable_cache', 'yes');
        $cache_lifetime_hours = (int) get_option('dynamic_cta_cache_lifetime', '12');
        if ($cache_lifetime_hours <= 0) {
            $cache_lifetime_hours = 12;
        }

        // Determine current URL path
        $current_path = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        $current_path = trim(wp_parse_url($current_path, PHP_URL_PATH) ?? '', '/');

        if (!$post_id) {
            $post_id = get_queried_object_id();
            if (!$post_id) {
                $post_id = get_the_ID();
            }
        }

        $transient_key = 'dcta_url_' . md5($current_path . '_' . $post_id);

        if ($enable_cache === 'yes') {
            $cached_url = get_transient($transient_key);
            if ($cached_url !== false && !empty($cached_url)) {
                return esc_url_raw($cached_url);
            }
        }

        $resolved_url = null;

        // LAYER 1: Custom Database Mappings Overrides (if specified in Admin Area Mapping)
        $mappings = self::get_all_mappings();
        if (!empty($mappings)) {
            // Check current path against custom mapping keywords
            $resolved_url = self::match_custom_mapping($current_path, $mappings);

            // Check post title / slug against custom mappings
            if (!$resolved_url && $post_id) {
                $post = get_post($post_id);
                if ($post) {
                    $resolved_url = self::match_custom_mapping($post->post_name, $mappings);
                    if (!$resolved_url) {
                        $resolved_url = self::match_custom_mapping($post->post_title, $mappings);
                    }
                }
            }
        }

        // LAYER 2: Direct URL Path Segment Extraction (e.g. /promo/bandung/ or /harga/bekasi/)
        if (!$resolved_url && !empty($current_path)) {
            $segments = explode('/', $current_path);
            foreach (array_reverse($segments) as $segment) {
                $matched_area = Scanner::detect_area_from_string($segment);
                if ($matched_area) {
                    $resolved_url = $base_url . $matched_area . '/';
                    break;
                }
            }
        }

        // LAYER 3: Post Context (Slug, Title, Categories, Tags)
        if (!$resolved_url && $post_id) {
            $post = get_post($post_id);
            if ($post) {
                // Check post slug (e.g. pasang-iconnet-bandung)
                $matched_area = Scanner::detect_area_from_string($post->post_name);

                // Check post title (e.g. Promo Iconnet Bandung Terbaru)
                if (!$matched_area) {
                    $matched_area = Scanner::detect_area_from_string(strtolower($post->post_title));
                }

                // Check Categories
                if (!$matched_area) {
                    $categories = get_the_category($post_id);
                    if ($categories && !is_wp_error($categories)) {
                        foreach ($categories as $cat) {
                            $matched_area = Scanner::detect_area_from_string(strtolower($cat->slug));
                            if (!$matched_area) {
                                $matched_area = Scanner::detect_area_from_string(strtolower($cat->name));
                            }
                            if ($matched_area) {
                                break;
                            }
                        }
                    }
                }

                // Check Tags
                if (!$matched_area) {
                    $tags = get_the_tags($post_id);
                    if ($tags && !is_wp_error($tags)) {
                        foreach ($tags as $tag) {
                            $matched_area = Scanner::detect_area_from_string(strtolower($tag->slug));
                            if (!$matched_area) {
                                $matched_area = Scanner::detect_area_from_string(strtolower($tag->name));
                            }
                            if ($matched_area) {
                                break;
                            }
                        }
                    }
                }

                if ($matched_area) {
                    $resolved_url = $base_url . $matched_area . '/';
                }
            }
        }

        // LAYER 4: Fallback Default URL
        $final_url = $resolved_url ? $resolved_url : $default_url;
        $final_url = esc_url_raw($final_url);

        if ($enable_cache === 'yes') {
            set_transient($transient_key, $final_url, $cache_lifetime_hours * HOUR_IN_SECONDS);
        }

        return $final_url;
    }

    /**
     * Match custom mappings against subject string
     *
     * @param string $subject
     * @param array $mappings
     * @return string|null
     */
    private static function match_custom_mapping(string $subject, array $mappings): ?string {
        if (empty($subject)) {
            return null;
        }

        $subject = strtolower($subject);
        $best_match = null;
        $best_len = 0;

        foreach ($mappings as $row) {
            $keyword = strtolower(trim($row->keyword));
            if (empty($keyword)) {
                continue;
            }

            if (str_contains($subject, $keyword)) {
                $len = strlen($keyword);
                if ($len > $best_len) {
                    $best_len = $len;
                    $best_match = $row->destination_url;
                }
            }
        }

        return $best_match;
    }

    /**
     * Get all custom mappings with transient caching
     *
     * @return array
     */
    public static function get_all_mappings(): array {
        $transient_key = 'dcta_all_mappings';
        $mappings = get_transient($transient_key);

        if ($mappings === false) {
            global $wpdb;
            $table = DB::get_mappings_table();
            if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) !== $table) {
                return [];
            }

            $mappings = $wpdb->get_results("SELECT keyword, area_name, source_url, destination_url FROM {$table} ORDER BY CHAR_LENGTH(keyword) DESC");
            if (!is_array($mappings)) {
                $mappings = [];
            }
            set_transient($transient_key, $mappings, 12 * HOUR_IN_SECONDS);
        }

        return $mappings;
    }

    /**
     * Clear CTA Transients Cache (Compatible with Redis / Memcached / DB transients)
     */
    public static function clear_cache(): void {
        delete_transient('dcta_all_mappings');

        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_dcta_url_%' OR option_name LIKE '_transient_timeout_dcta_url_%'");

        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }
}
