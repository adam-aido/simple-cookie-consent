<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package    Simple_Cookie_Consent
 */

class Simple_Cookie_Consent_Frontend {

    /**
     * Initialize the class.
     */
    public function init() {
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Add consent banner to footer
        add_action('wp_footer', array($this, 'add_consent_banner'));
    }

    /**
     * Enqueue plugin scripts and styles
     */
    public function enqueue_assets() {
        // Main stylesheet
        wp_enqueue_style(
            'simple-cookie-consent-css',
            SIMPLE_COOKIE_CONSENT_ASSETS_URL . 'css/cookie-consent.css',
            array(),
            SIMPLE_COOKIE_CONSENT_VERSION
        );

        // Cookie blocker script (load early in head)
        wp_enqueue_script(
            'simple-cookie-consent-blocker',
            SIMPLE_COOKIE_CONSENT_ASSETS_URL . 'js/cookie-blocker.js',
            array(),
            SIMPLE_COOKIE_CONSENT_VERSION,
            false
        );

        // Cookie consent handler script
        wp_enqueue_script(
            'simple-cookie-consent-js',
            SIMPLE_COOKIE_CONSENT_ASSETS_URL . 'js/cookie-consent.js',
            array('jquery'),
            SIMPLE_COOKIE_CONSENT_VERSION,
            true
        );

        // Pass settings to script
        $settings = array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('simple-cookie-consent-nonce'),
            'cookieExpiry' => get_option('simple_cookie_consent_expiry', 180),
            'consentTypes' => Simple_Cookie_Consent::get_consent_types(),
            'googleConsentMode' => get_option('simple_cookie_consent_gcm_enabled', 'yes') === 'yes',
            'googleConsentRegion' => get_option('simple_cookie_consent_gcm_region', 'EU'),
            'googleTagId' => get_option('simple_cookie_consent_gcm_tag_id', ''),
            'texts' => array(
                'banner_title' => get_option('simple_cookie_consent_title', __('Cookie Consent', 'simple-cookie-consent')),
                'banner_text' => get_option('simple_cookie_consent_text', __('This website uses cookies to ensure you get the best experience.', 'simple-cookie-consent')),
                'accept_all' => get_option('simple_cookie_consent_accept_all', __('Accept All', 'simple-cookie-consent')),
                'accept_essential' => get_option('simple_cookie_consent_accept_essential', __('Accept Only Essential', 'simple-cookie-consent')),
                'customize' => get_option('simple_cookie_consent_customize', __('Customize', 'simple-cookie-consent')),
                'save_preferences' => get_option('simple_cookie_consent_save_preferences', __('Save Preferences', 'simple-cookie-consent')),
                'preferences_updated' => __('Preferences Updated!', 'simple-cookie-consent'),
            )
        );
        wp_localize_script('simple-cookie-consent-js', 'simpleCookieConsent', $settings);
    }

    /**
     * Add consent banner to footer
     */
    public function add_consent_banner() {
        include SIMPLE_COOKIE_CONSENT_TEMPLATES_DIR . 'consent-banner.php';
    }
}