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
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_menu_page'));
        
        // Add export functionality
        add_action('admin_init', array($this, 'handle_export'));
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
}