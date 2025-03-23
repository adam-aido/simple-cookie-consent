# Simple Cookie Consent Plugin for WordPress

A lightweight yet powerful GDPR/CCPA compliant cookie consent plugin for WordPress that blocks cookies, localStorage, and sessionStorage until the user gives consent. Fully compatible with Google Consent Mode v2.

## Features

- **Pre-consent Blocking**: Automatically blocks cookies, localStorage, and sessionStorage before consent
- **Google Consent Mode v2**: Complete integration with Google's latest consent framework
- **Customizable UI**: Light/dark themes, position options, and fully customizable text
- **Multiple Consent Categories**: Necessary, Preferences, Analytics, Marketing, and Social Media
- **Responsive Design**: Looks great on both desktop and mobile devices
- **Multi-language Support**: Includes translation templates and examples
- **Shortcode Support**: Easily add consent forms or settings buttons anywhere

## Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher
- A modern web browser (Chrome, Firefox, Safari, Edge)
- JavaScript enabled in the browser

## Installation

1. Download the plugin and upload it to your `/wp-content/plugins/` directory
2. Activate the plugin through the WordPress Plugins menu
3. Go to Settings > Cookie Consent to configure the plugin

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

```
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

### 1.0.0

- Initial release
- Core cookie and storage blocking functionality
- Google Consent Mode v2 integration
- Customizable UI with light/dark themes
- Translation support
