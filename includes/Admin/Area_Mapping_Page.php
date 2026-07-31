<?php
namespace DynamicCTA\Admin;

use DynamicCTA\DB;
use DynamicCTA\CTA_Resolver;
use DynamicCTA\Scanner;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Area_Mapping_Page
 * Renders Area Mapping Admin Interface displaying Source URL (Web Saat Ini) & Destination URL (Web Tujuan).
 */
class Area_Mapping_Page {

    /**
     * Initialize AJAX and CSV Export hooks
     */
    public static function init_hooks(): void {
        add_action('wp_ajax_dynamic_cta_save_mapping', [self::class, 'ajax_save_mapping']);
        add_action('wp_ajax_dynamic_cta_delete_mapping', [self::class, 'ajax_delete_mapping']);
        add_action('wp_ajax_dynamic_cta_clear_all_mappings', [self::class, 'ajax_clear_all_mappings']);
        add_action('wp_ajax_dynamic_cta_import_destination_sitemap', [self::class, 'ajax_import_destination_sitemap']);
        add_action('wp_ajax_dynamic_cta_import_csv', [self::class, 'ajax_import_csv']);
        add_action('admin_init', [self::class, 'handle_csv_export']);
        add_action('admin_init', [self::class, 'handle_bulk_actions']);
    }

    /**
     * Render Area Mapping Admin Page
     */
    public static function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'dynamic-cta-elementor'));
        }

        // Auto Ensure Database Columns exist on page load
        DB::ensure_schema_up_to_date();

        $table = new Area_Mapping_Table();
        $table->prepare_items();
        ?>
        <div class="wrap dynamic-cta-wrap">
            <h1 class="wp-heading-inline">
                <span class="dashicons dashicons-location-alt"></span>
                <?php esc_html_e('Dynamic CTA - Area Mapping', 'dynamic-cta-elementor'); ?>
            </h1>

            <hr class="wp-header-end">

            <!-- Alert Box Container -->
            <div id="dynamic-cta-notice" class="notice notice-info is-dismissible" style="display:none;">
                <p></p>
            </div>

            <!-- Action Toolbar -->
            <div class="dynamic-cta-toolbar">
                <div class="toolbar-left">
                    <button type="button" class="button button-primary btn-open-add-modal">
                        <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e('Add New Mapping', 'dynamic-cta-elementor'); ?>
                    </button>
                    <button type="button" class="button button-secondary btn-open-dest-sitemap-modal">
                        <span class="dashicons dashicons-sitemap"></span> <?php esc_html_e('Import Destination Sitemap', 'dynamic-cta-elementor'); ?>
                    </button>
                    <button type="button" class="button button-secondary button-link-delete btn-clear-all-mappings">
                        <span class="dashicons dashicons-trash"></span> <?php esc_html_e('Clear All Mappings', 'dynamic-cta-elementor'); ?>
                    </button>
                </div>
                <div class="toolbar-right">
                    <button type="button" class="button button-secondary btn-open-import-modal">
                        <span class="dashicons dashicons-upload"></span> <?php esc_html_e('Import CSV', 'dynamic-cta-elementor'); ?>
                    </button>
                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=dynamic-cta&action=dynamic_cta_export_csv'), 'dynamic_cta_export_nonce')); ?>" class="button button-secondary">
                        <span class="dashicons dashicons-download"></span> <?php esc_html_e('Export CSV', 'dynamic-cta-elementor'); ?>
                    </a>
                </div>
            </div>

            <!-- Search and Table Form -->
            <form method="post" id="mapping-list-form">
                <?php
                wp_nonce_field('dynamic_cta_bulk_nonce', 'bulk_nonce');
                $table->search_box(__('Search Mappings', 'dynamic-cta-elementor'), 'mapping-search');
                $table->display();
                ?>
            </form>
        </div>

        <!-- Add / Edit Modal -->
        <div id="modal-mapping-form" class="dcta-modal-overlay" style="display:none;">
            <div class="dcta-modal-container">
                <div class="dcta-modal-header">
                    <h3 id="modal-form-title"><?php esc_html_e('Add Area Mapping', 'dynamic-cta-elementor'); ?></h3>
                    <button type="button" class="dcta-modal-close">&times;</button>
                </div>
                <div class="dcta-modal-body">
                    <form id="dcta-save-form">
                        <input type="hidden" name="mapping_id" id="field-mapping-id" value="0">
                        
                        <div class="dcta-form-group">
                            <label for="field-keyword"><strong><?php esc_html_e('Keyword', 'dynamic-cta-elementor'); ?> *</strong></label>
                            <input type="text" name="keyword" id="field-keyword" class="regular-text" placeholder="e.g. bandung" required>
                            <p class="description"><?php esc_html_e('Word to match against post slug, category, or tag (e.g. bandung).', 'dynamic-cta-elementor'); ?></p>
                        </div>

                        <div class="dcta-form-group">
                            <label for="field-area-name"><strong><?php esc_html_e('Area Name', 'dynamic-cta-elementor'); ?> *</strong></label>
                            <input type="text" name="area_name" id="field-area-name" class="regular-text" placeholder="e.g. Bandung" required>
                            <p class="description"><?php esc_html_e('Human readable area name (e.g. Bandung).', 'dynamic-cta-elementor'); ?></p>
                        </div>

                        <div class="dcta-form-group">
                            <label for="field-source-url"><strong><?php esc_html_e('Source URL (Web Saat Ini)', 'dynamic-cta-elementor'); ?></strong></label>
                            <input type="url" name="source_url" id="field-source-url" class="large-text" placeholder="e.g. https://iconnet.biz.id/pasang-iconnet-bandung/">
                            <p class="description"><?php esc_html_e('Sample URL on current website for reference.', 'dynamic-cta-elementor'); ?></p>
                        </div>

                        <div class="dcta-form-group">
                            <label for="field-destination-url"><strong><?php esc_html_e('Destination URL (Web Tujuan)', 'dynamic-cta-elementor'); ?> *</strong></label>
                            <input type="url" name="destination_url" id="field-destination-url" class="large-text" placeholder="https://jasawifi.com/iconnet/bandung/" required>
                            <p class="description"><?php esc_html_e('Target destination URL where traffic will be routed.', 'dynamic-cta-elementor'); ?></p>
                        </div>
                    </form>
                </div>
                <div class="dcta-modal-footer">
                    <button type="button" class="button button-secondary dcta-modal-close"><?php esc_html_e('Cancel', 'dynamic-cta-elementor'); ?></button>
                    <button type="button" class="button button-primary btn-save-mapping-submit"><?php esc_html_e('Save Mapping', 'dynamic-cta-elementor'); ?></button>
                </div>
            </div>
        </div>

        <!-- Import Destination Sitemap Modal -->
        <div id="modal-dest-sitemap" class="dcta-modal-overlay" style="display:none;">
            <div class="dcta-modal-container">
                <div class="dcta-modal-header">
                    <h3><?php esc_html_e('Import Destination Website Sitemap', 'dynamic-cta-elementor'); ?></h3>
                    <button type="button" class="dcta-modal-close">&times;</button>
                </div>
                <div class="dcta-modal-body">
                    <form id="dcta-dest-sitemap-form">
                        <div class="dcta-form-group">
                            <label for="field-dest-sitemap-url"><strong><?php esc_html_e('Destination Sitemap XML URL', 'dynamic-cta-elementor'); ?> *</strong></label>
                            <input type="url" name="dest_sitemap_url" id="field-dest-sitemap-url" class="large-text" placeholder="e.g. https://jasawifi.com/post-sitemap.xml" required>
                            <p class="description">
                                💡 <?php esc_html_e('Paste the XML sitemap URL of your NEW destination website (e.g. https://jasawifi.com/post-sitemap.xml). The plugin will automatically extract all landing URLs and match keywords instantly!', 'dynamic-cta-elementor'); ?>
                            </p>
                        </div>
                    </form>
                </div>
                <div class="dcta-modal-footer">
                    <button type="button" class="button button-secondary dcta-modal-close"><?php esc_html_e('Cancel', 'dynamic-cta-elementor'); ?></button>
                    <button type="button" class="button button-primary btn-submit-dest-sitemap"><?php esc_html_e('Fetch & Import URLs', 'dynamic-cta-elementor'); ?></button>
                </div>
            </div>
        </div>

        <!-- Import CSV Modal -->
        <div id="modal-import-csv" class="dcta-modal-overlay" style="display:none;">
            <div class="dcta-modal-container">
                <div class="dcta-modal-header">
                    <h3><?php esc_html_e('Import Area Mappings (CSV)', 'dynamic-cta-elementor'); ?></h3>
                    <button type="button" class="dcta-modal-close">&times;</button>
                </div>
                <div class="dcta-modal-body">
                    <form id="dcta-import-form" enctype="multipart/form-data">
                        <div class="dcta-form-group">
                            <label for="field-csv-file"><strong><?php esc_html_e('Select CSV File', 'dynamic-cta-elementor'); ?> *</strong></label>
                            <input type="file" name="csv_file" id="field-csv-file" accept=".csv" required>
                            <p class="description">
                                <?php esc_html_e('CSV header: keyword,area_name,source_url,destination_url', 'dynamic-cta-elementor'); ?>
                            </p>
                        </div>
                    </form>
                </div>
                <div class="dcta-modal-footer">
                    <button type="button" class="button button-secondary dcta-modal-close"><?php esc_html_e('Cancel', 'dynamic-cta-elementor'); ?></button>
                    <button type="button" class="button button-primary btn-submit-import"><?php esc_html_e('Upload & Import', 'dynamic-cta-elementor'); ?></button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Handle Bulk Actions (Bulk Delete)
     */
    public static function handle_bulk_actions(): void {
        if (!isset($_POST['action']) && !isset($_POST['action2'])) {
            return;
        }

        $action = isset($_POST['action']) && $_POST['action'] !== '-1' ? sanitize_text_field($_POST['action']) : '';
        if (empty($action) && isset($_POST['action2']) && $_POST['action2'] !== '-1') {
            $action = sanitize_text_field($_POST['action2']);
        }

        if ($action === 'bulk-delete' && isset($_POST['mapping_id'])) {
            check_admin_referer('dynamic_cta_bulk_nonce', 'bulk_nonce');

            if (!current_user_can('manage_options')) {
                wp_die(__('Unauthorized action.', 'dynamic-cta-elementor'));
            }

            global $wpdb;
            $table = DB::get_mappings_table();
            $ids = array_map('intval', (array) $_POST['mapping_id']);

            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '%d'));
                $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE id IN ({$placeholders})", $ids));
                CTA_Resolver::clear_cache();
            }
        }
    }

    /**
     * AJAX Save Mapping (Add / Edit)
     */
    public static function ajax_save_mapping(): void {
        check_ajax_referer('dynamic_cta_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized permission.', 'dynamic-cta-elementor')]);
        }

        DB::ensure_schema_up_to_date();

        $id              = isset($_POST['mapping_id']) ? (int) $_POST['mapping_id'] : 0;
        $keyword         = isset($_POST['keyword']) ? sanitize_key($_POST['keyword']) : '';
        $area_name       = isset($_POST['area_name']) ? sanitize_text_field($_POST['area_name']) : '';
        $source_url      = isset($_POST['source_url']) ? esc_url_raw($_POST['source_url']) : '';
        $destination_url = isset($_POST['destination_url']) ? esc_url_raw($_POST['destination_url']) : '';

        if (empty($keyword) || empty($area_name) || empty($destination_url)) {
            wp_send_json_error(['message' => __('Please fill all required fields.', 'dynamic-cta-elementor')]);
        }

        global $wpdb;
        $table = DB::get_mappings_table();

        if ($id > 0) {
            $duplicate = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE keyword = %s AND id != %d", $keyword, $id));
        } else {
            $duplicate = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE keyword = %s", $keyword));
        }

        if ($duplicate) {
            wp_send_json_error(['message' => sprintf(__('Keyword "%s" already exists in Area Mapping.', 'dynamic-cta-elementor'), $keyword)]);
        }

        if ($id > 0) {
            $wpdb->update(
                $table,
                [
                    'keyword'         => $keyword,
                    'area_name'       => $area_name,
                    'source_url'      => $source_url,
                    'destination_url' => $destination_url,
                ],
                ['id' => $id],
                ['%s', '%s', '%s', '%s'],
                ['%d']
            );
            $msg = __('Mapping updated successfully.', 'dynamic-cta-elementor');
        } else {
            $wpdb->insert(
                $table,
                [
                    'keyword'         => $keyword,
                    'area_name'       => $area_name,
                    'source_url'      => $source_url,
                    'destination_url' => $destination_url,
                ],
                ['%s', '%s', '%s', '%s']
            );
            $msg = __('New area mapping added successfully.', 'dynamic-cta-elementor');
        }

        CTA_Resolver::clear_cache();
        wp_send_json_success(['message' => $msg]);
    }

    /**
     * AJAX Delete Single Mapping
     */
    public static function ajax_delete_mapping(): void {
        check_ajax_referer('dynamic_cta_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized permission.', 'dynamic-cta-elementor')]);
        }

        global $wpdb;
        $table = DB::get_mappings_table();
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) {
            wp_send_json_error(['message' => __('Invalid Mapping ID.', 'dynamic-cta-elementor')]);
        }

        $wpdb->delete($table, ['id' => $id], ['%d']);

        CTA_Resolver::clear_cache();
        wp_send_json_success(['message' => __('Mapping deleted successfully.', 'dynamic-cta-elementor')]);
    }

    /**
     * AJAX Clear All Mappings
     */
    public static function ajax_clear_all_mappings(): void {
        check_ajax_referer('dynamic_cta_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized permission.', 'dynamic-cta-elementor')]);
        }

        Scanner::truncate_mappings();
        wp_send_json_success(['message' => __('All area mappings cleared successfully.', 'dynamic-cta-elementor')]);
    }

    /**
     * AJAX Import Destination Sitemap
     */
    public static function ajax_import_destination_sitemap(): void {
        check_ajax_referer('dynamic_cta_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized permission.', 'dynamic-cta-elementor')]);
        }

        DB::ensure_schema_up_to_date();

        $sitemap_url = isset($_POST['sitemap_url']) ? esc_url_raw($_POST['sitemap_url']) : '';
        if (empty($sitemap_url)) {
            wp_send_json_error(['message' => __('Please enter a valid Destination Sitemap URL.', 'dynamic-cta-elementor')]);
        }

        $result = Scanner::import_destination_sitemap($sitemap_url);

        if (isset($result['error'])) {
            wp_send_json_error(['message' => $result['error']]);
        }

        $message = sprintf(
            __('Destination Sitemap Import Complete! Total URLs scanned: %d. Mappings processed/inserted: %d.', 'dynamic-cta-elementor'),
            $result['total_scanned'],
            $result['inserted'] + $result['updated']
        );

        wp_send_json_success(['message' => $message]);
    }

    /**
     * AJAX CSV Import
     */
    public static function ajax_import_csv(): void {
        check_ajax_referer('dynamic_cta_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized permission.', 'dynamic-cta-elementor')]);
        }

        DB::ensure_schema_up_to_date();

        if (empty($_FILES['csv_file']['tmp_name'])) {
            wp_send_json_error(['message' => __('No file uploaded.', 'dynamic-cta-elementor')]);
        }

        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        if (!$handle) {
            wp_send_json_error(['message' => __('Unable to read uploaded CSV file.', 'dynamic-cta-elementor')]);
        }

        global $wpdb;
        $table = DB::get_mappings_table();

        $header = fgetcsv($handle);
        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 3) {
                continue;
            }

            $keyword         = sanitize_key(trim($row[0]));
            $area_name       = sanitize_text_field(trim($row[1]));
            $source_url      = isset($row[2]) ? esc_url_raw(trim($row[2])) : '';
            $destination_url = isset($row[3]) ? esc_url_raw(trim($row[3])) : esc_url_raw(trim($row[2]));

            if (empty($keyword) || empty($area_name) || empty($destination_url)) {
                $skipped++;
                continue;
            }

            $wpdb->replace(
                $table,
                [
                    'keyword'         => $keyword,
                    'area_name'       => $area_name,
                    'source_url'      => $source_url,
                    'destination_url' => $destination_url,
                ],
                ['%s', '%s', '%s', '%s']
            );
            $imported++;
        }

        fclose($handle);
        CTA_Resolver::clear_cache();

        wp_send_json_success([
            'message' => sprintf(__('CSV Import complete! %d mappings processed/updated, %d skipped.', 'dynamic-cta-elementor'), $imported, $skipped)
        ]);
    }

    /**
     * Direct Export Mappings as CSV File
     */
    public static function handle_csv_export(): void {
        if (!isset($_GET['action']) || $_GET['action'] !== 'dynamic_cta_export_csv') {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized action.', 'dynamic-cta-elementor'));
        }

        check_admin_referer('dynamic_cta_export_nonce');

        DB::ensure_schema_up_to_date();

        global $wpdb;
        $table = DB::get_mappings_table();
        $results = $wpdb->get_results("SELECT keyword, area_name, source_url, destination_url FROM {$table} ORDER BY keyword ASC", ARRAY_A);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=dynamic_cta_mappings_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['keyword', 'area_name', 'source_url', 'destination_url']);

        if (is_array($results)) {
            foreach ($results as $row) {
                fputcsv($output, [$row['keyword'], $row['area_name'], $row['source_url'], $row['destination_url']]);
            }
        }

        fclose($output);
        exit;
    }
}
