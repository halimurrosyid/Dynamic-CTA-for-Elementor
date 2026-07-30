<?php
namespace DynamicCTA;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class CTA_Resolver
 * Resolves destination URLs dynamically according to Post Slug, Category, and Tag.
 */
class CTA_Resolver {

    /**
     * Resolves the Dynamic CTA URL for a given post or current page.
     *
     * @param int|null $post_id
     * @return string
     */
    public static function get_cta_url(?int $post_id = null): string {
        if (!$post_id) {
            $post_id = get_queried_object_id();
            if (!$post_id) {
                $post_id = get_the_ID();
            }
        }

        $default_url = get_option('dynamic_cta_default_url', 'https://jasawifi.com/iconnet/');
        if (empty($default_url)) {
            $default_url = 'https://jasawifi.com/iconnet/';
        }

        if (!$post_id || !is_singular()) {
            return esc_url_raw($default_url);
        }

        $enable_cache = get_option('dynamic_cta_enable_cache', 'yes');
        $cache_lifetime_hours = (int) get_option('dynamic_cta_cache_lifetime', '12');
        if ($cache_lifetime_hours <= 0) {
            $cache_lifetime_hours = 12;
        }

        $transient_key = 'dcta_url_' . $post_id;

        if ($enable_cache === 'yes') {
            $cached_url = get_transient($transient_key);
            if ($cached_url !== false && !empty($cached_url)) {
                return esc_url_raw($cached_url);
            }
        }

        // Fetch all active mappings from DB
        $mappings = self::get_all_mappings();
        if (empty($mappings)) {
            return esc_url_raw($default_url);
        }

        $resolved_url = null;
        $post = get_post($post_id);

        if ($post) {
            // 1. Check Post Slug
            $slug = strtolower($post->post_name);
            $resolved_url = self::match_keyword($slug, $mappings);

            // 2. Check Categories if not matched by slug
            if (!$resolved_url) {
                $categories = get_the_category($post_id);
                if ($categories && !is_wp_error($categories)) {
                    foreach ($categories as $cat) {
                        $resolved_url = self::match_keyword(strtolower($cat->slug), $mappings);
                        if (!$resolved_url) {
                            $resolved_url = self::match_keyword(strtolower($cat->name), $mappings);
                        }
                        if ($resolved_url) {
                            break;
                        }
                    }
                }
            }

            // 3. Check Tags if not matched by slug or category
            if (!$resolved_url) {
                $tags = get_the_tags($post_id);
                if ($tags && !is_wp_error($tags)) {
                    foreach ($tags as $tag) {
                        $resolved_url = self::match_keyword(strtolower($tag->slug), $mappings);
                        if (!$resolved_url) {
                            $resolved_url = self::match_keyword(strtolower($tag->name), $mappings);
                        }
                        if ($resolved_url) {
                            break;
                        }
                    }
                }
            }
        }

        $final_url = $resolved_url ? $resolved_url : $default_url;
        $final_url = esc_url_raw($final_url);

        if ($enable_cache === 'yes') {
            set_transient($transient_key, $final_url, $cache_lifetime_hours * HOUR_IN_SECONDS);
        }

        return $final_url;
    }

    /**
     * Match input string against keyword mappings.
     * Selects longest matching keyword for highest specificity.
     *
     * @param string $subject
     * @param array $mappings
     * @return string|null
     */
    private static function match_keyword(string $subject, array $mappings): ?string {
        if (empty($subject)) {
            return null;
        }

        $best_match = null;
        $best_len = 0;

        foreach ($mappings as $row) {
            $keyword = strtolower(trim($row->keyword));
            if (empty($keyword)) {
                continue;
            }

            // Check if keyword is contained within subject slug/name as word/hyphen boundary or substring
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
     * Get all mappings with transient caching
     *
     * @return array
     */
    public static function get_all_mappings(): array {
        $transient_key = 'dcta_all_mappings';
        $mappings = get_transient($transient_key);

        if ($mappings === false) {
            global $wpdb;
            $table = DB::get_mappings_table();
            // Check if table exists
            if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) !== $table) {
                return [];
            }

            $mappings = $wpdb->get_results("SELECT keyword, area_name, destination_url FROM {$table} ORDER BY CHAR_LENGTH(keyword) DESC");
            if (!is_array($mappings)) {
                $mappings = [];
            }
            set_transient($transient_key, $mappings, 12 * HOUR_IN_SECONDS);
        }

        return $mappings;
    }

    /**
     * Clear CTA Transients Cache
     */
    public static function clear_cache(): void {
        delete_transient('dcta_all_mappings');

        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_dcta_url_%' OR option_name LIKE '_transient_timeout_dcta_url_%'");
    }
}
