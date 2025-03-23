<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Simple_Cookie_Consent
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Options to remove
$options = array(
    'simple_cookie_consent_title',
    'simple_cookie_consent_text',
    'simple_cookie_consent_accept_all',
    'simple_cookie_consent_accept_essential',
    'simple_cookie_consent_customize',
    'simple_cookie_consent_save_preferences',
    'simple_cookie_consent_expiry',
    'simple_cookie_consent_position',
    'simple_cookie_consent_theme',
    'simple_cookie_consent_gcm_enabled',
    'simple_cookie_consent_gcm_region',
    'simple_cookie_consent_gcm_tag_id'
);

// Loop through each option and delete it
foreach ($options as $option) {
    delete_option($option);
}