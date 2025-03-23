<?php
/**
 * The core plugin class.
 *
 * This class defines all the hooks, dependencies, and attributes needed
 * for the plugin to function.
 *
 * @package    Simple_Cookie_Consent
 */

class Simple_Cookie_Consent {

    /**
     * The admin class instance.
     *
     * @var     Simple_Cookie_Consent_Admin
     */
    protected $admin;

    /**
     * The frontend class instance.
     *
     * @var     Simple_Cookie_Consent_Frontend
     */
    protected $frontend;

    /**
     * The cookie controller class instance.
     *
     * @var     Simple_Cookie_Consent_Cookie_Controller
     */
    protected $cookie_controller;

    /**
     * The Google Consent Mode class instance.
     *
     * @var     Simple_Cookie_Consent_Google_Consent_Mode
     */
    protected $google_consent_mode;

    /**
     * The shortcodes class instance.
     *
     * @var     Simple_Cookie_Consent_Shortcodes
     */
    protected $shortcodes;

    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        $this->load_dependencies();
    }

    /**
     * Load the plugin dependencies
     */
    private function load_dependencies() {
        $this->admin = new Simple_Cookie_Consent_Admin();
        $this->frontend = new Simple_Cookie_Consent_Frontend();
        $this->cookie_controller = new Simple_Cookie_Consent_Cookie_Controller();
        $this->google_consent_mode = new Simple_Cookie_Consent_Google_Consent_Mode();
        $this->shortcodes = new Simple_Cookie_Consent_Shortcodes();
    }

    /**
     * Run the plugin - register all hooks and filters
     */
    public function run() {
        // Load text domain for translations
        add_action('init', array($this, 'load_textdomain'));
        
        // Initialize components
        $this->admin->init();
        $this->frontend->init();
        $this->cookie_controller->init();
        $this->google_consent_mode->init();
        $this->shortcodes->init();
    }

    /**
     * Load the plugin text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'simple-cookie-consent',
            false,
            dirname(plugin_basename(SIMPLE_COOKIE_CONSENT_PLUGIN_DIR)) . '/languages/'
        );
    }

    /**
     * Get available consent types
     * 
     * @return array Array of consent types with their properties
     */
    public static function get_consent_types() {
        $consent_types = array(
            'necessary' => array(
                'id' => 'necessary',
                'label' => __('Necessary', 'simple-cookie-consent'),
                'description' => __('These cookies are essential for the website to function properly.', 'simple-cookie-consent'),
                'required' => true,
                'gcm_purpose' => 'functionality_storage'
            ),
            'preferences' => array(
                'id' => 'preferences',
                'label' => __('Preferences', 'simple-cookie-consent'),
                'description' => __('These cookies allow the website to remember choices you make.', 'simple-cookie-consent'),
                'required' => false,
                'gcm_purpose' => 'preference_storage'
            ),
            'analytics' => array(
                'id' => 'analytics',
                'label' => __('Analytics', 'simple-cookie-consent'),
                'description' => __('These cookies help us understand how visitors interact with the website.', 'simple-cookie-consent'),
                'required' => false,
                'gcm_purpose' => 'analytics_storage'
            ),
            'marketing' => array(
                'id' => 'marketing',
                'label' => __('Marketing', 'simple-cookie-consent'),
                'description' => __('These cookies are used to track visitors across websites to display relevant advertisements.', 'simple-cookie-consent'),
                'required' => false,
                'gcm_purpose' => 'ad_storage'
            ),
            'social' => array(
                'id' => 'social',
                'label' => __('Social Media', 'simple-cookie-consent'),
                'description' => __('These cookies are set by social media services that we have added to the site.', 'simple-cookie-consent'),
                'required' => false,
                'gcm_purpose' => 'personalization_storage'
            )
        );

        return apply_filters('simple_cookie_consent_types', $consent_types);
    }
}