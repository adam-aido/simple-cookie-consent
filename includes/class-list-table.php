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
        
        // Build query args
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
        
        // Get items from storage
        if ($this->storage && method_exists($this->storage, 'get_consents')) {
            $this->items = $this->storage->get_consents($args);
            
            // Set pagination args
            $total_items = $this->storage->count_consents();
            
            $this->set_pagination_args([
                'total_items' => $total_items,
                'per_page' => $per_page,
                'total_pages' => ceil($total_items / $per_page)
            ]);
        } else {
            $this->items = [];
            error_log('Storage not available or method get_consents not found');
            
            $this->set_pagination_args([
                'total_items' => 0,
                'per_page' => $per_page,
                'total_pages' => 0
            ]);
        }
        
        // Debug the results
        error_log('Consent items count: ' . count($this->items));
        if (!empty($this->items) && is_array($this->items)) {
            error_log('First item keys: ' . implode(', ', array_keys($this->items[0])));
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
     * Column default
     *
     * @param array $item Item data
     * @param string $column_name Column name
     * @return string
     */
    public function column_default($item, $column_name) {
        // Debug the item data for this column
        error_log("Rendering column $column_name for item ID: " . (isset($item['id']) ? $item['id'] : 'unknown'));
        
        switch ($column_name) {
            case 'id':
                return isset($item['id']) ? (int) $item['id'] : '';
            
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
     *
     * @param array $item Item data
     * @return string HTML output
     */
    private function column_user_info($item) {
        // Debug the item data
        error_log("User info column data: " . wp_json_encode(array_intersect_key($item, array_flip(['user_id', 'ip_address', 'user_agent']))));
        
        $output = '<div style="min-width: 200px;">';
        
        // User ID if available
        if (isset($item['user_id']) && !empty($item['user_id'])) {
            $user = get_userdata($item['user_id']);
            $output .= '<div style="margin-bottom: 5px;"><strong>' . esc_html__('User:', 'simple-cookie-consent') . '</strong> ';
            $output .= $user ? esc_html($user->display_name) . ' (#' . esc_html($item['user_id']) . ')' : esc_html('#' . $item['user_id']);
            $output .= '</div>';
        }
        
        // IP Address
        if (isset($item['ip_address']) && !empty($item['ip_address'])) {
            $output .= '<div style="margin-bottom: 5px;"><strong>' . esc_html__('IP:', 'simple-cookie-consent') . '</strong> ';
            $output .= esc_html($item['ip_address']);
            $output .= '</div>';
        }
        
        // User Agent (abbreviated)
        if (isset($item['user_agent']) && !empty($item['user_agent'])) {
            $user_agent = $item['user_agent'];
            if (strlen($user_agent) > 50) {
                $user_agent = substr($user_agent, 0, 47) . '...';
            }
            $output .= '<div style="margin-bottom: 5px;" title="' . esc_attr($item['user_agent']) . '">';
            $output .= '<strong>' . esc_html__('Browser:', 'simple-cookie-consent') . '</strong> ';
            $output .= esc_html($user_agent);
            $output .= '</div>';
        }
        
        if (strpos($output, '<strong>') === false) {
            $output .= esc_html__('No user information available', 'simple-cookie-consent');
        }
        
        $output .= '</div>';
        
        return $output;
    }

    /**
     * Consent details column
     *
     * @param array $item Item data
     * @return string HTML output
     */
    private function column_consent_details($item) {
        // Debug the consent details data
        if (isset($item['consent_details'])) {
            error_log("Consent details data: " . (is_array($item['consent_details']) ? wp_json_encode($item['consent_details']) : gettype($item['consent_details'])));
        } else {
            error_log("Consent details not set in item");
        }
        
        // Process the consent details data
        $details = null;
        
        // First, ensure we have some data to work with
        if (isset($item['consent_details'])) {
            // If it's already an array, use it directly
            if (is_array($item['consent_details'])) {
                $details = $item['consent_details'];
            } 
            // If it's a JSON string, try to decode it
            else if (is_string($item['consent_details'])) {
                try {
                    $decoded = json_decode($item['consent_details'], true);
                    if (is_array($decoded)) {
                        $details = $decoded;
                    }
                } catch (Exception $e) {
                    error_log('Error decoding consent details: ' . $e->getMessage());
                }
            }
        }
        
        // If we still don't have valid details, return a message
        if (!is_array($details) || empty($details)) {
            return '<div style="color: #666; font-style: italic;">' . esc_html__('No details available', 'simple-cookie-consent') . '</div>';
        }
        
        // Build the output for each consent type
        $output = '<div style="min-width: 200px;">';
        $output .= '<ul class="consent-details-list" style="margin: 0; padding-left: 20px;">';
        
        // Get consent types from the main class
        $consent_types = Simple_Cookie_Consent::get_consent_types();
        
        foreach ($consent_types as $type) {
            $type_id = $type['id'];
            $is_accepted = isset($details[$type_id]) && $details[$type_id] === true;
            
            $status_icon = $is_accepted 
                ? '<span class="dashicons dashicons-yes" style="color: green; font-size: 16px; vertical-align: text-bottom;"></span>' 
                : '<span class="dashicons dashicons-no" style="color: red; font-size: 16px; vertical-align: text-bottom;"></span>';
            
            $output .= '<li style="margin-bottom: 5px;">' . $status_icon . ' <span style="vertical-align: middle;">' . esc_html($type['label']) . '</span></li>';
        }
        
        $output .= '</ul>';
        $output .= '</div>';
        
        return $output;
    }

    /**
     * Status column
     *
     * @param array $item Item data
     * @return string HTML output
     */
    private function column_status($item) {
        // Debug the status data
        error_log("Status column data: " . (isset($item['status']) ? $item['status'] : 'not set'));
        
        if (!isset($item['status']) || empty($item['status'])) {
            return '<div style="color: #666; font-style: italic;">' . esc_html__('Unknown', 'simple-cookie-consent') . '</div>';
        }
        
        $status = $item['status'];
        $output = '<div style="min-width: 100px; text-align: center;">';
        
        switch ($status) {
            case 'accepted':
                $output .= '<span class="consent-status accepted" style="display: inline-block; padding: 4px 8px; background-color: #dff0d8; color: #3c763d; border-radius: 3px; font-weight: bold;">' . 
                    esc_html__('Accepted', 'simple-cookie-consent') . '</span>';
                break;
            
            case 'declined':
                $output .= '<span class="consent-status declined" style="display: inline-block; padding: 4px 8px; background-color: #f2dede; color: #a94442; border-radius: 3px; font-weight: bold;">' . 
                    esc_html__('Declined', 'simple-cookie-consent') . '</span>';
                break;
            
            case 'essential_only':
                $output .= '<span class="consent-status essential-only" style="display: inline-block; padding: 4px 8px; background-color: #fcf8e3; color: #8a6d3b; border-radius: 3px; font-weight: bold;">' . 
                    esc_html__('Essential Only', 'simple-cookie-consent') . '</span>';
                break;
            
            default:
                $output .= '<span class="consent-status" style="display: inline-block; padding: 4px 8px; background-color: #f5f5f5; color: #333; border-radius: 3px;">' . 
                    esc_html($status) . '</span>';
                break;
        }
        
        $output .= '</div>';
        
        return $output;
    }

    /**
     * Timestamp column
     *
     * @param array $item Item data
     * @return string HTML output
     */
    private function column_timestamp($item) {
        // Debug the timestamp data
        error_log("Timestamp column data: created_at=" . (isset($item['created_at']) ? $item['created_at'] : 'not set') . 
                  ", updated_at=" . (isset($item['updated_at']) ? $item['updated_at'] : 'not set'));
        
        if (!isset($item['created_at']) || empty($item['created_at'])) {
            return '<div style="color: #666; font-style: italic;">' . esc_html__('No timestamp available', 'simple-cookie-consent') . '</div>';
        }
        
        $created = strtotime($item['created_at']);
        $updated = isset($item['updated_at']) ? strtotime($item['updated_at']) : 0;
        
        $output = '<div style="min-width: 150px;">';
        
        // Format created time with both relative and absolute timestamps
        $created_absolute = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $created);
        $created_relative = human_time_diff($created, current_time('timestamp'));
        
        $output .= '<div style="margin-bottom: 3px;" title="' . esc_attr($created_absolute) . '">';
        $output .= '<span class="dashicons dashicons-calendar-alt" style="color: #0073aa; font-size: 16px; vertical-align: text-bottom;"></span> ';
        $output .= '<span style="vertical-align: middle;">' . esc_html($created_relative) . ' ' . esc_html__('ago', 'simple-cookie-consent') . '</span>';
        $output .= '</div>';
        
        // If updated time is different, show it too
        if ($updated > 0 && $updated > $created) {
            $updated_absolute = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $updated);
            $updated_relative = human_time_diff($updated, current_time('timestamp'));
            
            $output .= '<div style="margin-top: 3px; color: #777; font-size: 0.9em;" title="' . esc_attr($updated_absolute) . '">';
            $output .= '<span class="dashicons dashicons-update" style="color: #777; font-size: 14px; vertical-align: text-bottom;"></span> ';
            $output .= '<span style="vertical-align: middle;">' . esc_html__('Updated:', 'simple-cookie-consent') . ' ' . esc_html($updated_relative) . ' ' . esc_html__('ago', 'simple-cookie-consent') . '</span>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }

    /**
     * No items found text
     */
    public function no_items() {
        esc_html_e('No consent records found.', 'simple-cookie-consent');
    }

    /**
     * Display the table
     * Overridden to add extra debugging
     */
    public function display() {
        $singular = $this->_args['singular'];

        $this->display_tablenav('top');

        $this->screen->render_screen_reader_content('heading_list');
        ?>
        <table class="wp-list-table <?php echo implode(' ', $this->get_table_classes()); ?>">
            <thead>
            <tr>
                <?php $this->print_column_headers(); ?>
            </tr>
            </thead>

            <tbody id="the-list"<?php if ($singular) { echo " data-wp-lists='list:$singular'"; } ?>>
                <?php 
                // Debug the items before display
                error_log('Total items to display: ' . count($this->items));
                $this->display_rows_or_placeholder(); 
                ?>
            </tbody>

            <tfoot>
            <tr>
                <?php $this->print_column_headers(false); ?>
            </tr>
            </tfoot>
        </table>
        <?php
        $this->display_tablenav('bottom');
    }

    /**
     * Display rows or placeholder
     * Overridden to add debugging
     */
    public function display_rows_or_placeholder() {
        if (empty($this->items)) {
            echo '<tr class="no-items"><td class="colspanchange" colspan="' . $this->get_column_count() . '">';
            $this->no_items();
            echo '</td></tr>';
            return;
        }

        // Display each row
        foreach ($this->items as $item_index => $item) {
            error_log("Displaying row for item index: $item_index");
            $this->single_row($item);
        }
    }

    /**
     * Display a single row
     * Overridden to add debugging
     */
    public function single_row($item) {
        echo '<tr>';
        $this->single_row_columns($item);
        echo '</tr>';
    }
}