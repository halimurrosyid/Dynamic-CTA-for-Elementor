<?php
namespace DynamicCTA;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Stats
 * Click tracking recorder and statistics analyzer.
 */
class Stats {

    /**
     * Record a click event
     *
     * @param array $data
     * @return bool
     */
    public static function record_click(array $data): bool {
        global $wpdb;
        $table = DB::get_clicks_table();

        $post_id = isset($data['post_id']) ? (int) $data['post_id'] : 0;
        $post_title = '';
        if ($post_id > 0) {
            $post = get_post($post_id);
            if ($post) {
                $post_title = $post->post_title;
            }
        }

        $area_name       = isset($data['area_name']) ? sanitize_text_field($data['area_name']) : '';
        $destination_url = isset($data['destination_url']) ? esc_url_raw($data['destination_url']) : '';
        $referer         = isset($data['referer']) ? esc_url_raw($data['referer']) : '';
        $ip_address      = self::get_client_ip();
        $user_agent      = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';

        $inserted = $wpdb->insert(
            $table,
            [
                'click_date'      => current_time('mysql'),
                'post_id'         => $post_id,
                'post_title'      => $post_title,
                'area_name'       => $area_name,
                'destination_url' => $destination_url,
                'referer'         => $referer,
                'ip_address'      => $ip_address,
                'user_agent'      => $user_agent,
            ],
            ['%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        return (bool) $inserted;
    }

    /**
     * Get summary metrics for admin dashboard
     *
     * @return array
     */
    public static function get_summary(): array {
        global $wpdb;
        $table = DB::get_clicks_table();

        // Total Clicks
        $total_clicks = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");

        // Today's Clicks
        $today = current_time('Y-m-d');
        $today_clicks = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE DATE(click_date) = %s", $today));

        // Top Area
        $top_area_row = $wpdb->get_row("SELECT area_name, COUNT(*) as cnt FROM {$table} WHERE area_name IS NOT NULL AND area_name != '' GROUP BY area_name ORDER BY cnt DESC LIMIT 1");
        $top_area = $top_area_row ? $top_area_row->area_name . ' (' . $top_area_row->cnt . ')' : '-';

        // Top Page/Post
        $top_post_row = $wpdb->get_row("SELECT post_title, COUNT(*) as cnt FROM {$table} WHERE post_title IS NOT NULL AND post_title != '' GROUP BY post_title ORDER BY cnt DESC LIMIT 1");
        $top_post = $top_post_row ? $top_post_row->post_title . ' (' . $top_post_row->cnt . ')' : '-';

        return [
            'total_clicks' => $total_clicks,
            'today_clicks' => $today_clicks,
            'top_area'     => $top_area,
            'top_post'     => $top_post,
        ];
    }

    /**
     * Fetch paginated click records
     *
     * @param int $limit
     * @param int $offset
     * @param string $search
     * @return array
     */
    public static function get_clicks(int $limit = 50, int $offset = 0, string $search = ''): array {
        global $wpdb;
        $table = DB::get_clicks_table();

        $where = '';
        if (!empty($search)) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where = $wpdb->prepare(" WHERE post_title LIKE %s OR area_name LIKE %s OR destination_url LIKE %s OR ip_address LIKE %s ", $like, $like, $like, $like);
        }

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} {$where}");
        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} {$where} ORDER BY id DESC LIMIT %d OFFSET %d", $limit, $offset));

        return [
            'total'   => $total,
            'results' => is_array($results) ? $results : [],
        ];
    }

    /**
     * Clear all click logs history
     *
     * @return bool
     */
    public static function clear_stats(): bool {
        global $wpdb;
        $table = DB::get_clicks_table();
        return (bool) $wpdb->query("TRUNCATE TABLE {$table}");
    }

    /**
     * Helper to get anonymized client IP
     *
     * @return string
     */
    private static function get_client_ip(): string {
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return sanitize_text_field(trim($ip));
    }
}
