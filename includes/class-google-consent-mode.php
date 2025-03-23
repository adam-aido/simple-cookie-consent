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
            // Add Google Consent Mode initialization script in head with highest priority
            add_action('wp_head', array($this, 'add_gcm_initialization'), 1);
            
            // Filter YouTube and Google Maps embeds
            add_filter('embed_oembed_html', array($this, 'filter_embeds'), 10, 4);
            add_filter('the_content', array($this, 'process_content_embeds'));
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
<script data-cookieconsent="ignore">
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
// Initialize gtag - but don't load the script until consent
window.SCC_TAG_ID = '<?php echo esc_js($tag_id); ?>';
<?php endif; ?>
</script>
        <?php
        echo ob_get_clean();
    }
    
    /**
     * Filter oEmbed HTML for YouTube and other providers
     *
     * @param string $html The embed HTML
     * @param string $url The URL embedded
     * @param array $attr Additional attributes
     * @param int $post_id Post ID
     * @return string Modified HTML
     */
    public function filter_embeds($html, $url, $attr, $post_id) {
        // Check if embed is YouTube
        if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
            return $this->process_youtube_embed($html, $url);
        }
        
        // Check if embed is from Vimeo
        if (strpos($url, 'vimeo.com') !== false) {
            return $this->process_video_embed($html, 'social');
        }
        
        // Check if embed is from Google Maps
        if (strpos($url, 'google.com/maps') !== false || strpos($url, 'maps.google.com') !== false) {
            return $this->process_video_embed($html, 'preferences');
        }
        
        return $html;
    }
    
    /**
     * Process YouTube embeds specifically
     *
     * @param string $html The original embed HTML
     * @param string $url The embed URL
     * @return string The modified HTML
     */
    private function process_youtube_embed($html, $url) {
        // Get consent cookie if it exists
        $has_consent = false;
        $consent_details = array();
        
        if (isset($_COOKIE['simple_cookie_consent_accepted']) && $_COOKIE['simple_cookie_consent_accepted'] === '1') {
            $has_consent = true;
            
            if (isset($_COOKIE['simple_cookie_consent_details'])) {
                $consent_details = json_decode(stripslashes($_COOKIE['simple_cookie_consent_details']), true);
            }
        }
        
        // Check if social consent is given
        $social_consent = isset($consent_details['social']) && $consent_details['social'] === true;
        
        if ($has_consent && $social_consent) {
            // If consent is given, return the normal embed
            return $html;
        }
        
        // No consent yet - replace with placeholder
        // Extract video ID
        $video_id = '';
        if (preg_match('/youtube\.com\/embed\/([^\/\?]+)/', $html, $matches)) {
            $video_id = $matches[1];
        } elseif (preg_match('/youtu\.be\/([^\/\?]+)/', $url, $matches)) {
            $video_id = $matches[1];
        } elseif (preg_match('/youtube\.com\/watch\?v=([^&]+)/', $url, $matches)) {
            $video_id = $matches[1];
        }
        
        // If we can't get the video ID, just return a generic placeholder
        if (empty($video_id)) {
            return $this->get_embed_placeholder(
                __('YouTube Video', 'simple-cookie-consent'),
                __('Please accept cookies to view this YouTube video.', 'simple-cookie-consent'),
                'social'
            );
        }
        
        // Get a thumbnail URL from YouTube
        $thumbnail_url = "https://img.youtube.com/vi/{$video_id}/0.jpg";
        
        return $this->get_embed_placeholder(
            __('YouTube Video', 'simple-cookie-consent'),
            __('Please accept cookies to view this YouTube video.', 'simple-cookie-consent'),
            'social',
            $thumbnail_url,
            $html
        );
    }
    
    /**
     * Process generic video embeds
     *
     * @param string $html The original embed HTML
     * @param string $consent_type The consent type required
     * @return string The modified HTML
     */
    private function process_video_embed($html, $consent_type = 'social') {
        // Get consent cookie if it exists
        $has_consent = false;
        $consent_details = array();
        
        if (isset($_COOKIE['simple_cookie_consent_accepted']) && $_COOKIE['simple_cookie_consent_accepted'] === '1') {
            $has_consent = true;
            
            if (isset($_COOKIE['simple_cookie_consent_details'])) {
                $consent_details = json_decode(stripslashes($_COOKIE['simple_cookie_consent_details']), true);
            }
        }
        
        // Check if required consent is given
        $has_required_consent = isset($consent_details[$consent_type]) && $consent_details[$consent_type] === true;
        
        if ($has_consent && $has_required_consent) {
            // If consent is given, return the normal embed
            return $html;
        }
        
        // No consent yet - replace with placeholder
        $title = '';
        $message = '';
        
        if ($consent_type === 'social') {
            $title = __('Video Content', 'simple-cookie-consent');
            $message = __('Please accept Social Media cookies to view this video.', 'simple-cookie-consent');
        } elseif ($consent_type === 'preferences') {
            $title = __('Google Maps', 'simple-cookie-consent');
            $message = __('Please accept Preferences cookies to view this map.', 'simple-cookie-consent');
        }
        
        return $this->get_embed_placeholder($title, $message, $consent_type, '', $html);
    }
    
    /**
     * Get HTML for embed placeholder
     *
     * @param string $title Title for the placeholder
     * @param string $message Message for the placeholder
     * @param string $consent_type Type of consent required
     * @param string $image_url Optional thumbnail URL
     * @param string $original_embed Original embed HTML to restore after consent
     * @return string Placeholder HTML
     */
    private function get_embed_placeholder($title, $message, $consent_type, $image_url = '', $original_embed = '') {
        // Store original embed as a data attribute (escaped)
        $data_original = '';
        if (!empty($original_embed)) {
            $data_original = 'data-original-embed="' . esc_attr(base64_encode($original_embed)) . '"';
        }
        
        // Default background color
        $bg_color = '#f0f0f0';
        
        // Different styling based on consent type
        if ($consent_type === 'social') {
            $bg_color = '#e6f2ff';
            $icon = 'dashicons-share';
        } elseif ($consent_type === 'preferences') {
            $bg_color = '#e6ffe6';
            $icon = 'dashicons-location';
        } elseif ($consent_type === 'analytics') {
            $bg_color = '#fff2e6';
            $icon = 'dashicons-chart-bar';
        } elseif ($consent_type === 'marketing') {
            $bg_color = '#ffe6e6';
            $icon = 'dashicons-megaphone';
        } else {
            $icon = 'dashicons-lock';
        }
        
        // Style for the placeholder
        $style = "
            background-color: {$bg_color};
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            text-align: center;
            max-width: 100%;
            position: relative;
            margin: 10px 0;
        ";
        
        // Add background image if provided
        if (!empty($image_url)) {
            $style .= "
                background-image: url('{$image_url}');
                background-size: cover;
                background-position: center;
                color: white;
                text-shadow: 0 0 5px black;
                min-height: 200px;
                display: flex;
                flex-direction: column;
                justify-content: center;
            ";
        }
        
        // Create the HTML
        $html = "
        <div class='scc-embed-placeholder' data-consent-type='{$consent_type}' {$data_original} style='{$style}'>
            <h3 style='margin-top: 0;'>{$title}</h3>
            <p>{$message}</p>
            <button class='scc-embed-consent-btn' onclick='window.openCookieModal()' style='
                background-color: #4CAF50;
                color: white;
                border: none;
                padding: 10px 20px;
                text-align: center;
                text-decoration: none;
                display: inline-block;
                font-size: 16px;
                margin: 4px 2px;
                cursor: pointer;
                border-radius: 4px;
            '>
                " . __('Cookie Settings', 'simple-cookie-consent') . "
            </button>
        </div>
        ";
        
        return $html;
    }
    
    /**
     * Process embeds in content
     *
     * @param string $content The post content
     * @return string Modified content
     */
    public function process_content_embeds($content) {
        // Process all iframes in content
        $content = preg_replace_callback(
            '/<iframe.+?src=[\'"]((?:https?:)?\/\/(?:www\.)?(?:youtube\.com|youtu\.be|player\.vimeo\.com|maps\.google\.com).+?)[\'"].*?><\/iframe>/i',
            array($this, 'replace_iframe_callback'),
            $content
        );
        
        return $content;
    }
    
    /**
     * Callback to replace iframe based on source
     *
     * @param array $matches Regex matches
     * @return string Replacement HTML
     */
    private function replace_iframe_callback($matches) {
        $iframe = $matches[0];
        $src = $matches[1];
        
        // Determine consent type based on source
        $consent_type = 'social'; // Default
        
        if (strpos($src, 'youtube.com') !== false || strpos($src, 'youtu.be') !== false) {
            return $this->process_youtube_embed($iframe, $src);
        } elseif (strpos($src, 'vimeo.com') !== false) {
            return $this->process_video_embed($iframe, 'social');
        } elseif (strpos($src, 'maps.google.com') !== false || strpos($src, 'google.com/maps') !== false) {
            return $this->process_video_embed($iframe, 'preferences');
        }
        
        // If we don't know what it is, return original
        return $iframe;
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