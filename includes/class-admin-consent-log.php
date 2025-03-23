<?php
/**
 * Admin consent log list table
 *
 * @package    Simple_Cookie_Consent
 */

class Simple_Cookie_Consent_Admin_Log {

    /**
     * Storage instance
     *
     * @var Simple_Cookie_Consent_Storage
     */
    private $storage;

    /**
     * List table instance
     *
     * @var Simple_Cookie_Consent_List_Table
     */
    private $list_table;

    /**
     * Initialize the class.
     */
    public function init() {
        global $simple_cookie_consent_storage;
        $this->storage = $simple_cookie_consent_storage;
        
        // Verify storage initialization
        if (!$this->storage) {
            error_log('Storage not initialized in admin log');
        } else {
            error_log('Storage initialized in admin log');
        }
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_menu_page'));
        
        // Add export functionality
        add_action('admin_init', array($this, 'handle_export'));
        
        // Add admin styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
    }
    
    /**
     * Enqueue admin styles
     */
    public function enqueue_admin_styles($hook) {
        // Only load on our page
        if (strpos($hook, 'simple-cookie-consent-log') === false) {
            return;
        }
        
        // Add inline styles
        wp_add_inline_style('list-tables', '
            .wp-list-table .column-user_info { width: 25%; }
            .wp-list-table .column-consent_details { width: 30%; }
            .wp-list-table .column-status { width: 15%; }
            .wp-list-table .column-timestamp { width: 20%; }
            
            .consent-details-list li {
                margin-bottom: 5px;
            }
            
            .consent-status {
                display: inline-block;
                padding: 4px 8px;
                border-radius: 3px;
                font-weight: bold;
                text-align: center;
            }
            
            .consent-status.accepted {
                background-color: #dff0d8;
                color: #3c763d;
            }
            
            .consent-status.declined {
                background-color: #f2dede;
                color: #a94442;
            }
            
            .consent-status.essential-only {
                background-color: #fcf8e3;
                color: #8a6d3b;
            }
        ');
    }

    /**
     * Add menu page
     */
    public function add_menu_page() {
        $hook = add_submenu_page(
            'options-general.php',
            __('Cookie Consent Log', 'simple-cookie-consent'),
            __('Consent Log', 'simple-cookie-consent'),
            'manage_options',
            'simple-cookie-consent-log',
            array($this, 'render_log_page')
        );
        
        // Only load list table on our screen
        add_action('load-' . $hook, array($this, 'load_list_table'));
    }
    
    /**
     * Load list table class
     */
    public function load_list_table() {
        // This is the correct hook to load WP_List_Table
        require_once(SIMPLE_COOKIE_CONSENT_INCLUDES_DIR . 'class-list-table.php');
        
        // Debug storage availability
        if (!$this->storage) {
            error_log('Storage not available when loading list table');
            return;
        }
        
        // Check if table exists before initializing list table
        if (!$this->storage->table_exists()) {
            error_log('Consent table does not exist - creating table');
            $this->storage->create_tables();
        }
        
        $this->list_table = new Simple_Cookie_Consent_List_Table($this->storage);
        $this->list_table->prepare_items();
    }

    /**
     * Render log page
     */
    public function render_log_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'simple-cookie-consent'));
        }
        
        // Verify the list table is initialized
        if (!$this->list_table) {
            error_log('List table not initialized');
            echo '<div class="error notice"><p>';
            esc_html_e('Error: Could not initialize the consent log table. Please check the error log for details.', 'simple-cookie-consent');
            echo '</p></div>';
            return;
        }
        
        // Verify the storage is available
        if (!$this->storage) {
            error_log('Storage not available on render');
            echo '<div class="error notice"><p>';
            esc_html_e('Error: Consent storage is not available. Please check the error log for details.', 'simple-cookie-consent');
            echo '</p></div>';
            return;
        }
        
        // Verify the table exists
        if (!$this->storage->table_exists()) {
            error_log('Table does not exist on render');
            echo '<div class="error notice"><p>';
            esc_html_e('Error: Consent table does not exist in the database. Please deactivate and reactivate the plugin.', 'simple-cookie-consent');
            echo '</p></div>';
            return;
        }
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html__('Cookie Consent Log', 'simple-cookie-consent'); ?></h1>
            
            <form method="post">
                <input type="hidden" name="page" value="simple-cookie-consent-log">
                <?php
                $this->list_table->search_box(__('Search Consents', 'simple-cookie-consent'), 'consent-search');
                wp_nonce_field('simple_cookie_consent_log_nonce', 'simple_cookie_consent_log_nonce');
                $this->list_table->display();
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
        
        // Verify the storage is available
        if (!$this->storage) {
            wp_die(__('Error: Consent storage is not available. Please check the error log for details.', 'simple-cookie-consent'));
        }
        
        // Get all consents
        $consents = $this->storage->get_consents([
            'number' => 5000, // Limit to 5000 records
            'orderby' => 'created_at',
            'order' => 'DESC'
        ]);
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
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
        
        // Process each consent record
        $consent_types = ['necessary', 'preferences', 'analytics', 'marketing', 'social'];
        
        // Add data rows
        foreach ($consents as $consent) {
            // Extract consent details safely
            $details = isset($consent['consent_details']) ? $consent['consent_details'] : [];
            
            // Build the CSV row
            $row = [
                isset($consent['id']) ? $consent['id'] : '',
                isset($consent['user_id']) ? ($consent['user_id'] ? $consent['user_id'] : 'Guest') : 'Guest',
                isset($consent['ip_address']) ? $consent['ip_address'] : '',
                isset($consent['user_agent']) ? $consent['user_agent'] : '',
                isset($consent['consent_type']) ? $consent['consent_type'] : '',
                isset($consent['status']) ? $consent['status'] : '',
                isset($consent['created_at']) ? $consent['created_at'] : '',
                isset($consent['updated_at']) ? $consent['updated_at'] : '',
            ];
            
            // Add each consent type status
            foreach ($consent_types as $type) {
                $row[] = isset($details[$type]) && $details[$type] === true ? 'Yes' : 'No';
            }
            
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
}