<?php
/**
 * The cookie controller functionality of the plugin.
 *
 * @package    Simple_Cookie_Consent
 */

class Simple_Cookie_Consent_Cookie_Controller {

    /**
     * Storage instance
     * 
     * @var Simple_Cookie_Consent_Storage
     */
    private $storage;

    /**
     * Initialize the class.
     */
    public function init() {
        global $simple_cookie_consent_storage;
        $this->storage = $simple_cookie_consent_storage;
        
        // Add filters to block cookies until consent
        add_filter('wp_headers', array($this, 'modify_cookie_headers'), 999);
        
        // AJAX handlers for cookie consent - make sure they're properly hooked
        add_action('wp_ajax_simple_cookie_set_consent', array($this, 'ajax_set_consent'));
        add_action('wp_ajax_nopriv_simple_cookie_set_consent', array($this, 'ajax_set_consent'));
    }

    /**
     * Modify cookie headers to enforce consent
     * 
     * @param array $headers Current headers
     * @return array Modified headers
     */
    public function modify_cookie_headers($headers) {
        // Implement strict headers for cookie control
        if (!isset($_COOKIE['simple_cookie_consent_accepted'])) {
            $headers['Cache-Control'] = 'no-store, no-cache, must-revalidate, max-age=0';
            $headers['Pragma'] = 'no-cache';
            $headers['Expires'] = '0';
            
            // Add SameSite policy
            if (!isset($headers['Set-Cookie'])) {
                $headers['Set-Cookie'] = '';
            }
            $headers['Set-Cookie'] .= '; SameSite=Lax; Secure';
        }
        return $headers;
    }

    /**
     * AJAX handler for setting cookie consent
     */
    public function ajax_set_consent() {
        // Basic debug information to verify the function is being called
        error_log('AJAX consent function called');
        
        // Verify nonce with relaxed checking for debugging
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'simple_cookie_consent_nonce')) {
            error_log('Nonce verification failed: ' . (isset($_POST['nonce']) ? $_POST['nonce'] : 'not set'));
            wp_send_json_error('Invalid security token');
            wp_die();
        }

        // Get consent data
        $consent_accepted = isset($_POST['accepted']) && $_POST['accepted'] ? true : false;
        
        // Log consent data for debugging
        error_log('Consent accepted: ' . ($consent_accepted ? 'yes' : 'no'));
        
        if (isset($_POST['details']) && is_array($_POST['details'])) {
            // Sanitize details
            $sanitized_details = array();
            
            // Explicitly validate against allowed consent types
            $allowed_types = array_keys(Simple_Cookie_Consent::get_consent_types());
            
            foreach ($_POST['details'] as $key => $value) {
                $key = sanitize_key($key);
                
                // Only accept known consent types plus googleConsentMode flag
                if (in_array($key, $allowed_types) || $key === 'googleConsentMode') {
                    $sanitized_details[$key] = (bool) $value;
                }
            }
            
            // Log sanitized details for debugging
            error_log('Consent details: ' . wp_json_encode($sanitized_details));
            
            // Store in database if storage is available
            if ($this->storage) {
                try {
                    $result = $this->storage->store_consent($consent_accepted, $sanitized_details);
                    error_log('Storage result: ' . ($result ? 'success' : 'failure'));
                } catch (Exception $e) {
                    error_log('Error storing consent: ' . $e->getMessage());
                }
            } else {
                error_log('Storage not available');
            }
            
            wp_send_json_success('Consent saved');
        } else {
            error_log('Invalid details format');
            wp_send_json_error('Invalid details format');
        }
        
        wp_die(); // Proper AJAX termination
    }

    /**
     * Set consent cookies
     * 
     * @param bool $accepted Whether consent was accepted
     * @param array $details Specific consent details
     */
    private function set_consent_cookies($accepted, $details = array()) {
        $expiry = absint(get_option('simple_cookie_consent_expiry', 180));
        $expiry_time = time() + ($expiry * DAY_IN_SECONDS);
        
        // Set the main acceptance cookie with proper flags
        setcookie(
            'simple_cookie_consent_accepted',
            $accepted ? '1' : '0',
            [
                'expires' => $expiry_time,
                'path' => '/',
                'domain' => '',
                'secure' => is_ssl(),
                'httponly' => false,
                'samesite' => 'Lax'
            ]
        );
        
        // Store details if provided
        if (!empty($details)) {
            // Encode with JSON_HEX_TAG for protection against XSS
            setcookie(
                'simple_cookie_consent_details',
                json_encode($details, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
                [
                    'expires' => $expiry_time,
                    'path' => '/',
                    'domain' => '',
                    'secure' => is_ssl(),
                    'httponly' => false,
                    'samesite' => 'Lax'
                ]
            );
        }
    }
}