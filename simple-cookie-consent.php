<?php
/**
 * Plugin Name: Simple Cookie Consent
 * Plugin URI: https://example.com/simple-cookie-consent
 * Description: A lightweight cookie consent plugin compatible with Google Consent Mode v2, blocking cookies, localStorage, and sessionStorage until user consent.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: Unlicense
 * License URI: http://unlicense.org/
 * Text Domain: simple-cookie-consent
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Plugin version
define('SIMPLE_COOKIE_CONSENT_VERSION', '1.0.0');

// Plugin paths
define('SIMPLE_COOKIE_CONSENT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SIMPLE_COOKIE_CONSENT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SIMPLE_COOKIE_CONSENT_INCLUDES_DIR', SIMPLE_COOKIE_CONSENT_PLUGIN_DIR . 'includes/');
define('SIMPLE_COOKIE_CONSENT_TEMPLATES_DIR', SIMPLE_COOKIE_CONSENT_PLUGIN_DIR . 'templates/');
define('SIMPLE_COOKIE_CONSENT_ASSETS_URL', SIMPLE_COOKIE_CONSENT_PLUGIN_URL . 'assets/');

/**
 * Load required files - Only if they exist
 */
$required_files = [
    SIMPLE_COOKIE_CONSENT_INCLUDES_DIR . 'class-simple-cookie-consent.php',
    SIMPLE_COOKIE_CONSENT_INCLUDES_DIR . 'class-admin.php',
    SIMPLE_COOKIE_CONSENT_INCLUDES_DIR . 'class-frontend.php',
    SIMPLE_COOKIE_CONSENT_INCLUDES_DIR . 'class-cookie-controller.php',
    SIMPLE_COOKIE_CONSENT_INCLUDES_DIR . 'class-google-consent-mode.php',
    SIMPLE_COOKIE_CONSENT_INCLUDES_DIR . 'class-shortcodes.php',
    SIMPLE_COOKIE_CONSENT_INCLUDES_DIR . 'class-consent-storage.php',
    SIMPLE_COOKIE_CONSENT_INCLUDES_DIR . 'class-admin-consent-log.php'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        require_once $file;
    } else {
        // Log error if file doesn't exist (for debugging)
        error_log('Simple Cookie Consent: Missing required file: ' . $file);
        
        // If a core file is missing, don't initialize the plugin to prevent errors
        if (strpos($file, 'class-simple-cookie-consent.php') !== false) {
            return;
        }
    }
}

/**
 * Global storage instance
 */
global $simple_cookie_consent_storage;

/**
 * Begins execution of the plugin.
 */
function run_simple_cookie_consent() {
    global $simple_cookie_consent_storage;
    
    // Only run if the main class exists
    if (class_exists('Simple_Cookie_Consent')) {
        // Initialize storage
        $simple_cookie_consent_storage = new Simple_Cookie_Consent_Storage();
        $simple_cookie_consent_storage->init();
        
        // Initialize main plugin
        $plugin = new Simple_Cookie_Consent();
        $plugin->run();
        
        // Initialize admin consent log
        if (is_admin() && class_exists('Simple_Cookie_Consent_Admin_Log')) {
            $consent_log = new Simple_Cookie_Consent_Admin_Log();
            $consent_log->init();
        }
    }
}

/**
 * Run the plugin.
 */
run_simple_cookie_consent();

/**
 * Activation hook
 */
register_activation_hook(__FILE__, 'simple_cookie_consent_activate');

/**
 * Plugin activation function
 */
function simple_cookie_consent_activate() {
    // Verify WordPress version meets requirements
    if (version_compare(get_bloginfo('version'), '6.0', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            esc_html__('Simple Cookie Consent requires WordPress version 6.0 or higher.', 'simple-cookie-consent'),
            'Plugin Activation Error',
            array('back_link' => true)
        );
    }
    
    // Create required directories if they don't exist
    $dirs = array(
        'assets',
        'assets/css',
        'assets/js',
        'includes',
        'templates',
        'languages'
    );
    
    foreach ($dirs as $dir) {
        $path = SIMPLE_COOKIE_CONSENT_PLUGIN_DIR . $dir;
        if (!file_exists($path)) {
            wp_mkdir_p($path);
        }
    }
    
    // Set default options with proper sanitization
    $default_options = array(
        'simple_cookie_consent_title' => __('Cookie Consent', 'simple-cookie-consent'),
        'simple_cookie_consent_text' => __('This website uses cookies to ensure you get the best experience.', 'simple-cookie-consent'),
        'simple_cookie_consent_accept_all' => __('Accept All', 'simple-cookie-consent'),
        'simple_cookie_consent_accept_essential' => __('Accept Only Essential', 'simple-cookie-consent'),
        'simple_cookie_consent_customize' => __('Customize', 'simple-cookie-consent'),
        'simple_cookie_consent_save_preferences' => __('Save Preferences', 'simple-cookie-consent'),
        'simple_cookie_consent_expiry' => 180,
        'simple_cookie_consent_position' => 'bottom',
        'simple_cookie_consent_theme' => 'light',
        'simple_cookie_consent_gcm_enabled' => 'yes',
        'simple_cookie_consent_gcm_region' => 'EU',
        'simple_cookie_consent_anonymize_ip' => 'yes',
    );
    
    foreach ($default_options as $option_name => $default_value) {
        if (get_option($option_name) === false) {
            if (is_numeric($default_value)) {
                update_option($option_name, absint($default_value));
            } else {
                update_option($option_name, sanitize_text_field($default_value));
            }
        }
    }
    
    // Create database tables
    if (class_exists('Simple_Cookie_Consent_Storage')) {
        global $simple_cookie_consent_storage;
        
        if (!$simple_cookie_consent_storage) {
            $simple_cookie_consent_storage = new Simple_Cookie_Consent_Storage();
        }
        
        do_action('simple_cookie_consent_create_tables');
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Set plugin version
    update_option('simple_cookie_consent_version', SIMPLE_COOKIE_CONSENT_VERSION);
}

/**
 * Deactivation hook
 */
register_deactivation_hook(__FILE__, 'simple_cookie_consent_deactivate');

/**
 * Plugin deactivation function
 */
function simple_cookie_consent_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Database update check on plugin update
 */
function simple_cookie_consent_check_version() {
    $installed_version = get_option('simple_cookie_consent_version');
    
    // If no version exists or version is different
    if (!$installed_version || version_compare($installed_version, SIMPLE_COOKIE_CONSENT_VERSION, '<')) {
        // Perform database updates if needed
        if (class_exists('Simple_Cookie_Consent_Storage')) {
            global $simple_cookie_consent_storage;
            
            if (!$simple_cookie_consent_storage) {
                $simple_cookie_consent_storage = new Simple_Cookie_Consent_Storage();
            }
            
            do_action('simple_cookie_consent_create_tables');
        }
        
        // Update version
        update_option('simple_cookie_consent_version', SIMPLE_COOKIE_CONSENT_VERSION);
    }
}
add_action('plugins_loaded', 'simple_cookie_consent_check_version');