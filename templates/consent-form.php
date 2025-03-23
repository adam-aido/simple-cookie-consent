<?php
/**
 * Consent form template (for shortcode)
 *
 * @package Simple_Cookie_Consent
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="scc-consent-form scc-theme-<?php echo esc_attr($atts['theme']); ?>">
    <h3><?php echo esc_html($atts['title']); ?></h3>
    
    <?php foreach (Simple_Cookie_Consent::get_consent_types() as $type): ?>
        <div class="scc-consent-item">
            <div class="scc-consent-header">
                <div class="scc-consent-title"><?php echo esc_html($type['label']); ?></div>
                <label class="scc-consent-toggle">
                    <input type="checkbox" class="scc-consent-checkbox" data-type="<?php echo esc_attr($type['id']); ?>" 
                        <?php echo $type['required'] ? 'checked disabled' : ''; ?>>
                    <span class="scc-consent-slider"></span>
                </label>
            </div>
            <div class="scc-consent-description"><?php echo esc_html($type['description']); ?></div>
        </div>
    <?php endforeach; ?>
    
    <div class="scc-footer">
        <button class="scc-button scc-button-primary scc-update-preferences"><?php echo esc_html($atts['button_text']); ?></button>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        // Load current settings if available
        function loadCurrentSettings() {
            try {
                const consentCookie = document.cookie
                    .split('; ')
                    .find(row => row.startsWith('simple_cookie_consent_details='));
                
                if (consentCookie) {
                    const details = JSON.parse(decodeURIComponent(consentCookie.split('=')[1]));
                    
                    // Set checkboxes based on stored preferences
                    $.each(details, function(key, value) {
                        if (value) {
                            $('input[data-type="' + key + '"]').prop('checked', true);
                        }
                    });
                }
            } catch (e) {
                console.error('Error loading consent settings', e);
            }
        }
        
        // Load current settings when form loads
        loadCurrentSettings();
        
        // Update preferences button click
        $('.scc-update-preferences').on('click', function() {
            var preferences = {};
            
            // Get all toggle states
            $('.scc-consent-checkbox').each(function() {
                var type = $(this).data('type');
                preferences[type] = $(this).is(':checked');
            });
            
            // Set preferences flag for Google Consent Mode
            preferences.googleConsentMode = true;
            
            // Use the main plugin's functions to update cookies
            if (typeof window.simpleCookieConsent !== 'undefined') {
                // Get cookie expiry
                var expiryDays = parseInt(window.simpleCookieConsent.cookieExpiry) || 180;
                var date = new Date();
                date.setTime(date.getTime() + (expiryDays * 24 * 60 * 60 * 1000));
                var expires = 'expires=' + date.toUTCString();
                
                // Set acceptance cookie
                document.cookie = 'simple_cookie_consent_accepted=1; ' + expires + '; path=/; SameSite=Lax';
                
                // Store preferences
                document.cookie = 'simple_cookie_consent_details=' + encodeURIComponent(JSON.stringify(preferences)) + 
                    '; ' + expires + '; path=/; SameSite=Lax';
                
                // Enable storage according to preferences
                if (typeof window.enableCookiesAndStorage === 'function') {
                    window.enableCookiesAndStorage(preferences);
                }
                
                // Update Google Consent Mode if enabled
                if (window.simpleCookieConsent.googleConsentMode && window.gtag) {
                    window.gtag('consent', 'update', {
                        'ad_storage': preferences.marketing ? 'granted' : 'denied',
                        'analytics_storage': preferences.analytics ? 'granted' : 'denied',
                        'functionality_storage': preferences.necessary ? 'granted' : 'denied',
                        'personalization_storage': preferences.social ? 'granted' : 'denied',
                        'security_storage': 'granted'
                    });
                }
            }
            
            // Show success message
            const originalText = $(this).text();
            $(this).text('<?php _e('Preferences Updated!', 'simple-cookie-consent'); ?>');
            setTimeout(function() {
                $('.scc-update-preferences').text(originalText);
            }, 2000);
        });
    });
</script>