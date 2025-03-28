<?php
/**
 * Plugin Name: Simple Cookie Consent
 * Plugin URI: https://github.com/adam-aido/simple-cookie-consent
 * Description: A lightweight cookie consent plugin compatible with Google Consent Mode v2, blocking cookies, localStorage, and sessionStorage until user consent.
 * Version: 1.3.0
 * Author: Adam Antoszczak + Claude.ai
 * Author URI: https://webartisan.pro
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
define('SIMPLE_COOKIE_CONSENT_VERSION', '1.3.0');

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

/**
 * Function to update the plugin files to support blocking Google and YouTube cookies
 * 
 * This function replaces the existing files with our enhanced versions
 */
function update_plugin_files_for_enhanced_blocking() {
    // 1. Replace cookie-blocker.js with enhanced version
    $cookie_blocker_path = SIMPLE_COOKIE_CONSENT_PLUGIN_DIR . 'assets/js/cookie-blocker.js';
    $enhanced_cookie_blocker = file_get_contents(__DIR__ . '/enhanced-cookie-blocker.js');
    if (!empty($enhanced_cookie_blocker)) {
        file_put_contents($cookie_blocker_path, $enhanced_cookie_blocker);
    }
    
    // 2. Replace cookie-consent.js with enhanced version
    $cookie_consent_path = SIMPLE_COOKIE_CONSENT_PLUGIN_DIR . 'assets/js/cookie-consent.js';
    $enhanced_cookie_consent = file_get_contents(__DIR__ . '/enhanced-cookie-consent.js');
    if (!empty($enhanced_cookie_consent)) {
        file_put_contents($cookie_consent_path, $enhanced_cookie_consent);
    }
    
    // 3. Replace class-frontend.php with enhanced version
    $frontend_path = SIMPLE_COOKIE_CONSENT_INCLUDES_DIR . 'class-frontend.php';
    $enhanced_frontend = file_get_contents(__DIR__ . '/enhanced-frontend.php');
    if (!empty($enhanced_frontend)) {
        file_put_contents($frontend_path, $enhanced_frontend);
    }
    
    // 4. Replace class-google-consent-mode.php with enhanced version
    $gcm_path = SIMPLE_COOKIE_CONSENT_INCLUDES_DIR . 'class-google-consent-mode.php';
    $enhanced_gcm = file_get_contents(__DIR__ . '/enhanced-gcm.php');
    if (!empty($enhanced_gcm)) {
        file_put_contents($gcm_path, $enhanced_gcm);
    }
    
    // 5. Replace class-simple-cookie-consent.php with enhanced version
    $core_path = SIMPLE_COOKIE_CONSENT_INCLUDES_DIR . 'class-simple-cookie-consent.php';
    $enhanced_core = file_get_contents(__DIR__ . '/enhanced-consent-types.php');
    if (!empty($enhanced_core)) {
        file_put_contents($core_path, $enhanced_core);
    }
    
    // Add a setting to indicate we've done the enhancement
    update_option('simple_cookie_consent_enhanced', 'yes');
}

// Call the function to update files if not already enhanced
function maybe_enable_enhanced_blocking() {
    if (get_option('simple_cookie_consent_enhanced', 'no') !== 'yes') {
        update_plugin_files_for_enhanced_blocking();
    }
}

// Hook this to plugin activation and admin init
add_action('activated_plugin', 'maybe_enable_enhanced_blocking');
add_action('admin_init', 'maybe_enable_enhanced_blocking');