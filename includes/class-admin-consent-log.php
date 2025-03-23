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
        
        // Add inline styles for better table display
        wp_add_inline_style('list-tables', '
            .simple-cookie-consent-table .column-id { width: 5%; }
            .simple-cookie-consent-table .column-user_info { width: 25%; }
            .simple-cookie-consent-table .column-consent_details { width: 30%; }
            .simple-cookie-consent-table .column-status { width: 15%; }
            .simple-cookie-consent-table .column-timestamp { width: 20%; }
            
            .simple-cookie-consent-table .consent-details-list {
                margin: 0;
                padding-left: 20px;
            }
            
            .simple-cookie-consent-table .consent-details-list li {
                margin-bottom: 5px;
            }
            
            .simple-cookie-consent-table .dashicons-yes {
                color: green;
            }
            
            .simple-cookie-consent-table .dashicons-no {
                color: red;
            }
            
            .simple-cookie-consent-table .consent-status.accepted {
                color: green;
                font-weight: bold;
            }
            
            .simple-cookie-consent-table .consent-status.declined {
                color: red;
                font-weight: bold;
            }
            
            .simple-cookie-consent-table .consent-status.essential-only {
                color: orange;
                font-weight: bold;
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
        // Load the list table class
        require_once(SIMPLE_COOKIE_CONSENT_INCLUDES_DIR . 'class-list-table.php');
        
        // Create the list table
        $this->list_table = new Simple_Cookie_Consent_List_Table($this->storage);
        
        // Prepare list table items
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
        
        // Ensure list table is loaded
        if (!$this->list_table) {
            // Try to load it again
            $this->load_list_table();
            
            // If still not loaded, show error
            if (!$this->list_table) {
                echo '<div class="notice notice-error"><p>';
                esc_html_e('Error: Could not initialize the consent log table.', 'simple-cookie-consent');
                echo '</p></div>';
                return;
            }
        }
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html__('Cookie Consent Log', 'simple-cookie-consent'); ?></h1>
            
            <hr class="wp-header-end">
            
            <?php
            // Show sample data notice
            if (isset($_GET['show_sample']) && $_GET['show_sample'] === '1') {
                $this->generate_sample_data();
                echo '<div class="notice notice-success is-dismissible"><p>';
                esc_html_e('Sample data has been generated successfully!', 'simple-cookie-consent');
                echo '</p></div>';
            }
            ?>
            
            <div class="tablenav top">
                <div class="alignleft actions">
                    <a href="<?php echo esc_url(add_query_arg('show_sample', '1')); ?>" class="button">
                        <?php esc_html_e('Generate Sample Data', 'simple-cookie-consent'); ?>
                    </a>
                </div>
            </div>
            
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
                    <form method="post" id="export-form">
                        <?php wp_nonce_field('simple_cookie_consent_export_nonce', 'simple_cookie_consent_export_nonce'); ?>
                        <input type="hidden" name="action" value="export_consents">
                        <input type="submit" class="button button-primary" value="<?php esc_attr_e('Export to CSV', 'simple-cookie-consent'); ?>">
                    </form>
                </div>
            </div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Make notices dismissible
            var notices = document.querySelectorAll('.notice.is-dismissible');
            notices.forEach(function(notice) {
                var closeButton = document.createElement('button');
                closeButton.type = 'button';
                closeButton.className = 'notice-dismiss';
                closeButton.addEventListener('click', function() {
                    notice.parentNode.removeChild(notice);
                });
                notice.appendChild(closeButton);
            });
            
            // Handle export form submission
            var exportForm = document.getElementById('export-form');
            if (exportForm) {
                exportForm.addEventListener('submit', function(e) {
                    // You can add validation here if needed
                    // e.preventDefault(); - don't prevent default as we want the form to submit
                });
            }
        });
        </script>
        <?php
    }

    /**
     * Generate sample data for demo purposes
     */
    private function generate_sample_data() {
        if (!$this->storage || !method_exists($this->storage, 'store_consent')) {
            return;
        }
        
        // Sample consent types
        $consent_types = array(
            // Accept all
            array(
                'necessary' => true,
                'preferences' => true,
                'analytics' => true,
                'marketing' => true,
                'social' => true,
                'googleConsentMode' => true
            ),
            // Essential only
            array(
                'necessary' => true,
                'preferences' => false,
                'analytics' => false,
                'marketing' => false,
                'social' => false,
                'googleConsentMode' => true
            ),
            // Mixed
            array(
                'necessary' => true,
                'preferences' => true,
                'analytics' => true,
                'marketing' => false,
                'social' => false,
                'googleConsentMode' => true
            )
        );
        
        // Generate 5 sample records
        for ($i = 0; $i < 5; $i++) {
            $consent_index = $i % 3; // Cycle through the 3 sample consent types
            $this->storage->store_consent(true, $consent_types[$consent_index]);
        }
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