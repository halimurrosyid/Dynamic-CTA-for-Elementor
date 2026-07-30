<?php
namespace DynamicCTA\Admin;

use DynamicCTA\Stats;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Stats_Page
 * Renders Click Statistics Dashboard and Logs Table.
 */
class Stats_Page {

    /**
     * Register admin-post handlers
     */
    public static function init_hooks(): void {
        add_action('admin_post_dynamic_cta_clear_stats', [self::class, 'handle_clear_stats']);
    }

    /**
     * Handle Clear Stats Log POST request
     */
    public static function handle_clear_stats(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized action.', 'dynamic-cta-elementor'));
        }

        check_admin_referer('dynamic_cta_clear_stats_nonce');

        Stats::clear_stats();

        wp_redirect(add_query_arg([
            'page'    => 'dynamic-cta-stats',
            'cleared' => '1',
        ], admin_url('admin.php')));
        exit;
    }

    /**
     * Render Statistics Page
     */
    /**
     * Render Statistics Page
     */
    public static function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'dynamic-cta-elementor'));
        }

        $summary = Stats::get_comparison_summary();
        $range   = isset($_GET['range']) ? (int) $_GET['range'] : 7;
        if (!in_array($range, [7, 14, 30], true)) {
            $range = 7;
        }

        $daily_data = Stats::get_daily_trends($range);
        $top_areas  = Stats::get_top_areas(5, $range);
        $top_posts  = Stats::get_top_posts(5, $range);

        $paged   = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
        $search  = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $limit   = 20;
        $offset  = ($paged - 1) * $limit;

        $logs_data   = Stats::get_clicks($limit, $offset, $search);
        $total_items = $logs_data['total'];
        $total_pages = ceil($total_items / $limit);
        $clicks      = $logs_data['results'];
        ?>
        <div class="wrap dynamic-cta-wrap">
            <h1>
                <span class="dashicons dashicons-chart-bar"></span>
                <?php esc_html_e('Dynamic CTA - Click Statistics & Analytics', 'dynamic-cta-elementor'); ?>
            </h1>

            <hr class="wp-header-end">

            <?php if (isset($_GET['cleared']) && $_GET['cleared'] === '1'): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e('Click statistics log cleared successfully!', 'dynamic-cta-elementor'); ?></p>
                </div>
            <?php endif; ?>

            <!-- Metrics Comparison Summary Grid -->
            <div class="dcta-stats-grid">
                <!-- Today's Clicks & Day Growth -->
                <div class="dcta-metric-card">
                    <div class="metric-icon"><span class="dashicons dashicons-calendar-alt"></span></div>
                    <div class="metric-data">
                        <div class="metric-value"><?php echo esc_html(number_format_i18n($summary['today_clicks'])); ?></div>
                        <div class="metric-label"><?php esc_html_e("Today's Clicks", 'dynamic-cta-elementor'); ?></div>
                        <?php if ($summary['day_growth'] > 0): ?>
                            <div class="dcta-growth-badge positive">
                                ▲ +<?php echo esc_html($summary['day_growth']); ?>% vs <?php esc_html_e('Yesterday', 'dynamic-cta-elementor'); ?>
                            </div>
                        <?php elseif ($summary['day_growth'] < 0): ?>
                            <div class="dcta-growth-badge negative">
                                ▼ <?php echo esc_html($summary['day_growth']); ?>% vs <?php esc_html_e('Yesterday', 'dynamic-cta-elementor'); ?>
                            </div>
                        <?php else: ?>
                            <div class="dcta-growth-badge neutral">
                                ➔ 0% vs <?php esc_html_e('Yesterday', 'dynamic-cta-elementor'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Last 7 Days & Week Growth -->
                <div class="dcta-metric-card">
                    <div class="metric-icon"><span class="dashicons dashicons-chart-line"></span></div>
                    <div class="metric-data">
                        <div class="metric-value"><?php echo esc_html(number_format_i18n($summary['last_7_clicks'])); ?></div>
                        <div class="metric-label"><?php esc_html_e('Last 7 Days', 'dynamic-cta-elementor'); ?></div>
                        <?php if ($summary['week_growth'] > 0): ?>
                            <div class="dcta-growth-badge positive">
                                ▲ +<?php echo esc_html($summary['week_growth']); ?>% vs <?php esc_html_e('Prev 7 Days', 'dynamic-cta-elementor'); ?>
                            </div>
                        <?php elseif ($summary['week_growth'] < 0): ?>
                            <div class="dcta-growth-badge negative">
                                ▼ <?php echo esc_html($summary['week_growth']); ?>% vs <?php esc_html_e('Prev 7 Days', 'dynamic-cta-elementor'); ?>
                            </div>
                        <?php else: ?>
                            <div class="dcta-growth-badge neutral">
                                ➔ 0% vs <?php esc_html_e('Prev 7 Days', 'dynamic-cta-elementor'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Last 30 Days -->
                <div class="dcta-metric-card">
                    <div class="metric-icon"><span class="dashicons dashicons-calendar"></span></div>
                    <div class="metric-data">
                        <div class="metric-value"><?php echo esc_html(number_format_i18n($summary['last_30_clicks'])); ?></div>
                        <div class="metric-label"><?php esc_html_e('Last 30 Days', 'dynamic-cta-elementor'); ?></div>
                        <div class="dcta-growth-badge neutral">
                            <?php esc_html_e('Monthly Total', 'dynamic-cta-elementor'); ?>
                        </div>
                    </div>
                </div>

                <!-- Total All-Time -->
                <div class="dcta-metric-card">
                    <div class="metric-icon"><span class="dashicons dashicons-database"></span></div>
                    <div class="metric-data">
                        <div class="metric-value"><?php echo esc_html(number_format_i18n($summary['total_clicks'])); ?></div>
                        <div class="metric-label"><?php esc_html_e('Total All-Time Clicks', 'dynamic-cta-elementor'); ?></div>
                        <div class="dcta-growth-badge neutral">
                            <?php esc_html_e('Lifetime Record', 'dynamic-cta-elementor'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daily Trend Visualizer & Breakdown Section -->
            <div class="dcta-analytics-container">
                <div class="dcta-analytics-header">
                    <h2>
                        <span class="dashicons dashicons-chart-area"></span>
                        <?php printf(esc_html__('Daily Click Trend (%d Days)', 'dynamic-cta-elementor'), $range); ?>
                    </h2>
                    <form method="get" class="dcta-inline-form">
                        <input type="hidden" name="page" value="dynamic-cta-stats">
                        <?php if (!empty($search)): ?>
                            <input type="hidden" name="s" value="<?php echo esc_attr($search); ?>">
                        <?php endif; ?>
                        <select name="range" onchange="this.form.submit()">
                            <option value="7" <?php selected($range, 7); ?>><?php esc_html_e('Last 7 Days', 'dynamic-cta-elementor'); ?></option>
                            <option value="14" <?php selected($range, 14); ?>><?php esc_html_e('Last 14 Days', 'dynamic-cta-elementor'); ?></option>
                            <option value="30" <?php selected($range, 30); ?>><?php esc_html_e('Last 30 Days', 'dynamic-cta-elementor'); ?></option>
                        </select>
                    </form>
                </div>

                <!-- Daily Bar Chart -->
                <div class="dcta-chart-bars">
                    <?php
                    $max = $daily_data['max_clicks'];
                    foreach ($daily_data['trends'] as $item):
                        $pct = max(5, round(($item['clicks'] / $max) * 100));
                    ?>
                        <div class="dcta-bar-col" title="<?php echo esc_attr($item['formatted_date'] . ': ' . $item['clicks'] . ' clicks'); ?>">
                            <span class="dcta-bar-value"><?php echo esc_html($item['clicks']); ?></span>
                            <div class="dcta-bar-fill" style="height: <?php echo esc_attr($pct); ?>%;"></div>
                            <span class="dcta-bar-label"><?php echo esc_html($item['formatted_date']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Side-by-Side Top Performance Breakdown -->
                <div class="dcta-breakdown-grid">
                    <!-- Top Areas Card -->
                    <div class="dcta-breakdown-card">
                        <h3>
                            <span class="dashicons dashicons-location"></span>
                            <?php esc_html_e('Top Performing Areas', 'dynamic-cta-elementor'); ?>
                        </h3>
                        <?php if (empty($top_areas)): ?>
                            <p style="color:#8c8f94;"><?php esc_html_e('No area data in this period.', 'dynamic-cta-elementor'); ?></p>
                        <?php else: ?>
                            <?php foreach ($top_areas as $area): ?>
                                <div class="dcta-rank-item">
                                    <div class="dcta-rank-info">
                                        <span class="dcta-rank-title"><?php echo esc_html($area['area_name']); ?></span>
                                        <span class="dcta-rank-count"><?php echo esc_html($area['clicks']); ?> clicks (<?php echo esc_html($area['percent']); ?>%)</span>
                                    </div>
                                    <div class="dcta-progress-bg">
                                        <div class="dcta-progress-bar" style="width: <?php echo esc_attr($area['percent']); ?>%;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Top Articles Card -->
                    <div class="dcta-breakdown-card">
                        <h3>
                            <span class="dashicons dashicons-admin-post"></span>
                            <?php esc_html_e('Top Performing Articles', 'dynamic-cta-elementor'); ?>
                        </h3>
                        <?php if (empty($top_posts)): ?>
                            <p style="color:#8c8f94;"><?php esc_html_e('No post data in this period.', 'dynamic-cta-elementor'); ?></p>
                        <?php else: ?>
                            <?php foreach ($top_posts as $post_item): ?>
                                <div class="dcta-rank-item">
                                    <div class="dcta-rank-info">
                                        <span class="dcta-rank-title">
                                            <?php if ($post_item['post_id'] > 0): ?>
                                                <a href="<?php echo esc_url(get_permalink($post_item['post_id'])); ?>" target="_blank" rel="noopener noreferrer">
                                                    <?php echo esc_html(wp_trim_words($post_item['post_title'], 6, '...')); ?>
                                                </a>
                                            <?php else: ?>
                                                <?php echo esc_html($post_item['post_title']); ?>
                                            <?php endif; ?>
                                        </span>
                                        <span class="dcta-rank-count"><?php echo esc_html($post_item['clicks']); ?> clicks (<?php echo esc_html($post_item['percent']); ?>%)</span>
                                    </div>
                                    <div class="dcta-progress-bg">
                                        <div class="dcta-progress-bar" style="width: <?php echo esc_attr($post_item['percent']); ?>%;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Toolbar & Detailed Click Logs Table -->
            <div class="dynamic-cta-toolbar margin-top-20">
                <div class="toolbar-left">
                    <form method="get" class="dcta-inline-form">
                        <input type="hidden" name="page" value="dynamic-cta-stats">
                        <input type="hidden" name="range" value="<?php echo esc_attr($range); ?>">
                        <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search click logs...', 'dynamic-cta-elementor'); ?>">
                        <button type="submit" class="button"><?php esc_html_e('Search', 'dynamic-cta-elementor'); ?></button>
                    </form>
                    <span style="color:#646970; font-size:12px; margin-left: 10px;">
                        ⚡ <?php esc_html_e('Anti-spam & automatic log pruning active (>60 days cleared to preserve speed).', 'dynamic-cta-elementor'); ?>
                    </span>
                </div>
                <div class="toolbar-right">
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
                        <input type="hidden" name="action" value="dynamic_cta_clear_stats">
                        <?php wp_nonce_field('dynamic_cta_clear_stats_nonce'); ?>
                        <button type="submit" class="button button-secondary button-link-delete btn-confirm-clear-stats">
                            <span class="dashicons dashicons-trash"></span> <?php esc_html_e('Clear Click History', 'dynamic-cta-elementor'); ?>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Logs Table -->
            <table class="wp-list-table widefat fixed striped table-view-list">
                <thead>
                    <tr>
                        <th style="width: 140px;"><?php esc_html_e('Date & Time', 'dynamic-cta-elementor'); ?></th>
                        <th><?php esc_html_e('Article Title', 'dynamic-cta-elementor'); ?></th>
                        <th style="width: 120px;"><?php esc_html_e('Detected Area', 'dynamic-cta-elementor'); ?></th>
                        <th><?php esc_html_e('Destination URL', 'dynamic-cta-elementor'); ?></th>
                        <th><?php esc_html_e('Referer', 'dynamic-cta-elementor'); ?></th>
                        <th style="width: 120px;"><?php esc_html_e('IP Address', 'dynamic-cta-elementor'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($clicks)): ?>
                        <tr>
                            <td colspan="6"><?php esc_html_e('No click records found.', 'dynamic-cta-elementor'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($clicks as $row): ?>
                            <tr>
                                <td><?php echo esc_html($row->click_date); ?></td>
                                <td>
                                    <?php if ($row->post_id > 0): ?>
                                        <a href="<?php echo esc_url(get_permalink($row->post_id)); ?>" target="_blank" rel="noopener noreferrer">
                                            <?php echo esc_html($row->post_title ? $row->post_title : 'Post #' . $row->post_id); ?>
                                        </a>
                                    <?php else: ?>
                                        <?php echo esc_html($row->post_title ? $row->post_title : '-'); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row->area_name): ?>
                                        <span class="dcta-badge"><?php echo esc_html($row->area_name); ?></span>
                                    <?php else: ?>
                                        <span class="dcta-badge dcta-badge-default"><?php esc_html_e('Default', 'dynamic-cta-elementor'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url($row->destination_url); ?>" target="_blank" rel="noopener noreferrer">
                                        <?php echo esc_html($row->destination_url); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php echo esc_html($row->referer ? $row->referer : '-'); ?>
                                </td>
                                <td><code><?php echo esc_html($row->ip_address ? $row->ip_address : '-'); ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?php printf(esc_html__('%d items', 'dynamic-cta-elementor'), $total_items); ?></span>
                        <?php
                        echo paginate_links([
                            'base'      => add_query_arg('paged', '%#%'),
                            'format'    => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total'     => $total_pages,
                            'current'   => $paged,
                        ]);
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
