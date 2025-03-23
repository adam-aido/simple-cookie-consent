<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Simple_Cookie_Consent
 */

class Simple_Cookie_Consent_Admin {

    /**
     * Initialize the class.
     */
    public function init() {
        // Add settings page
        add_action('admin_menu', array($this, 'add_settings_page'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add settings link on plugins page
        add_filter('plugin_action_links_' . plugin_basename(SIMPLE_COOKIE_CONSENT_PLUGIN_DIR . 'simple-cookie-consent.php'), 
            array($this, 'add_settings_link')
        );
    }

    /**
     * Add settings page to admin menu
     */
    public function add_settings_page() {
        add_options_page(
            __('Cookie Consent Settings', 'simple-cookie-consent'),
            __('Cookie Consent', 'simple-cookie-consent'),
            'manage_options',
            'simple-cookie-consent',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        // General settings
        register_setting('simple_cookie_consent_general', 'simple_cookie_consent_expiry');
        
        // Appearance settings
        register_setting('simple_cookie_consent_appearance', 'simple_cookie_consent_position');
        register_setting('simple_cookie_consent_appearance', 'simple_cookie_consent_theme');
        
        // Text settings
        register_setting('simple_cookie_consent_texts', 'simple_cookie_consent_title');
        register_setting('simple_cookie_consent_texts', 'simple_cookie_consent_text');
        register_setting('simple_cookie_consent_texts', 'simple_cookie_consent_accept_all');
        register_setting('simple_cookie_consent_texts', 'simple_cookie_consent_accept_essential');
        register_setting('simple_cookie_consent_texts', 'simple_cookie_consent_customize');
        register_setting('simple_cookie_consent_texts', 'simple_cookie_consent_save_preferences');
        
        // Google Consent Mode settings
        register_setting('simple_cookie_consent_gcm', 'simple_cookie_consent_gcm_enabled');
        register_setting('simple_cookie_consent_gcm', 'simple_cookie_consent_gcm_region');
        register_setting('simple_cookie_consent_gcm', 'simple_cookie_consent_gcm_tag_id');
    }

    /**
     * Add settings link to plugin listing
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=simple-cookie-consent') . '">' . __('Settings', 'simple-cookie-consent') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Render settings page HTML
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Get saved options
        $title = get_option('simple_cookie_consent_title', __('Cookie Consent', 'simple-cookie-consent'));
        $text = get_option('simple_cookie_consent_text', __('This website uses cookies to ensure you get the best experience.', 'simple-cookie-consent'));
        $accept_all = get_option('simple_cookie_consent_accept_all', __('Accept All', 'simple-cookie-consent'));
        $accept_essential = get_option('simple_cookie_consent_accept_essential', __('Accept Only Essential', 'simple-cookie-consent'));
        $customize = get_option('simple_cookie_consent_customize', __('Customize', 'simple-cookie-consent'));
        $save_preferences = get_option('simple_cookie_consent_save_preferences', __('Save Preferences', 'simple-cookie-consent'));
        $expiry = get_option('simple_cookie_consent_expiry', 180);
        $position = get_option('simple_cookie_consent_position', 'bottom');
        $theme = get_option('simple_cookie_consent_theme', 'light');
        $gcm_enabled = get_option('simple_cookie_consent_gcm_enabled', 'yes');
        $gcm_region = get_option('simple_cookie_consent_gcm_region', 'EU');
        $gcm_tag_id = get_option('simple_cookie_consent_gcm_tag_id', '');
        
        // Include the settings template
        include SIMPLE_COOKIE_CONSENT_TEMPLATES_DIR . 'admin-settings.php';
    }
}