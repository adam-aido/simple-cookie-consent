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
        
        // AJAX handlers for cookie consent
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
        check_ajax_referer('simple_cookie_consent_nonce', 'nonce');

        $consent_accepted = isset($_POST['accepted']) && $_POST['accepted'] ? true : false;
        
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
            
            // Store in cookies
            $this->set_consent_cookies($consent_accepted, $sanitized_details);
            
            // Also store in database
            if ($this->storage) {
                $this->storage->store_consent($consent_accepted, $sanitized_details);
            }
            
            wp_send_json_success('Consent saved');
        } else {
            wp_send_json_error('Invalid details');
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