<?php
/**
 * Admin settings page template
 *
 * @package Simple_Cookie_Consent
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="nav-tab-wrapper">
        <a href="#general" class="nav-tab nav-tab-active"><?php _e('General', 'simple-cookie-consent'); ?></a>
        <a href="#appearance" class="nav-tab"><?php _e('Appearance', 'simple-cookie-consent'); ?></a>
        <a href="#texts" class="nav-tab"><?php _e('Text Settings', 'simple-cookie-consent'); ?></a>
        <a href="#gcm" class="nav-tab"><?php _e('Google Consent Mode', 'simple-cookie-consent'); ?></a>
    </div>
    
    <div class="tab-container">
        <form method="post" action="options.php">
            <!-- General Settings Tab -->
            <div id="general" class="tab-content active">
                <?php settings_fields('simple_cookie_consent_general'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="simple_cookie_consent_expiry"><?php _e('Cookie Expiry (days)', 'simple-cookie-consent'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="simple_cookie_consent_expiry" name="simple_cookie_consent_expiry" 
                                value="<?php echo esc_attr($expiry); ?>" min="1" max="365" />
                            <p class="description"><?php _e('Number of days until the consent expires and the banner is shown again.', 'simple-cookie-consent'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Appearance Settings Tab -->
            <div id="appearance" class="tab-content" style="display:none;">
                <?php settings_fields('simple_cookie_consent_appearance'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Banner Position', 'simple-cookie-consent'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="radio" name="simple_cookie_consent_position" value="bottom" 
                                        <?php checked('bottom', $position); ?> />
                                    <?php _e('Bottom of screen', 'simple-cookie-consent'); ?>
                                </label><br>
                                <label>
                                    <input type="radio" name="simple_cookie_consent_position" value="top" 
                                        <?php checked('top', $position); ?> />
                                    <?php _e('Top of screen', 'simple-cookie-consent'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Color Theme', 'simple-cookie-consent'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="radio" name="simple_cookie_consent_theme" value="light" 
                                        <?php checked('light', $theme); ?> />
                                    <?php _e('Light', 'simple-cookie-consent'); ?>
                                </label><br>
                                <label>
                                    <input type="radio" name="simple_cookie_consent_theme" value="dark" 
                                        <?php checked('dark', $theme); ?> />
                                    <?php _e('Dark', 'simple-cookie-consent'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Text Settings Tab -->
            <div id="texts" class="tab-content" style="display:none;">
                <?php settings_fields('simple_cookie_consent_texts'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="simple_cookie_consent_title"><?php _e('Banner Title', 'simple-cookie-consent'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="simple_cookie_consent_title" name="simple_cookie_consent_title" 
                                value="<?php echo esc_attr($title); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="simple_cookie_consent_text"><?php _e('Banner Text', 'simple-cookie-consent'); ?></label>
                        </th>
                        <td>
                            <textarea id="simple_cookie_consent_text" name="simple_cookie_consent_text" 
                                rows="4" class="large-text"><?php echo esc_textarea($text); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="simple_cookie_consent_accept_all"><?php _e('Accept All Button', 'simple-cookie-consent'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="simple_cookie_consent_accept_all" name="simple_cookie_consent_accept_all" 
                                value="<?php echo esc_attr($accept_all); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="simple_cookie_consent_accept_essential"><?php _e('Accept Essential Button', 'simple-cookie-consent'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="simple_cookie_consent_accept_essential" name="simple_cookie_consent_accept_essential" 
                                value="<?php echo esc_attr($accept_essential); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="simple_cookie_consent_customize"><?php _e('Customize Button', 'simple-cookie-consent'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="simple_cookie_consent_customize" name="simple_cookie_consent_customize" 
                                value="<?php echo esc_attr($customize); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="simple_cookie_consent_save_preferences"><?php _e('Save Preferences Button', 'simple-cookie-consent'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="simple_cookie_consent_save_preferences" name="simple_cookie_consent_save_preferences" 
                                value="<?php echo esc_attr($save_preferences); ?>" class="regular-text" />
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Google Consent Mode Settings Tab -->
            <div id="gcm" class="tab-content" style="display:none;">
                <?php settings_fields('simple_cookie_consent_gcm'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Enable Google Consent Mode', 'simple-cookie-consent'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="radio" name="simple_cookie_consent_gcm_enabled" value="yes" 
                                        <?php checked('yes', $gcm_enabled); ?> />
                                    <?php _e('Enabled', 'simple-cookie-consent'); ?>
                                </label><br>
                                <label>
                                    <input type="radio" name="simple_cookie_consent_gcm_enabled" value="no" 
                                        <?php checked('no', $gcm_enabled); ?> />
                                    <?php _e('Disabled', 'simple-cookie-consent'); ?>
                                </label>
                            </fieldset>
                            <p class="description"><?php _e('Enable Google Consent Mode v2 integration.', 'simple-cookie-consent'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="simple_cookie_consent_gcm_region"><?php _e('Region', 'simple-cookie-consent'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="simple_cookie_consent_gcm_region" name="simple_cookie_consent_gcm_region" 
                                value="<?php echo esc_attr($gcm_region); ?>" class="regular-text" />
                            <p class="description"><?php _e('The region where consent is required (e.g., EU). Leave blank for all regions.', 'simple-cookie-consent'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="simple_cookie_consent_gcm_tag_id"><?php _e('Google Tag ID', 'simple-cookie-consent'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="simple_cookie_consent_gcm_tag_id" name="simple_cookie_consent_gcm_tag_id" 
                                value="<?php echo esc_attr($gcm_tag_id); ?>" class="regular-text" placeholder="G-XXXXXXXXXX" />
                            <p class="description"><?php _e('Your Google Tag ID (optional).', 'simple-cookie-consent'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <?php submit_button(); ?>
        </form>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        // Tab navigation
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            var target = $(this).attr('href');
            
            // Update active tab
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            // Show target content
            $('.tab-content').hide();
            $(target).show();
        });
    });
</script>