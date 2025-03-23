<?php
/**
 * Consent banner template
 *
 * @package Simple_Cookie_Consent
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

$position = esc_attr(get_option('simple_cookie_consent_position', 'bottom'));
$theme = esc_attr(get_option('simple_cookie_consent_theme', 'light'));
$title = esc_html(get_option('simple_cookie_consent_title', __('Cookie Consent', 'simple-cookie-consent')));
$text = esc_html(get_option('simple_cookie_consent_text', __('This website uses cookies to ensure you get the best experience.', 'simple-cookie-consent')));
$accept_all = esc_html(get_option('simple_cookie_consent_accept_all', __('Accept All', 'simple-cookie-consent')));
$accept_essential = esc_html(get_option('simple_cookie_consent_accept_essential', __('Accept Only Essential', 'simple-cookie-consent')));
$customize = esc_html(get_option('simple_cookie_consent_customize', __('Customize', 'simple-cookie-consent')));
?>

<div class="scc-banner scc-position-<?php echo $position; ?> scc-theme-<?php echo $theme; ?>">
    <div class="scc-banner-content">
        <div class="scc-banner-text">
            <div class="scc-banner-title"><?php echo $title; ?></div>
            <div class="scc-banner-message"><?php echo $text; ?></div>
        </div>
        <div class="scc-banner-buttons">
            <button class="scc-button scc-button-primary scc-accept-all"><?php echo $accept_all; ?></button>
            <button class="scc-button scc-button-secondary scc-accept-essential"><?php echo $accept_essential; ?></button>
            <button class="scc-button scc-button-tertiary scc-customize"><?php echo $customize; ?></button>
        </div>
    </div>
</div>

<?php
// Include the consent modal template
include SIMPLE_COOKIE_CONSENT_TEMPLATES_DIR . 'consent-modal.php';
?>