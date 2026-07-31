<?php
namespace DynamicCTA;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class CTA_Resolver
 * High-performance Multi-Layer Smart Resolver for dynamic CTA destination URLs.
 * Ensures Database Area Mappings ALWAYS take priority over fallback Base URL patterns.
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

        // Build Custom DB Mappings dictionary
        $mappings = self::get_all_mappings();
        $mapping_dict = [];
        if (!empty($mappings)) {
            foreach ($mappings as $row) {
                $kw = strtolower(trim($row->keyword));
                if (!empty($kw)) {
                    $mapping_dict[$kw] = $row->destination_url;
                }
            }
        }

        $detected_keyword = null;
        $resolved_url     = null;

        // STEP 1: Direct Path Segments Inspection (e.g. /area/malang/ or /promo/bandung/)
        if (!empty($current_path)) {
            $segments = explode('/', $current_path);
            foreach (array_reverse($segments) as $segment) {
                $segment_kw = Scanner::detect_area_from_string($segment);
                if ($segment_kw) {
                    $detected_keyword = $segment_kw;
                    break;
                }
            }
        }

        // STEP 2: Post Context (Slug, Title, Categories, Tags)
        if (!$detected_keyword && $post_id) {
            $post = get_post($post_id);
            if ($post) {
                $detected_keyword = Scanner::detect_area_from_string($post->post_name);
                if (!$detected_keyword) {
                    $matched_custom = self::match_custom_mapping($post->post_name, $mappings);
                    if ($matched_custom) {
                        $resolved_url = $matched_custom;
                    } else {
                        $detected_keyword = Scanner::detect_area_from_string(strtolower($post->post_title));
                    }
                }

                if (!$detected_keyword && !$resolved_url) {
                    $categories = get_the_category($post_id);
                    if ($categories && !is_wp_error($categories)) {
                        foreach ($categories as $cat) {
                            $detected_keyword = Scanner::detect_area_from_string(strtolower($cat->slug));
                            if (!$detected_keyword) {
                                $detected_keyword = Scanner::detect_area_from_string(strtolower($cat->name));
                            }
                            if ($detected_keyword) {
                                break;
                            }
                        }
                    }
                }

                if (!$detected_keyword && !$resolved_url) {
                    $tags = get_the_tags($post_id);
                    if ($tags && !is_wp_error($tags)) {
                        foreach ($tags as $tag) {
                            $detected_keyword = Scanner::detect_area_from_string(strtolower($tag->slug));
                            if (!$detected_keyword) {
                                $detected_keyword = Scanner::detect_area_from_string(strtolower($tag->name));
                            }
                            if ($detected_keyword) {
                                break;
                            }
                        }
                    }
                }
            }
        }

        // STEP 3: PRIORITY CHECK - Does the detected keyword match a Custom DB Mapping?
        if (!$resolved_url && $detected_keyword) {
            $kw_lower = strtolower($detected_keyword);
            if (isset($mapping_dict[$kw_lower]) && !empty($mapping_dict[$kw_lower])) {
                // ALWAYS USE EXACT DESTINATION URL FROM DATABASE
                $resolved_url = $mapping_dict[$kw_lower];
            } else {
                // Fallback to pattern generation: base_url + detected_keyword + '/'
                $resolved_url = $base_url . $detected_keyword . '/';
            }
        }

        // STEP 4: Fallback Default URL
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

            $pattern = '/(?<=^|[-_\s\/])' . preg_quote($keyword, '/') . '(?=$|[-_\s\/])/i';
            if (preg_match($pattern, $subject)) {
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
