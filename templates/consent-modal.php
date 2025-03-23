<?php
/**
 * Consent modal template
 *
 * @package Simple_Cookie_Consent
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

$theme = esc_attr(get_option('simple_cookie_consent_theme', 'light'));
$save_preferences = esc_html(get_option('simple_cookie_consent_save_preferences', __('Save Preferences', 'simple-cookie-consent')));
?>

<div class="scc-modal scc-theme-<?php echo $theme; ?>">
    <div class="scc-modal-content">
        <h2><?php _e('Cookie Preferences', 'simple-cookie-consent'); ?></h2>
        
        <div class="scc-tabs">
            <div class="scc-tab scc-active" data-tab="scc-tab-cookies"><?php _e('Cookies', 'simple-cookie-consent'); ?></div>
            <div class="scc-tab" data-tab="scc-tab-about"><?php _e('About', 'simple-cookie-consent'); ?></div>
        </div>
        
        <div id="scc-tab-cookies" class="scc-tab-content scc-active">
            <?php foreach (Simple_Cookie_Consent::get_consent_types() as $type): ?>
                <div class="scc-consent-item">
                    <div class="scc-consent-header">
                        <div class="scc-consent-title"><?php echo esc_html($type['label']); ?></div>
                        <label class="scc-consent-toggle">
                            <input type="checkbox" id="scc-consent-<?php echo esc_attr($type['id']); ?>" 
                                <?php echo $type['required'] ? 'checked disabled' : ''; ?>>
                            <span class="scc-consent-slider"></span>
                        </label>
                    </div>
                    <div class="scc-consent-description"><?php echo esc_html($type['description']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div id="scc-tab-about" class="scc-tab-content">
            <h3><?php _e('About Cookies', 'simple-cookie-consent'); ?></h3>
            <p><?php _e('Cookies are small text files that are stored on your browser when you visit websites. They are widely used to make websites work more efficiently and to provide information to the website owners.', 'simple-cookie-consent'); ?></p>
            
            <h3><?php _e('How We Use Cookies', 'simple-cookie-consent'); ?></h3>
            <p><?php _e('We use different types of cookies for different purposes. Some cookies are necessary for the website to function properly, while others help us improve your experience by providing insights into how the site is used.', 'simple-cookie-consent'); ?></p>
            
            <h3><?php _e('Your Choices', 'simple-cookie-consent'); ?></h3>
            <p><?php _e('You can choose to accept or decline cookies. Most web browsers automatically accept cookies, but you can usually modify your browser settings to decline cookies if you prefer. However, this may prevent you from taking full advantage of the website.', 'simple-cookie-consent'); ?></p>
        </div>
        
        <div class="scc-footer">
            <button class="scc-button scc-button-primary scc-save-preferences"><?php echo $save_preferences; ?></button>
        </div>
    </div>
</div>