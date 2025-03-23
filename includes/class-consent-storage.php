<?php
/**
 * Database consent storage functionality.
 *
 * @package    Simple_Cookie_Consent
 */

class Simple_Cookie_Consent_Storage {

    /**
     * Database table name
     *
     * @var string
     */
    private $table_name;

    /**
     * Initialize the class.
     */
    public function init() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'cookie_consents';

        // Register activation hook for creating the table
        add_action('simple_cookie_consent_create_tables', array($this, 'create_tables'));
        
        // Ajax handler for storing consent in database
        add_action('wp_ajax_simple_cookie_set_consent', array($this, 'ajax_store_consent'));
        add_action('wp_ajax_nopriv_simple_cookie_set_consent', array($this, 'ajax_store_consent'));
    }

    /**
     * Check if the consent table exists in the database
     *
     * @return bool Whether the table exists
     */
    public function table_exists() {
        global $wpdb;
        
        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $this->table_name
            )
        );
        
        return !empty($result);
    }

    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            ip_address varchar(100) NOT NULL,
            user_agent text NOT NULL,
            consent_type varchar(50) NOT NULL,
            consent_details longtext NOT NULL,
            status varchar(20) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY ip_address (ip_address),
            KEY consent_type (consent_type),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Debug log
        error_log('Cookie consent table created: ' . $this->table_name);
    }

    /**
     * AJAX handler for storing consent in database
     */
    public function ajax_store_consent() {
        // Verify nonce
        check_ajax_referer('simple_cookie_consent_nonce', 'nonce');

        $consent_accepted = isset($_POST['accepted']) && $_POST['accepted'] ? true : false;
        
        // Only proceed if details are provided and are an array
        if (isset($_POST['details']) && is_array($_POST['details'])) {
            // Sanitize consent details
            $sanitized_details = $this->sanitize_consent_details($_POST['details']);
            
            // Save consent to database
            $result = $this->store_consent($consent_accepted, $sanitized_details);
            
            if ($result) {
                wp_send_json_success('Consent saved to database');
            } else {
                wp_send_json_error('Error saving consent to database');
            }
        } else {
            wp_send_json_error('Invalid consent details format');
        }
        
        wp_die();
    }

    /**
     * Sanitize consent details
     *
     * @param array $details Raw consent details
     * @return array Sanitized consent details
     */
    private function sanitize_consent_details($details) {
        // Define allowed consent types
        $allowed_types = array_keys(Simple_Cookie_Consent::get_consent_types());
        $allowed_types[] = 'googleConsentMode'; // Also allow this flag
        
        $sanitized = array();
        
        // Safety check - ensure $details is an array
        if (!is_array($details)) {
            return $sanitized;
        }
        
        foreach ($details as $key => $value) {
            // Sanitize the key
            $key = sanitize_key($key);
            
            // Only include allowed keys
            if (in_array($key, $allowed_types, true)) {
                // Convert to boolean and handle JS "false" strings
                if (is_string($value) && ($value === 'false' || $value === '0')) {
                    $sanitized[$key] = false;
                } else {
                    $sanitized[$key] = (bool) $value;
                }
            }
        }
        
        return $sanitized;
    }

    /**
     * Store consent in database
     *
     * @param bool $accepted Whether consent was accepted
     * @param array $details Consent details
     * @return bool Success status
     */
    public function store_consent($accepted, $details = array()) {
        global $wpdb;
        
        // Get user information - with proper sanitization
        $user_id = get_current_user_id();
        $ip_address = $this->get_ip_address();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';
        
        // Anonymize IP if setting enabled
        if (get_option('simple_cookie_consent_anonymize_ip', 'yes') === 'yes') {
            $ip_address = $this->anonymize_ip($ip_address);
        }
        
        // Determine consent type and status
        $consent_type = 'explicit'; // Default to explicit
        $status = $accepted ? 'accepted' : 'declined';
        
        // Check if any specific categories are accepted
        if ($accepted && !empty($details)) {
            $essential_only = true;
            
            foreach ($details as $key => $value) {
                if (!in_array($key, ['necessary', 'googleConsentMode']) && $value === true) {
                    $essential_only = false;
                    break;
                }
            }
            
            if ($essential_only) {
                $status = 'essential_only';
            }
        }
        
        // Validate consent_details before insertion
        $encoded_details = wp_json_encode($details);
        if ($encoded_details === false) {
            error_log('Failed to encode consent details as JSON');
            $encoded_details = '{}'; // Fallback to empty object
        }
        
        // Create table if it doesn't exist
        if (!$this->table_exists()) {
            $this->create_tables();
        }
        
        // Insert record with prepared statement for security
        $inserted = $wpdb->insert(
            $this->table_name,
            array(
                'user_id'         => $user_id ? $user_id : null,
                'ip_address'      => $ip_address,
                'user_agent'      => $user_agent,
                'consent_type'    => $consent_type,
                'consent_details' => $encoded_details,
                'status'          => $status,
                'created_at'      => current_time('mysql'),
                'updated_at'      => current_time('mysql'),
            ),
            array(
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            )
        );
        
        if ($inserted === false) {
            error_log('Database error when storing consent: ' . $wpdb->last_error);
        }
        
        return ($inserted !== false);
    }

    /**
     * Get visitor IP address with proxy support
     *
     * @return string IP address
     */
    private function get_ip_address() {
        // Check for shared internet/ISP IP
        if (!empty($_SERVER['HTTP_CLIENT_IP']) && $this->validate_ip($_SERVER['HTTP_CLIENT_IP'])) {
            return sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
        }
        
        // Check for IPs passing through proxies
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Check if multiple IPs
            $ips = explode(',', sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']));
            foreach ($ips as $ip) {
                $ip = trim($ip);
                if ($this->validate_ip($ip)) {
                    return $ip;
                }
            }
        }
        
        // Check for the remote address
        if (!empty($_SERVER['REMOTE_ADDR']) && $this->validate_ip($_SERVER['REMOTE_ADDR'])) {
            return sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }
        
        // If everything else fails
        return '0.0.0.0';
    }

    /**
     * Validate an IP address
     *
     * @param string $ip IP address to validate
     * @return bool Valid status
     */
    private function validate_ip($ip) {
        return filter_var($ip, FILTER_VALIDATE_IP, 
            FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    /**
     * Anonymize IP address for GDPR compliance
     *
     * @param string $ip IP address to anonymize
     * @return string Anonymized IP
     */
    private function anonymize_ip($ip) {
        if (empty($ip)) {
            return '0.0.0.0';
        }
        
        // Handle IPv4
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            // Replace last octet with zeros
            return preg_replace('/\.\d+$/', '.0', $ip);
        }
        
        // Handle IPv6
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // Remove last 80 bits (last 5 hextets)
            $parts = explode(':', $ip);
            $parts = array_slice($parts, 0, 3);
            while (count($parts) < 8) {
                $parts[] = '0000';
            }
            return implode(':', $parts);
        }
        
        return '0.0.0.0';
    }

    /**
     * Get consents from database with proper data handling
     *
     * @param array $args Query arguments
     * @return array Consent records
     */
    public function get_consents($args = array()) {
        global $wpdb;
        
        // Create table if it doesn't exist
        if (!$this->table_exists()) {
            $this->create_tables();
            return array(); // Return empty array as the table was just created
        }
        
        $defaults = array(
            'number'     => 20,
            'offset'     => 0,
            'orderby'    => 'id',
            'order'      => 'DESC',
            'user_id'    => null,
            'ip_address' => null,
            'status'     => null,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Sanitize input parameters
        $number = absint($args['number']);
        $offset = absint($args['offset']);
        
        // Validate orderby field
        $allowed_orderby = ['id', 'status', 'created_at', 'updated_at']; 
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'id';
        
        // Validate order direction
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        
        // Build query with prepared statements for security
        $query = "SELECT * FROM {$this->table_name} WHERE 1=1";
        $prepare_args = array();
        
        // Add conditionals using prepared statements
        if ($args['user_id'] !== null) {
            $query .= " AND user_id = %d";
            $prepare_args[] = $args['user_id'];
        }
        
        if ($args['ip_address'] !== null) {
            $query .= " AND ip_address = %s";
            $prepare_args[] = $args['ip_address'];
        }
        
        if ($args['status'] !== null) {
            $query .= " AND status = %s";
            $prepare_args[] = $args['status'];
        }
        
        // Add order and limit
        $query .= " ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $prepare_args[] = $number;
        $prepare_args[] = $offset;
        
        // Prepare the query
        $prepared_query = !empty($prepare_args) 
            ? $wpdb->prepare($query, $prepare_args) 
            : $query;
            
        // Debug the query
        error_log('Consent query: ' . $prepared_query);
        
        // Execute the query
        $results = $wpdb->get_results($prepared_query, ARRAY_A);
        
        // Check for errors
        if ($wpdb->last_error) {
            error_log('Database error in get_consents: ' . $wpdb->last_error);
            return array();
        }
        
        // Debug the results
        error_log('Query returned ' . count($results) . ' results');
        
        // Process results - properly decode the JSON consent details
        $consents = array();
        if (is_array($results) && !empty($results)) {
            foreach ($results as $row) {
                // Ensure all expected fields exist
                $processed_row = array(
                    'id' => isset($row['id']) ? (int) $row['id'] : 0,
                    'user_id' => isset($row['user_id']) ? (int) $row['user_id'] : null,
                    'ip_address' => isset($row['ip_address']) ? $row['ip_address'] : '',
                    'user_agent' => isset($row['user_agent']) ? $row['user_agent'] : '',
                    'consent_type' => isset($row['consent_type']) ? $row['consent_type'] : '',
                    'status' => isset($row['status']) ? $row['status'] : '',
                    'created_at' => isset($row['created_at']) ? $row['created_at'] : '',
                    'updated_at' => isset($row['updated_at']) ? $row['updated_at'] : '',
                );
                
                // Process consent details - decode JSON if present
                if (isset($row['consent_details']) && !empty($row['consent_details'])) {
                    try {
                        // Attempt to decode the JSON
                        $details = json_decode($row['consent_details'], true);
                        
                        // Verify it decoded to an array
                        if (is_array($details)) {
                            $processed_row['consent_details'] = $details;
                        } else {
                            error_log('Consent details not an array for ID ' . $row['id'] . ': ' . gettype($details));
                            $processed_row['consent_details'] = array();
                        }
                    } catch (Exception $e) {
                        error_log('Error decoding consent details for ID ' . $row['id'] . ': ' . $e->getMessage());
                        $processed_row['consent_details'] = array();
                    }
                } else {
                    $processed_row['consent_details'] = array();
                }
                
                // Debug the processed row
                error_log('Processed row ID ' . $processed_row['id'] . ' with consent details: ' . 
                          wp_json_encode($processed_row['consent_details']));
                
                $consents[] = $processed_row;
            }
        }
        
        return $consents;
    }

    /**
     * Count consents in database
     *
     * @param array $args Query arguments
     * @return int Number of consents
     */
    public function count_consents($args = array()) {
        global $wpdb;
        
        // Create table if it doesn't exist
        if (!$this->table_exists()) {
            $this->create_tables();
            return 0; // Return 0 as the table was just created
        }
        
        $defaults = array(
            'user_id'    => null,
            'ip_address' => null,
            'status'     => null,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Build query
        $query = "SELECT COUNT(*) FROM {$this->table_name} WHERE 1=1";
        $prepare_args = array();
        
        // Add conditionals with prepared statements
        if ($args['user_id'] !== null) {
            $query .= " AND user_id = %d";
            $prepare_args[] = $args['user_id'];
        }
        
        if ($args['ip_address'] !== null) {
            $query .= " AND ip_address = %s";
            $prepare_args[] = $args['ip_address'];
        }
        
        if ($args['status'] !== null) {
            $query .= " AND status = %s";
            $prepare_args[] = $args['status'];
        }
        
        // Prepare and execute the query
        $prepared_query = !empty($prepare_args) 
            ? $wpdb->prepare($query, $prepare_args) 
            : $query;
        
        // Debug the count query
        error_log('Count query: ' . $prepared_query);
        
        // Get count
        $count = (int) $wpdb->get_var($prepared_query);
        
        // Debug the count result
        error_log('Count result: ' . $count);
        
        return $count;
    }
}