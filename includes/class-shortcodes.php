<?php
/**
 * The shortcodes functionality of the plugin.
 *
 * @package    Simple_Cookie_Consent
 */

class Simple_Cookie_Consent_Shortcodes {

    /**
     * Initialize the class.
     */
    public function init() {
        // Register shortcodes
        add_shortcode('cookie_consent', array($this, 'cookie_consent_shortcode'));
        add_shortcode('cookie_settings', array($this, 'cookie_settings_shortcode'));
    }

    /**
     * Shortcode to display the consent form
     * 
     * @param array $atts Shortcode attributes
     * @return string Rendered HTML
     */
    public function cookie_consent_shortcode($atts) {
        $atts = shortcode_atts(array(
            'theme' => get_option('simple_cookie_consent_theme', 'light'),
            'title' => __('Cookie Settings', 'simple-cookie-consent'),
            'button_text' => __('Update Preferences', 'simple-cookie-consent')
        ), $atts, 'cookie_consent');

        ob_start();
        include SIMPLE_COOKIE_CONSENT_TEMPLATES_DIR . 'consent-form.php';
        return ob_get_clean();
    }
    
    /**
     * Shortcode to display just the cookie settings button
     * 
     * @param array $atts Shortcode attributes
     * @return string Rendered HTML
     */
    public function cookie_settings_shortcode($atts) {
        $atts = shortcode_atts(array(
            'text' => __('Cookie Settings', 'simple-cookie-consent'),
            'class' => 'scc-settings-button',
        ), $atts, 'cookie_settings');
        
        return '<button class="' . esc_attr($atts['class']) . '" onclick="if(typeof window.openCookieModal === \'function\') window.openCookieModal();">' . 
            esc_html($atts['text']) . '</button>';
    }
}