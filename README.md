# Simple Cookie Consent Plugin for WordPress

A lightweight yet powerful GDPR/CCPA compliant cookie consent plugin for WordPress that blocks cookies, localStorage, and sessionStorage until the user gives consent. Fully compatible with Google Consent Mode v2.

## Features

- **Pre-consent Blocking**: Automatically blocks cookies, localStorage, and sessionStorage before consent
- **Google Consent Mode v2**: Complete integration with Google's latest consent framework
- **Database Storage**: Records all consent choices in a database table for auditing and compliance
- **Admin Dashboard**: View and export consent logs with user details and timestamps
- **Customizable UI**: Light/dark themes, position options, and fully customizable text
- **Multiple Consent Categories**: Necessary, Preferences, Analytics, Marketing, and Social Media
- **Responsive Design**: Looks great on both desktop and mobile devices
- **Multi-language Support**: Includes translation templates and examples
- **Shortcode Support**: Easily add consent forms or settings buttons anywhere

## Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- A modern web browser (Chrome, Firefox, Safari, Edge)
- JavaScript enabled in the browser

## Installation

1. Download the plugin and upload it to your `/wp-content/plugins/` directory
2. Activate the plugin through the WordPress Plugins menu
3. Go to Settings > Cookie Consent to configure the plugin

## Database Storage & Compliance

This plugin records all consent choices in a dedicated database table (`wp_cookie_consents`) which includes:

- User ID (for logged-in users)
- IP address (can be anonymized)
- User agent
- Consent type and details
- Timestamp of consent

### Consent Log Dashboard

Administrators can access a consent log at Settings > Consent Log to:

1. View all consent records with filterable columns
2. See which specific categories were accepted/rejected
3. Export consent records to CSV for compliance documentation
4. Track consent changes over time

### IP Anonymization

For GDPR compliance, the plugin can anonymize IP addresses before storing them:

- IPv4 addresses have the last octet replaced with zeros (e.g., 192.168.1.1 → 192.168.1.0)
- IPv6 addresses have the last 80 bits (last 5 hextets) replaced with zeros

This option is enabled by default and can be configured in settings.

## Usage

### Basic Setup

1. After activating the plugin, go to Settings > Cookie Consent
2. Configure the general settings, including cookie expiry time
3. Customize the appearance (position and theme)
4. Edit the text for the consent banner and buttons
5. Enable/disable Google Consent Mode and set the region

### Google Consent Mode v2 Setup

1. Go to the Google Consent Mode tab in settings
2. Enable Google Consent Mode integration
3. Set your region (e.g., "EU" for European Union)
4. Optionally add your Google Tag ID

### Using Shortcodes

The plugin provides two shortcodes that can be used anywhere on your site:

#### Display a Consent Form

```html
[cookie_consent theme="light" title="Cookie Settings" button_text="Update Preferences"]
```

Parameters:

- `theme`: light or dark (default: matches your settings)
- `title`: The heading for the form (default: "Cookie Settings")
- `button_text`: Text for the save button (default: "Update Preferences")

#### Display a Settings Button

```html
[cookie_settings text="Cookie Settings" class="my-custom-class"]
```

Parameters:

- `text`: The button text (default: "Cookie Settings")
- `class`: CSS class for styling (default: "scc-settings-button")

## Security Features

This plugin includes robust security measures:

- Input validation and sanitization for all settings
- Protection against XSS and CSRF attacks
- Secure cookie storage with SameSite and Secure flags
- AJAX security with proper nonce verification
- Comprehensive error handling

## Customization

### CSS Customization

You can add custom CSS to your theme to override the default styles of the cookie consent banner and modal.

### Filter Hooks

The plugin provides several filters for developers to extend functionality:

```php
// Modify consent types
add_filter('simple_cookie_consent_types', function($types) {
    // Add or modify consent types
    $types['my_custom_type'] = [
        'id' => 'my_custom_type',
        'label' => 'My Custom Type',
        'description' => 'Description for my custom consent type',
        'required' => false,
        'gcm_purpose' => 'personalization_storage'
    ];
    return $types;
});
```

## Browser Compatibility

- Chrome: 60+
- Firefox: 60+
- Safari: 12+
- Edge: 79+
- Opera: 47+
- Mobile browsers: Modern versions

## License

This plugin is released under the Unlicense. This places the software in the public domain, allowing anyone to use, modify, share, or sell it without restriction.

### The Unlicense

```text
This is free and unencumbered software released into the public domain.

Anyone is free to copy, modify, publish, use, compile, sell, or
distribute this software, either in source code form or as a compiled
binary, for any purpose, commercial or non-commercial, and by any
means.

In jurisdictions that recognize copyright laws, the author or authors
of this software dedicate any and all copyright interest in the
software to the public domain. We make this dedication for the benefit
of the public at large and to the detriment of our heirs and
successors. We intend this dedication to be an overt act of
relinquishment in perpetuity of all present and future rights to this
software under copyright law.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS BE LIABLE FOR ANY CLAIM, DAMAGES OR
OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,
ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.

For more information, please refer to <http://unlicense.org/>
```

## Contributing

Contributions are welcome! Feel free to fork the repository, make your changes, and submit a pull request.

## Changelog

### 1.1.2 (March 23, 2025)

- **Bug Fixes:**
  - Fixed localStorage and sessionStorage override error in modern browsers
  - Implemented method interception instead of property override for storage APIs
  - Improved error handling and graceful degradation
  - Enhanced console logging for better debugging

### 1.1.1 (March 23, 2025)

- **Bug Fixes:**
  - Fixed fatal error when loading WP_List_Table class
  - Improved admin page loading for consent log
  - Separated list table functionality into dedicated class
  - Fixed potential admin-only functions conflict

### 1.1.0 (March 23, 2025)

- **New Features:**
  - Database storage of consent records
  - Admin consent log with CSV export
  - IP anonymization for GDPR compliance

- **Technical Improvements:**
  - Enhanced security for data storage
  - Database version tracking and updates
  - Optimized query performance

### 1.0.0 (February 15, 2025)

- **New Features:**
  - Core cookie and storage blocking functionality
  - Google Consent Mode v2 integration
  - Customizable UI with light/dark themes
  - Shortcode support for forms and buttons
  - Translation support with Polish and British English examples

- **Technical Improvements:**
  - WordPress 6.7 compatibility
  - Modular code architecture
  - Security hardening and XSS protection
  - SameSite and Secure cookie attributes
  - Input validation and sanitization
  - CSRF protection

- **Documentation:**
  - Comprehensive README with examples
  - Proper code comments
  - Translation templates
