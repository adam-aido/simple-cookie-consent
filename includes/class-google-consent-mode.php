<?php
/**
 * The Google Consent Mode v2 integration functionality.
 *
 * @package    Simple_Cookie_Consent
 */

class Simple_Cookie_Consent_Google_Consent_Mode {

    /**
     * Initialize the class.
     */
    public function init() {
        // Only add scripts if Google Consent Mode is enabled
        if (get_option('simple_cookie_consent_gcm_enabled', 'yes') === 'yes') {
            // Add Google Consent Mode initialization script in head
            add_action('wp_head', array($this, 'add_gcm_initialization'), 1);
        }
    }

    /**
     * Add Google Consent Mode v2 initialization script
     */
    public function add_gcm_initialization() {
        $region = get_option('simple_cookie_consent_gcm_region', 'EU');
        $tag_id = get_option('simple_cookie_consent_gcm_tag_id', '');
        
        // Default consent is denied for all purposes except security
        ob_start();
        ?>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}

// Default consent settings - denied by default
gtag('consent', 'default', {
    'ad_storage': 'denied',
    'analytics_storage': 'denied',
    'functionality_storage': 'denied',
    'personalization_storage': 'denied',
    'security_storage': 'granted', // Always granted for security
    <?php if (!empty($region)) : ?>
    'region': ['<?php echo esc_js($region); ?>']
    <?php endif; ?>
});

// Set these flags to ensure cookies are handled properly
gtag('set', 'ads_data_redaction', true);
gtag('set', 'url_passthrough', true);

<?php if (!empty($tag_id)) : ?>
// Load Google Tag script if tag ID is provided
(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtag/js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?php echo esc_js($tag_id); ?>');

// Initialize gtag
gtag('js', new Date());
gtag('config', '<?php echo esc_js($tag_id); ?>');
<?php endif; ?>
</script>
        <?php
        echo ob_get_clean();
    }
    
    /**
     * Map consent types to Google Consent Mode purposes
     * 
     * @param array $consent_details Consent details from user
     * @return array Google Consent Mode formatted consent
     */
    public static function map_consent_to_gcm($consent_details) {
        $gcm_consent = array(
            'ad_storage' => isset($consent_details['marketing']) && $consent_details['marketing'] ? 'granted' : 'denied',
            'analytics_storage' => isset($consent_details['analytics']) && $consent_details['analytics'] ? 'granted' : 'denied',
            'functionality_storage' => isset($consent_details['necessary']) && $consent_details['necessary'] ? 'granted' : 'denied',
            'personalization_storage' => isset($consent_details['social']) && $consent_details['social'] ? 'granted' : 'denied',
            'security_storage' => 'granted' // Always granted for security
        );
        
        return $gcm_consent;
    }
}