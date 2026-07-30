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
    public static function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'dynamic-cta-elementor'));
        }

        $summary = Stats::get_summary();
        $paged   = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
        $search  = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $limit   = 20;
        $offset  = ($paged - 1) * $limit;

        $logs_data = Stats::get_clicks($limit, $offset, $search);
        $total_items = $logs_data['total'];
        $total_pages = ceil($total_items / $limit);
        $clicks      = $logs_data['results'];
        ?>
        <div class="wrap dynamic-cta-wrap">
            <h1>
                <span class="dashicons dashicons-chart-bar"></span>
                <?php esc_html_e('Dynamic CTA - Click Statistics', 'dynamic-cta-elementor'); ?>
            </h1>

            <hr class="wp-header-end">

            <?php if (isset($_GET['cleared']) && $_GET['cleared'] === '1'): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e('Click statistics log cleared successfully!', 'dynamic-cta-elementor'); ?></p>
                </div>
            <?php endif; ?>

            <!-- Metrics Summary Grid -->
            <div class="dcta-stats-grid">
                <div class="dcta-metric-card">
                    <div class="metric-icon"><span class="dashicons dashicons-chart-line"></span></div>
                    <div class="metric-data">
                        <div class="metric-value"><?php echo esc_html(number_format_i18n($summary['total_clicks'])); ?></div>
                        <div class="metric-label"><?php esc_html_e('Total Clicks', 'dynamic-cta-elementor'); ?></div>
                    </div>
                </div>

                <div class="dcta-metric-card">
                    <div class="metric-icon"><span class="dashicons dashicons-calendar-alt"></span></div>
                    <div class="metric-data">
                        <div class="metric-value"><?php echo esc_html(number_format_i18n($summary['today_clicks'])); ?></div>
                        <div class="metric-label"><?php esc_html_e("Today's Clicks", 'dynamic-cta-elementor'); ?></div>
                    </div>
                </div>

                <div class="dcta-metric-card">
                    <div class="metric-icon"><span class="dashicons dashicons-location"></span></div>
                    <div class="metric-data">
                        <div class="metric-value"><?php echo esc_html($summary['top_area']); ?></div>
                        <div class="metric-label"><?php esc_html_e('Top Performing Area', 'dynamic-cta-elementor'); ?></div>
                    </div>
                </div>

                <div class="dcta-metric-card">
                    <div class="metric-icon"><span class="dashicons dashicons-admin-post"></span></div>
                    <div class="metric-data">
                        <div class="metric-value"><?php echo esc_html($summary['top_post']); ?></div>
                        <div class="metric-label"><?php esc_html_e('Top Article', 'dynamic-cta-elementor'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Toolbar & Search -->
            <div class="dynamic-cta-toolbar margin-top-20">
                <div class="toolbar-left">
                    <form method="get" class="dcta-inline-form">
                        <input type="hidden" name="page" value="dynamic-cta-stats">
                        <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search click logs...', 'dynamic-cta-elementor'); ?>">
                        <button type="submit" class="button"><?php esc_html_e('Search', 'dynamic-cta-elementor'); ?></button>
                    </form>
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
