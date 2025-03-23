<?php
/**
 * List Table class for consent logs
 *
 * @package    Simple_Cookie_Consent
 */

// Make sure WP_List_Table exists
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Simple_Cookie_Consent_List_Table extends WP_List_Table {

    /**
     * Storage instance
     *
     * @var Simple_Cookie_Consent_Storage
     */
    private $storage;

    /**
     * Constructor
     */
    public function __construct($storage) {
        parent::__construct([
            'singular' => 'consent',
            'plural'   => 'consents',
            'ajax'     => false
        ]);
        
        $this->storage = $storage;
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
        
        $this->items = array();
        
        // Get items from storage
        if ($this->storage && method_exists($this->storage, 'get_consents')) {
            $this->items = $this->storage->get_consents($args);
            
            // Debug the items
            if (is_array($this->items) && !empty($this->items)) {
                error_log('First item keys: ' . implode(', ', array_keys($this->items[0])));
            } else {
                error_log('No items returned from get_consents');
            }
            
            // Set pagination args
            $total_items = $this->storage->count_consents();
            
            $this->set_pagination_args([
                'total_items' => $total_items,
                'per_page' => $per_page,
                'total_pages' => ceil($total_items / $per_page)
            ]);
        } else {
            error_log('Storage not available or method get_consents not found');
            
            $this->set_pagination_args([
                'total_items' => 0,
                'per_page' => $per_page,
                'total_pages' => 0
            ]);
        }
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
     * Column default - first check if method exists
     */
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'id':
                return isset($item['id']) ? absint($item['id']) : '';
            
            case 'user_info':
                return $this->column_user_info($item);
            
            case 'consent_details':
                return $this->column_consent_details($item);
            
            case 'status':
                return $this->column_status($item);
            
            case 'timestamp':
                return $this->column_timestamp($item);
            
            default:
                return isset($item[$column_name]) ? esc_html($item[$column_name]) : '';
        }
    }

    /**
     * User info column
     */
    public function column_user_info($item) {
        $output = '';
        
        if (isset($item['user_id']) && !empty($item['user_id'])) {
            $user = get_userdata($item['user_id']);
            $output .= '<strong>' . esc_html__('User:', 'simple-cookie-consent') . '</strong> ';
            $output .= $user ? esc_html($user->display_name) . ' (#' . esc_html($item['user_id']) . ')' : esc_html('#' . $item['user_id']);
            $output .= '<br>';
        }
        
        if (isset($item['ip_address']) && !empty($item['ip_address'])) {
            $output .= '<strong>' . esc_html__('IP:', 'simple-cookie-consent') . '</strong> ';
            $output .= esc_html($item['ip_address']);
            $output .= '<br>';
        }
        
        if (isset($item['user_agent']) && !empty($item['user_agent'])) {
            $user_agent = $item['user_agent'];
            if (strlen($user_agent) > 50) {
                $user_agent = substr($user_agent, 0, 47) . '...';
            }
            $output .= '<strong>' . esc_html__('Browser:', 'simple-cookie-consent') . '</strong> ';
            $output .= esc_html($user_agent);
        }
        
        if (empty($output)) {
            $output = esc_html__('No user information available', 'simple-cookie-consent');
        }
        
        return $output;
    }

    /**
     * Consent details column
     */
    public function column_consent_details($item) {
        $output = '';
        
        $details = null;
        
        // Check if consent_details exists and is populated
        if (isset($item['consent_details'])) {
            if (is_array($item['consent_details'])) {
                $details = $item['consent_details'];
            } elseif (is_string($item['consent_details']) && !empty($item['consent_details'])) {
                // Try to decode JSON
                $decoded = json_decode($item['consent_details'], true);
                if (is_array($decoded)) {
                    $details = $decoded;
                }
            }
        }
        
        if (!is_array($details) || empty($details)) {
            return esc_html__('No consent details available', 'simple-cookie-consent');
        }
        
        $output .= '<ul class="consent-details-list" style="margin: 0; padding-left: 20px;">';
        
        // Get all available consent types
        $consent_types = Simple_Cookie_Consent::get_consent_types();
        
        // Display each consent type status
        foreach ($consent_types as $type) {
            $type_id = $type['id'];
            
            $is_accepted = isset($details[$type_id]) && $details[$type_id] === true;
            
            $status_icon = $is_accepted 
                ? '<span class="dashicons dashicons-yes" style="color: green;"></span>' 
                : '<span class="dashicons dashicons-no" style="color: red;"></span>';
            
            $output .= '<li>' . $status_icon . ' ' . esc_html($type['label']) . '</li>';
        }
        
        $output .= '</ul>';
        
        return $output;
    }

    /**
     * Status column
     */
    public function column_status($item) {
        if (!isset($item['status']) || empty($item['status'])) {
            return esc_html__('Unknown', 'simple-cookie-consent');
        }
        
        $status = sanitize_text_field($item['status']);
        
        switch ($status) {
            case 'accepted':
                return '<span class="consent-status accepted" style="color: green; font-weight: bold;">' . 
                    esc_html__('Accepted', 'simple-cookie-consent') . '</span>';
            
            case 'declined':
                return '<span class="consent-status declined" style="color: red; font-weight: bold;">' . 
                    esc_html__('Declined', 'simple-cookie-consent') . '</span>';
            
            case 'essential_only':
                return '<span class="consent-status essential-only" style="color: orange; font-weight: bold;">' . 
                    esc_html__('Essential Only', 'simple-cookie-consent') . '</span>';
            
            default:
                return '<span class="consent-status">' . esc_html($status) . '</span>';
        }
    }

    /**
     * Timestamp column
     */
    public function column_timestamp($item) {
        if (!isset($item['created_at']) || empty($item['created_at'])) {
            return esc_html__('No timestamp available', 'simple-cookie-consent');
        }
        
        $created = strtotime($item['created_at']);
        
        $created_date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $created);
        $human_time = human_time_diff($created, current_time('timestamp'));
        
        $output = '<span title="' . esc_attr($created_date) . '">';
        $output .= esc_html($human_time) . ' ' . esc_html__('ago', 'simple-cookie-consent');
        $output .= '</span>';
        
        // Add updated time if available and different
        if (isset($item['updated_at']) && !empty($item['updated_at']) && $item['updated_at'] !== $item['created_at']) {
            $updated = strtotime($item['updated_at']);
            $updated_date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $updated);
            $updated_human_time = human_time_diff($updated, current_time('timestamp'));
            
            $output .= '<br><small>';
            $output .= '<em>' . esc_html__('Updated:', 'simple-cookie-consent') . '</em> ';
            $output .= '<span title="' . esc_attr($updated_date) . '">';
            $output .= esc_html($updated_human_time) . ' ' . esc_html__('ago', 'simple-cookie-consent');
            $output .= '</span>';
            $output .= '</small>';
        }
        
        return $output;
    }

    /**
     * No items found text
     */
    public function no_items() {
        esc_html_e('No consent records found.', 'simple-cookie-consent');
    }
    
    /**
     * Get table classes
     * Override to add our custom class
     */
    protected function get_table_classes() {
        $classes = parent::get_table_classes();
        $classes[] = 'simple-cookie-consent-table';
        return $classes;
    }
}