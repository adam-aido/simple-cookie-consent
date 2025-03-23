<?php
/**
 * The cookie controller functionality of the plugin.
 *
 * @package    Simple_Cookie_Consent
 */

class Simple_Cookie_Consent_Cookie_Controller {

    /**
     * Initialize the class.
     */
    public function init() {
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
        }
        return $headers;
    }

    /**
     * AJAX handler for setting cookie consent
     */
    public function ajax_set_consent() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'simple-cookie-consent-nonce')) {
            wp_send_json_error('Invalid nonce');
            exit;
        }

        $consent_accepted = isset($_POST['accepted']) ? (bool) $_POST['accepted'] : false;
        $consent_details = isset($_POST['details']) ? $_POST['details'] : null;
        
        if ($consent_details && is_array($consent_details)) {
            // Sanitize details
            $sanitized_details = array();
            foreach ($consent_details as $key => $value) {
                $sanitized_details[sanitize_key($key)] = (bool) $value;
            }
            
            // Store in a separate cookie
            $this->set_consent_cookies($consent_accepted, $sanitized_details);
            
            wp_send_json_success('Consent saved');
        } else {
            wp_send_json_error('Invalid details');
        }
        
        exit;
    }

    /**
     * Set consent cookies
     * 
     * @param bool $accepted Whether consent was accepted
     * @param array $details Specific consent details
     */
    private function set_consent_cookies($accepted, $details = array()) {
        $expiry = (int) get_option('simple_cookie_consent_expiry', 180);
        $expiry_time = time() + ($expiry * DAY_IN_SECONDS);
        
        // Set the main acceptance cookie
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
        
        // Set the details cookie
        if (!empty($details)) {
            setcookie(
                'simple_cookie_consent_details',
                json_encode($details),
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