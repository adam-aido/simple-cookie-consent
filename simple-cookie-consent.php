<?php
/**
 * Plugin Name: Simple Cookie Consent
 * Plugin URI: https://example.com/simple-cookie-consent
 * Description: A lightweight cookie consent plugin compatible with Google Consent Mode v2, blocking cookies, localStorage, and sessionStorage until user consent.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
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
 * Load required files
 */
require_once SIMPLE_COOKIE_CONSENT_INCLUDES_DIR . 'class-simple-cookie-consent.php';
require_once SIMPLE_COOKIE_CONSENT_INCLUDES_DIR . 'class-admin.php';
require_once SIMPLE_COOKIE_CONSENT_INCLUDES_DIR . 'class-frontend.php';
require_once SIMPLE_COOKIE_CONSENT_INCLUDES_DIR . 'class-cookie-controller.php';
require_once SIMPLE_COOKIE_CONSENT_INCLUDES_DIR . 'class-google-consent-mode.php';
require_once SIMPLE_COOKIE_CONSENT_INCLUDES_DIR . 'class-shortcodes.php';

/**
 * Begins execution of the plugin.
 */
function run_simple_cookie_consent() {
    $plugin = new Simple_Cookie_Consent();
    $plugin->run();
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
    
    // Set default options
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
    );
    
    foreach ($default_options as $option_name => $default_value) {
        if (get_option($option_name) === false) {
            update_option($option_name, $default_value);
        }
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
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