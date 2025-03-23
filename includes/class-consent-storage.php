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
        add_action('wp_ajax_simple_cookie_store_consent', array($this, 'ajax_store_consent'));
        add_action('wp_ajax_nopriv_simple_cookie_store_consent', array($this, 'ajax_store_consent'));
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
    }

    /**
     * AJAX handler for storing consent in database
     */
    public function ajax_store_consent() {
        // Verify nonce
        check_ajax_referer('simple_cookie_consent_nonce', 'nonce');

        $consent_accepted = isset($_POST['accepted']) && $_POST['accepted'] ? true : false;
        $consent_details = isset($_POST['details']) && is_array($_POST['details']) ? $_POST['details'] : array();
        
        // Sanitize consent details
        $sanitized_details = $this->sanitize_consent_details($consent_details);
        
        // Save consent to database
        $result = $this->store_consent($consent_accepted, $sanitized_details);
        
        if ($result) {
            wp_send_json_success('Consent saved to database');
        } else {
            wp_send_json_error('Error saving consent to database');
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
        
        foreach ($details as $key => $value) {
            $key = sanitize_key($key);
            
            // Only include allowed keys
            if (in_array($key, $allowed_types, true)) {
                $sanitized[$key] = (bool) $value;
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
        
        // Get user information
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
        
        // Insert record
        $inserted = $wpdb->insert(
            $this->table_name,
            array(
                'user_id'         => $user_id ? $user_id : null,
                'ip_address'      => $ip_address,
                'user_agent'      => $user_agent,
                'consent_type'    => $consent_type,
                'consent_details' => wp_json_encode($details),
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
     * Get consents from database
     *
     * @param array $args Query arguments
     * @return array Consent records
     */
    public function get_consents($args = array()) {
        global $wpdb;
        
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
        
        // Build query
        $query = "SELECT * FROM {$this->table_name} WHERE 1=1";
        
        // Add conditionals
        if ($args['user_id'] !== null) {
            $query .= $wpdb->prepare(" AND user_id = %d", $args['user_id']);
        }
        
        if ($args['ip_address'] !== null) {
            $query .= $wpdb->prepare(" AND ip_address = %s", $args['ip_address']);
        }
        
        if ($args['status'] !== null) {
            $query .= $wpdb->prepare(" AND status = %s", $args['status']);
        }
        
        // Add order
        $query .= " ORDER BY " . sanitize_sql_orderby("{$args['orderby']} {$args['order']}");
        
        // Add limit
        $query .= $wpdb->prepare(" LIMIT %d, %d", $args['offset'], $args['number']);
        
        // Get results
        $results = $wpdb->get_results($query, ARRAY_A);
        
        // Process results
        $consents = array();
        foreach ($results as $row) {
            $row['consent_details'] = json_decode($row['consent_details'], true);
            $consents[] = $row;
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
        
        $defaults = array(
            'user_id'    => null,
            'ip_address' => null,
            'status'     => null,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Build query
        $query = "SELECT COUNT(*) FROM {$this->table_name} WHERE 1=1";
        
        // Add conditionals
        if ($args['user_id'] !== null) {
            $query .= $wpdb->prepare(" AND user_id = %d", $args['user_id']);
        }
        
        if ($args['ip_address'] !== null) {
            $query .= $wpdb->prepare(" AND ip_address = %s", $args['ip_address']);
        }
        
        if ($args['status'] !== null) {
            $query .= $wpdb->prepare(" AND status = %s", $args['status']);
        }
        
        // Get count
        return (int) $wpdb->get_var($query);
    }
}