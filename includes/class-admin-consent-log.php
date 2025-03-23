<?php
/**
 * Admin consent log list table
 *
 * @package    Simple_Cookie_Consent
 */

// Load WP_List_Table if not loaded
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Simple_Cookie_Consent_Admin_Log extends WP_List_Table {

    /** 
     * Consent storage instance
     *
     * @var Simple_Cookie_Consent_Storage
     */
    private $storage;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct([
            'singular' => 'consent',
            'plural'   => 'consents',
            'ajax'     => false
        ]);
        
        // Get storage instance
        global $simple_cookie_consent_storage;
        $this->storage = $simple_cookie_consent_storage;
    }

    /**
     * Initialize the class.
     */
    public function init() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_menu_page'));
        
        // Add export functionality
        add_action('admin_init', array($this, 'handle_export'));
    }

    /**
     * Add menu page
     */
    public function add_menu_page() {
        add_submenu_page(
            'options-general.php',
            __('Cookie Consent Log', 'simple-cookie-consent'),
            __('Consent Log', 'simple-cookie-consent'),
            'manage_options',
            'simple-cookie-consent-log',
            array($this, 'render_log_page')
        );
    }

    /**
     * Render log page
     */
    public function render_log_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'simple-cookie-consent'));
        }
        
        $this->prepare_items();
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html__('Cookie Consent Log', 'simple-cookie-consent'); ?></h1>
            
            <form method="post">
                <input type="hidden" name="page" value="simple-cookie-consent-log">
                <?php
                $this->search_box(__('Search Consents', 'simple-cookie-consent'), 'consent-search');
                wp_nonce_field('simple_cookie_consent_log_nonce', 'simple_cookie_consent_log_nonce');
                $this->display();
                ?>
            </form>
            
            <div class="tablenav bottom">
                <div class="alignleft actions">
                    <form method="post">
                        <?php wp_nonce_field('simple_cookie_consent_export_nonce', 'simple_cookie_consent_export_nonce'); ?>
                        <input type="hidden" name="action" value="export_consents">
                        <input type="submit" class="button button-primary" value="<?php esc_attr_e('Export to CSV', 'simple-cookie-consent'); ?>">
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Handle export action
     */
    public function handle_export() {
        if (!isset($_POST['action']) || $_POST['action'] !== 'export_consents') {
            return;
        }
        
        // Check nonce
        check_admin_referer('simple_cookie_consent_export_nonce', 'simple_cookie_consent_export_nonce');
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to export this data.', 'simple-cookie-consent'));
        }
        
        // Get all consents
        $consents = $this->storage->get_consents([
            'number' => 5000, // Limit to 5000 records
            'orderby' => 'created_at',
            'order' => 'DESC'
        ]);
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="cookie-consents-' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM
        fputs($output, "\xEF\xBB\xBF");
        
        // Add CSV headers
        fputcsv($output, [
            'ID',
            'User ID',
            'IP Address',
            'User Agent',
            'Consent Type',
            'Status',
            'Created At',
            'Updated At',
            'Necessary',
            'Preferences',
            'Analytics',
            'Marketing',
            'Social'
        ]);
        
        // Add data rows
        foreach ($consents as $consent) {
            // Extract consent details
            $details = $consent['consent_details'];
            
            fputcsv($output, [
                $consent['id'],
                $consent['user_id'] ? $consent['user_id'] : 'Guest',
                $consent['ip_address'],
                $consent['user_agent'],
                $consent['consent_type'],
                $consent['status'],
                $consent['created_at'],
                $consent['updated_at'],
                isset($details['necessary']) && $details['necessary'] ? 'Yes' : 'No',
                isset($details['preferences']) && $details['preferences'] ? 'Yes' : 'No',
                isset($details['analytics']) && $details['analytics'] ? 'Yes' : 'No',
                isset($details['marketing']) && $details['marketing'] ? 'Yes' : 'No',
                isset($details['social']) && $details['social'] ? 'Yes' : 'No'
            ]);
        }
        
        fclose($output);
        exit;
    }

    /**
     * Prepare items for the table
     */
    public function prepare_items() {
        // Set column headers
        $this->_column_headers = [
            $this->get_columns(),
            [], // Hidden columns
            $this->get_sortable_columns(),
            'id' // Primary column
        ];
        
        // Get search parameters
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        
        // Set up pagination
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;
        
        // Get items
        $args = [
            'number' => $per_page,
            'offset' => $offset,
            'orderby' => isset($_REQUEST['orderby']) ? sanitize_sql_orderby($_REQUEST['orderby']) : 'id',
            'order' => isset($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'DESC'
        ];
        
        // Add search conditions
        if (!empty($search)) {
            if (is_numeric($search)) {
                $args['user_id'] = (int) $search;
            } elseif (filter_var($search, FILTER_VALIDATE_IP)) {
                $args['ip_address'] = $search;
            } elseif (in_array($search, ['accepted', 'declined', 'essential_only'])) {
                $args['status'] = $search;
            }
        }
        
        $this->items = $this->storage->get_consents($args);
        
        // Set pagination args
        $total_items = $this->storage->count_consents();
        
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);
    }

    /**
     * Get columns
     *
     * @return array
     */
    public function get_columns() {
        return [
            'id' => __('ID', 'simple-cookie-consent'),
            'user_info' => __('User Information', 'simple-cookie-consent'),
            'consent_details' => __('Consent Details', 'simple-cookie-consent'),
            'status' => __('Status', 'simple-cookie-consent'),
            'timestamp' => __('Time', 'simple-cookie-consent')
        ];
    }

    /**
     * Get sortable columns
     *
     * @return array
     */
    public function get_sortable_columns() {
        return [
            'id' => ['id', true],
            'status' => ['status', false],
            'timestamp' => ['created_at', false]
        ];
    }

    /**
     * Column default
     *
     * @param array $item Item data
     * @param string $column_name Column name
     * @return string
     */
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'id':
                return $item['id'];
            
            case 'user_info':
                return $this->column_user_info($item);
            
            case 'consent_details':
                return $this->column_consent_details($item);
            
            case 'status':
                return $this->column_status($item);
            
            case 'timestamp':
                return $this->column_timestamp($item);
            
            default:
                return print_r($item, true);
        }
    }

    /**
     * User info column
     *
     * @param array $item Item data
     * @return string
     */
    private function column_user_info($item) {
        $output = '';
        
        // User ID if available
        if (!empty($item['user_id'])) {
            $user = get_userdata($item['user_id']);
            $output .= '<strong>' . esc_html__('User:', 'simple-cookie-consent') . '</strong> ';
            $output .= $user ? esc_html($user->display_name) . ' (#' . esc_html($item['user_id']) . ')' : esc_html('#' . $item['user_id']);
            $output .= '<br>';
        }
        
        // IP Address
        $output .= '<strong>' . esc_html__('IP:', 'simple-cookie-consent') . '</strong> ';
        $output .= esc_html($item['ip_address']) . '<br>';
        
        // User Agent (abbreviated)
        $user_agent = $item['user_agent'];
        if (strlen($user_agent) > 50) {
            $user_agent = substr($user_agent, 0, 47) . '...';
        }
        $output .= '<span title="' . esc_attr($item['user_agent']) . '">';
        $output .= '<strong>' . esc_html__('Browser:', 'simple-cookie-consent') . '</strong> ';
        $output .= esc_html($user_agent);
        $output .= '</span>';
        
        return $output;
    }

    /**
     * Consent details column
     *
     * @param array $item Item data
     * @return string
     */
    private function column_consent_details($item) {
        $details = $item['consent_details'];
        
        if (!is_array($details)) {
            return esc_html__('No details available', 'simple-cookie-consent');
        }
        
        $output = '<ul class="consent-details-list">';
        
        // Handle standard consent types
        $consent_types = Simple_Cookie_Consent::get_consent_types();
        
        foreach ($consent_types as $type) {
            $type_id = $type['id'];
            $status = isset($details[$type_id]) && $details[$type_id] ? 
                '<span class="dashicons dashicons-yes" style="color:green;"></span>' : 
                '<span class="dashicons dashicons-no" style="color:red;"></span>';
            
            $output .= '<li>' . $status . ' ' . esc_html($type['label']) . '</li>';
        }
        
        $output .= '</ul>';
        
        return $output;
    }

    /**
     * Status column
     *
     * @param array $item Item data
     * @return string
     */
    private function column_status($item) {
        $status = $item['status'];
        
        switch ($status) {
            case 'accepted':
                return '<span class="consent-status accepted">' . esc_html__('Accepted', 'simple-cookie-consent') . '</span>';
            
            case 'declined':
                return '<span class="consent-status declined">' . esc_html__('Declined', 'simple-cookie-consent') . '</span>';
            
            case 'essential_only':
                return '<span class="consent-status essential-only">' . esc_html__('Essential Only', 'simple-cookie-consent') . '</span>';
            
            default:
                return '<span class="consent-status">' . esc_html($status) . '</span>';
        }
    }

    /**
     * Timestamp column
     *
     * @param array $item Item data
     * @return string
     */
    private function column_timestamp($item) {
        $created = strtotime($item['created_at']);
        $updated = strtotime($item['updated_at']);
        
        $output = '<span title="' . esc_attr(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $created)) . '">';
        $output .= esc_html(human_time_diff($created, current_time('timestamp'))) . ' ' . esc_html__('ago', 'simple-cookie-consent');
        $output .= '</span>';
        
        // Show updated time if different
        if ($updated > $created) {
            $output .= '<br><small>';
            $output .= '<em>' . esc_html__('Updated:', 'simple-cookie-consent') . '</em> ';
            $output .= '<span title="' . esc_attr(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $updated)) . '">';
            $output .= esc_html(human_time_diff($updated, current_time('timestamp'))) . ' ' . esc_html__('ago', 'simple-cookie-consent');
            $output .= '</span>';
            $output .= '</small>';
        }
        
        return $output;
    }
}