<?php
namespace DynamicCTA;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Stats
 * Click tracking recorder and statistics analyzer with multi-day comparison and automatic light log optimization.
 */
class Stats {

    /**
     * Record a click event with deduplication and auto-pruning
     *
     * @param array $data
     * @return bool
     */
    public static function record_click(array $data): bool {
        global $wpdb;
        $table = DB::get_clicks_table();

        $post_id = isset($data['post_id']) ? (int) $data['post_id'] : 0;
        $ip_address = self::get_client_ip();

        // Anti-Spam / Deduplication: Prevent duplicate log entries within 5 seconds for same IP and post
        $recent = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE ip_address = %s AND post_id = %d AND click_date >= NOW() - INTERVAL 5 SECOND LIMIT 1",
            $ip_address,
            $post_id
        ));

        if ($recent) {
            return false;
        }

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

        // Lightweight Auto-Pruning: 1 in 50 chance to prune logs older than retention period (default 60 days)
        if ($inserted && rand(1, 50) === 1) {
            self::prune_old_logs(60);
        }

        return (bool) $inserted;
    }

    /**
     * Get multi-day comparison metrics for dashboard
     *
     * @return array
     */
    public static function get_comparison_summary(): array {
        global $wpdb;
        $table = DB::get_clicks_table();

        // Total Clicks
        $total_clicks = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");

        // Today vs Yesterday
        $today = current_time('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day', strtotime($today)));

        $today_clicks = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE DATE(click_date) = %s", $today));
        $yesterday_clicks = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE DATE(click_date) = %s", $yesterday));

        $day_growth = 0;
        if ($yesterday_clicks > 0) {
            $day_growth = round((($today_clicks - $yesterday_clicks) / $yesterday_clicks) * 100, 1);
        } elseif ($today_clicks > 0) {
            $day_growth = 100;
        }

        // Last 7 Days vs Prior 7 Days
        $last_7_start = date('Y-m-d', strtotime('-6 days', strtotime($today)));
        $prev_7_start = date('Y-m-d', strtotime('-13 days', strtotime($today)));
        $prev_7_end   = date('Y-m-d', strtotime('-7 days', strtotime($today)));

        $last_7_clicks = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE DATE(click_date) BETWEEN %s AND %s", $last_7_start, $today));
        $prev_7_clicks = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE DATE(click_date) BETWEEN %s AND %s", $prev_7_start, $prev_7_end));

        $week_growth = 0;
        if ($prev_7_clicks > 0) {
            $week_growth = round((($last_7_clicks - $prev_7_clicks) / $prev_7_clicks) * 100, 1);
        } elseif ($last_7_clicks > 0) {
            $week_growth = 100;
        }

        // Last 30 Days
        $last_30_start = date('Y-m-d', strtotime('-29 days', strtotime($today)));
        $last_30_clicks = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE DATE(click_date) BETWEEN %s AND %s", $last_30_start, $today));

        return [
            'total_clicks'     => $total_clicks,
            'today_clicks'     => $today_clicks,
            'yesterday_clicks' => $yesterday_clicks,
            'day_growth'       => $day_growth,
            'last_7_clicks'    => $last_7_clicks,
            'prev_7_clicks'    => $prev_7_clicks,
            'week_growth'      => $week_growth,
            'last_30_clicks'   => $last_30_clicks,
        ];
    }

    /**
     * Get daily breakdown for trend chart & comparison table
     *
     * @param int $days
     * @return array
     */
    public static function get_daily_trends(int $days = 7): array {
        global $wpdb;
        $table = DB::get_clicks_table();

        $days = in_array($days, [7, 14, 30], true) ? $days : 7;
        $today = current_time('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-' . ($days - 1) . ' days', strtotime($today)));

        $raw_results = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(click_date) as cdate, COUNT(*) as cnt FROM {$table} WHERE DATE(click_date) BETWEEN %s AND %s GROUP BY DATE(click_date) ORDER BY cdate ASC",
            $start_date,
            $today
        ), OBJECT_K);

        $trends = [];
        $max_clicks = 1;

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime('-' . $i . ' days', strtotime($today)));
            $formatted_date = date_i18n('d M', strtotime($date));
            $day_name = date_i18n('D', strtotime($date));
            $clicks = isset($raw_results[$date]) ? (int) $raw_results[$date]->cnt : 0;

            if ($clicks > $max_clicks) {
                $max_clicks = $clicks;
            }

            $trends[] = [
                'date'           => $date,
                'formatted_date' => $formatted_date,
                'day_name'       => $day_name,
                'clicks'         => $clicks,
            ];
        }

        return [
            'trends'     => $trends,
            'max_clicks' => $max_clicks,
        ];
    }

    /**
     * Get top performing areas
     *
     * @param int $limit
     * @param int $days
     * @return array
     */
    public static function get_top_areas(int $limit = 5, int $days = 30): array {
        global $wpdb;
        $table = DB::get_clicks_table();

        $today = current_time('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-' . ($days - 1) . ' days', strtotime($today)));

        $total_in_period = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE DATE(click_date) BETWEEN %s AND %s",
            $start_date,
            $today
        ));

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT area_name, COUNT(*) as cnt FROM {$table} WHERE DATE(click_date) BETWEEN %s AND %s AND area_name IS NOT NULL AND area_name != '' GROUP BY area_name ORDER BY cnt DESC LIMIT %d",
            $start_date,
            $today,
            $limit
        ));

        if (!is_array($results)) {
            return [];
        }

        $items = [];
        foreach ($results as $row) {
            $cnt = (int) $row->cnt;
            $percent = $total_in_period > 0 ? round(($cnt / $total_in_period) * 100, 1) : 0;
            $items[] = [
                'area_name' => $row->area_name,
                'clicks'    => $cnt,
                'percent'   => $percent,
            ];
        }

        return $items;
    }

    /**
     * Get top performing articles/posts
     *
     * @param int $limit
     * @param int $days
     * @return array
     */
    public static function get_top_posts(int $limit = 5, int $days = 30): array {
        global $wpdb;
        $table = DB::get_clicks_table();

        $today = current_time('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-' . ($days - 1) . ' days', strtotime($today)));

        $total_in_period = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE DATE(click_date) BETWEEN %s AND %s",
            $start_date,
            $today
        ));

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT post_id, post_title, COUNT(*) as cnt FROM {$table} WHERE DATE(click_date) BETWEEN %s AND %s AND post_title IS NOT NULL AND post_title != '' GROUP BY post_id, post_title ORDER BY cnt DESC LIMIT %d",
            $start_date,
            $today,
            $limit
        ));

        if (!is_array($results)) {
            return [];
        }

        $items = [];
        foreach ($results as $row) {
            $cnt = (int) $row->cnt;
            $percent = $total_in_period > 0 ? round(($cnt / $total_in_period) * 100, 1) : 0;
            $items[] = [
                'post_id'    => (int) $row->post_id,
                'post_title' => $row->post_title,
                'clicks'     => $cnt,
                'percent'    => $percent,
            ];
        }

        return $items;
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
     * Prune logs older than specified days to keep DB table ultra lightweight
     *
     * @param int $days
     * @return int Number of deleted rows
     */
    public static function prune_old_logs(int $days = 60): int {
        global $wpdb;
        $table = DB::get_clicks_table();
        $cutoff_date = date('Y-m-d 00:00:00', strtotime('-' . (int)$days . ' days', current_time('timestamp')));
        return (int) $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE click_date < %s", $cutoff_date));
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

