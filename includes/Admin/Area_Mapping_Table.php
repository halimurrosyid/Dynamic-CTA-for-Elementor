<?php
namespace DynamicCTA\Admin;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class Area_Mapping_Table
 * Custom WP_List_Table for managing Area Mappings.
 */
class Area_Mapping_Table extends \WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => __('Mapping', 'dynamic-cta-elementor'),
            'plural'   => __('Mappings', 'dynamic-cta-elementor'),
            'ajax'     => false,
        ]);
    }

    public function get_columns(): array {
        return [
            'cb'              => '<input type="checkbox" />',
            'keyword'         => __('Keyword', 'dynamic-cta-elementor'),
            'area_name'       => __('Area Name', 'dynamic-cta-elementor'),
            'destination_url' => __('Destination URL', 'dynamic-cta-elementor'),
            'updated_at'      => __('Last Updated', 'dynamic-cta-elementor'),
            'actions'         => __('Actions', 'dynamic-cta-elementor'),
        ];
    }

    protected function get_sortable_columns(): array {
        return [
            'keyword'    => ['keyword', false],
            'area_name'  => ['area_name', false],
            'updated_at' => ['updated_at', true],
        ];
    }

    protected function column_cb($item): string {
        return sprintf('<input type="checkbox" name="mapping_id[]" value="%d" />', $item['id']);
    }

    protected function column_default($item, $column_name): string {
        switch ($column_name) {
            case 'keyword':
                return '<code>' . esc_html($item['keyword']) . '</code>';
            case 'area_name':
                return '<strong>' . esc_html($item['area_name']) . '</strong>';
            case 'destination_url':
                return '<a href="' . esc_url($item['destination_url']) . '" target="_blank" rel="noopener noreferrer">' . esc_html($item['destination_url']) . '</a>';
            case 'updated_at':
                return esc_html($item['updated_at']);
            case 'actions':
                $edit_btn = sprintf(
                    '<button type="button" class="button button-small btn-edit-mapping" data-id="%d" data-keyword="%s" data-area="%s" data-url="%s">%s</button>',
                    $item['id'],
                    esc_attr($item['keyword']),
                    esc_attr($item['area_name']),
                    esc_attr($item['destination_url']),
                    esc_html__('Edit', 'dynamic-cta-elementor')
                );
                $delete_btn = sprintf(
                    '<button type="button" class="button button-small button-link-delete btn-delete-mapping" data-id="%d">%s</button>',
                    $item['id'],
                    esc_html__('Delete', 'dynamic-cta-elementor')
                );
                return $edit_btn . ' ' . $delete_btn;
            default:
                return isset($item[$column_name]) ? esc_html($item[$column_name]) : '';
        }
    }

    public function get_bulk_actions(): array {
        return [
            'bulk-delete' => __('Delete', 'dynamic-cta-elementor'),
        ];
    }

    public function prepare_items(): void {
        global $wpdb;
        $table = \DynamicCTA\DB::get_mappings_table();

        $columns  = $this->get_columns();
        $hidden   = [];
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = [$columns, $hidden, $sortable];

        $per_page = 20;
        $current_page = $this->get_pagenum();

        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        $where = '';
        if (!empty($search)) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where = $wpdb->prepare(" WHERE keyword LIKE %s OR area_name LIKE %s OR destination_url LIKE %s ", $like, $like, $like);
        }

        $orderby = isset($_REQUEST['orderby']) ? sanitize_sql_orderby($_REQUEST['orderby']) : 'updated_at';
        $order   = (isset($_REQUEST['order']) && strtolower($_REQUEST['order']) === 'asc') ? 'ASC' : 'DESC';

        $total_items = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} {$where}");

        $offset = ($current_page - 1) * $per_page;
        $query  = $wpdb->prepare("SELECT * FROM {$table} {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", $per_page, $offset);

        $results = $wpdb->get_results($query, ARRAY_A);

        $this->items = is_array($results) ? $results : [];

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ]);
    }
}
