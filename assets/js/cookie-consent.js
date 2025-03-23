/**
 * Cookie Consent Handler - Fixed Boolean Handling
 */
(function($) {
    'use strict';

    // Initialize once DOM is ready
    $(document).ready(function() {
        // Check if consent was already given
        if (!hasCookieConsent()) {
            // Show the banner
            $('.scc-banner').fadeIn(300);
        }
        
        // Setup event handlers
        setupEventHandlers();
        
        // Initialize Google Consent Mode if enabled
        initGoogleConsentMode();
    });
    
    /**
     * Check if cookie consent has been given
     */
    function hasCookieConsent() {
        return document.cookie.indexOf('simple_cookie_consent_accepted=1') !== -1;
    }
    
    /**
     * Get consent details from cookie - with security improvements
     */
    function getConsentDetails() {
        try {
            const consentCookie = document.cookie
                .split('; ')
                .find(row => row.startsWith('simple_cookie_consent_details='));
            
            if (consentCookie) {
                try {
                    // Parse JSON safely
                    const cookieValue = decodeURIComponent(consentCookie.split('=')[1]);
                    const details = JSON.parse(cookieValue);
                    
                    // Validate the structure to prevent injection
                    if (typeof details === 'object' && details !== null) {
                        // Ensure all values are booleans
                        Object.keys(details).forEach(key => {
                            details[key] = !!details[key]; // Force boolean
                        });
                        return details;
                    }
                } catch (parseError) {
                    console.error('Error parsing consent details', parseError);
                }
            }
        } catch (e) {
            console.error('Error reading consent cookie', e);
        }
        
        return null;
    }
    
    /**
     * Setup all event handlers
     */
    function setupEventHandlers() {
        // Accept all cookies
        $('.scc-accept-all').on('click', function(e) {
            e.preventDefault();
            acceptAllCookies();
        });
        
        // Accept only essential cookies
        $('.scc-accept-essential').on('click', function(e) {
            e.preventDefault();
            acceptEssentialCookies();
        });
        
        // Open customization modal
        $('.scc-customize').on('click', function(e) {
            e.preventDefault();
            openCustomizeModal();
        });
        
        // Save preferences from modal
        $('.scc-save-preferences').on('click', function(e) {
            e.preventDefault();
            savePreferences();
        });
        
        // Tab navigation
        $('.scc-tab').on('click', function() {
            const tabId = $(this).data('tab');
            if (!tabId) return;
            
            $('.scc-tab').removeClass('scc-active');
            $(this).addClass('scc-active');
            
            $('.scc-tab-content').removeClass('scc-active');
            $('#' + tabId).addClass('scc-active');
        });
        
        // Close modal when clicking outside
        $('.scc-modal').on('click', function(e) {
            if ($(e.target).hasClass('scc-modal')) {
                closeCustomizeModal();
            }
        });
        
        // Global function to open modal (for use with shortcode)
        window.openCookieModal = function() {
            openCustomizeModal();
        };
    }
    
    /**
     * Initialize Google Consent Mode v2 if enabled
     */
    function initGoogleConsentMode() {
        if (!simpleCookieConsent || !simpleCookieConsent.googleConsentMode) {
            return;
        }
        
        try {
            // Google Consent Mode v2 initial setup
            if (!window.gtag) {
                window.dataLayer = window.dataLayer || [];
                window.gtag = function() { 
                    window.dataLayer.push(arguments); 
                };
            }
            
            // Only set default if consent hasn't been given
            if (!hasCookieConsent()) {
                gtag('consent', 'default', {
                    'ad_storage': 'denied',
                    'analytics_storage': 'denied',
                    'functionality_storage': 'denied',
                    'personalization_storage': 'denied',
                    'security_storage': 'granted'
                });
                
                // Set region if specified
                if (simpleCookieConsent.googleConsentRegion) {
                    gtag('consent', 'default', {
                        'region': [simpleCookieConsent.googleConsentRegion]
                    });
                }
                
                // Add wait for update function
                gtag('set', 'ads_data_redaction', true);
                gtag('set', 'url_passthrough', true);
            } else {
                // If consent exists, update based on stored preferences
                const details = getConsentDetails();
                if (details) {
                    updateGoogleConsentMode(details);
                }
            }
            
            // Load Google Tag if ID is provided
            if (simpleCookieConsent.googleTagId) {
                loadGoogleTag(simpleCookieConsent.googleTagId);
            }
        } catch (e) {
            console.error('Error initializing Google Consent Mode:', e);
        }
    }
    
    /**
     * Load Google Tag script safely
     */
    function loadGoogleTag(tagId) {
        if (!tagId || typeof tagId !== 'string') {
            return;
        }
        
        // Validate tag ID format (basic validation)
        if (!/^G-[A-Z0-9]+$|^UA-[0-9]+-[0-9]+$|^GTM-[A-Z0-9]+$/.test(tagId)) {
            console.error('Invalid Google Tag ID format');
            return;
        }
        
        try {
            // Only load if not already loaded
            if (!document.querySelector('script[src*="googletagmanager.com/gtag/js?id=' + tagId + '"]')) {
                const script = document.createElement('script');
                script.async = true;
                script.src = 'https://www.googletagmanager.com/gtag/js?id=' + tagId;
                document.head.appendChild(script);
                
                gtag('js', new Date());
                gtag('config', tagId);
            }
        } catch (e) {
            console.error('Error loading Google Tag script:', e);
        }
    }
    
    /**
     * Accept all cookies
     */
    function acceptAllCookies() {
        const consentDetails = {};
        
        // Mark all cookie types as accepted
        $.each(simpleCookieConsent.consentTypes, function(index, type) {
            consentDetails[type.id] = true;
        });
        
        consentDetails.googleConsentMode = simpleCookieConsent.googleConsentMode;
        
        // Set cookies
        setCookieConsent(true, consentDetails);
        
        // Hide banner and modal
        $('.scc-banner').fadeOut(300);
        closeCustomizeModal();
        
        // Update Google Consent Mode if enabled
        updateGoogleConsentMode(consentDetails);
        
        // AJAX call to notify server and store in database
        sendConsentToServer(true, consentDetails);
    }
    
    /**
     * Accept only essential cookies
     */
    function acceptEssentialCookies() {
        const consentDetails = {};
        
        // Mark only essential cookies as accepted
        $.each(simpleCookieConsent.consentTypes, function(index, type) {
            consentDetails[type.id] = type.required ? true : false;
        });
        
        consentDetails.googleConsentMode = simpleCookieConsent.googleConsentMode;
        
        // Set cookies
        setCookieConsent(true, consentDetails);
        
        // Hide banner and modal
        $('.scc-banner').fadeOut(300);
        closeCustomizeModal();
        
        // Update Google Consent Mode if enabled
        updateGoogleConsentMode(consentDetails);
        
        // AJAX call to notify server and store in database
        sendConsentToServer(true, consentDetails);
    }
    
    /**
     * Open the customization modal
     */
    function openCustomizeModal() {
        // Pre-load existing preferences
        const details = getConsentDetails();
        
        // Mark required cookies as checked and disabled
        $.each(simpleCookieConsent.consentTypes, function(index, type) {
            const $checkbox = $('#scc-consent-' + type.id);
            if (!$checkbox.length) return;
            
            if (type.required) {
                $checkbox.prop('checked', true).prop('disabled', true);
            } else if (details && typeof details[type.id] !== 'undefined') {
                // Set checkbox state from saved preferences
                $checkbox.prop('checked', !!details[type.id]);
            } else {
                // Default to unchecked for non-required types with no saved preference
                $checkbox.prop('checked', false);
            }
        });
        
        // Show modal
        $('.scc-modal').css('display', 'flex');
    }
    
    /**
     * Close the customization modal
     */
    function closeCustomizeModal() {
        $('.scc-modal').fadeOut(300);
    }
    
    /**
     * Save user preferences from modal
     */
    function savePreferences() {
        const consentDetails = {};
        
        // Get all toggle states
        $.each(simpleCookieConsent.consentTypes, function(index, type) {
            const $checkbox = $('#scc-consent-' + type.id);
            // Explicitly set as true or false
            consentDetails[type.id] = $checkbox.length ? $checkbox.is(':checked') : false;
        });
        
        consentDetails.googleConsentMode = simpleCookieConsent.googleConsentMode;
        
        // Log for debugging
        if (simpleCookieConsent.debugMode) {
            console.log('Saving preferences:', consentDetails);
        }
        
        // Set cookies
        setCookieConsent(true, consentDetails);
        
        // Hide banner and modal
        $('.scc-banner').fadeOut(300);
        closeCustomizeModal();
        
        // Update Google Consent Mode if enabled
        updateGoogleConsentMode(consentDetails);
        
        // AJAX call to notify server and store in database
        sendConsentToServer(true, consentDetails);
    }
    
    /**
     * Set cookie consent status and details
     */
    function setCookieConsent(accepted, details = {}) {
        const expiryDays = parseInt(simpleCookieConsent.cookieExpiry) || 180;
        const date = new Date();
        date.setTime(date.getTime() + (expiryDays * 24 * 60 * 60 * 1000));
        const expires = 'expires=' + date.toUTCString();
        
        // Set the main acceptance cookie
        document.cookie = 'simple_cookie_consent_accepted=' + (accepted ? '1' : '0') + '; ' + expires + '; path=/; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
        
        // Store details if provided
        if (!$.isEmptyObject(details)) {
            try {
                const safeDetails = {};
                
                // Force all values to be true or false (explicit booleans)
                Object.keys(details).forEach(key => {
                    safeDetails[key] = details[key] === true;
                });
                
                document.cookie = 'simple_cookie_consent_details=' + 
                    encodeURIComponent(JSON.stringify(safeDetails)) + 
                    '; ' + expires + '; path=/; SameSite=Lax' +
                    (location.protocol === 'https:' ? '; Secure' : '');
            } catch (e) {
                console.error('Error setting consent details cookie:', e);
            }
        }
        
        // Enable storage according to preferences
        if (typeof window.enableCookiesAndStorage === 'function') {
            window.enableCookiesAndStorage(details);
        }
    }
    
    /**
     * Update Google Consent Mode with user preferences
     */
    function updateGoogleConsentMode(consentDetails) {
        if (!simpleCookieConsent.googleConsentMode || typeof gtag !== 'function') {
            return;
        }
        
        try {
            gtag('consent', 'update', {
                'ad_storage': consentDetails.marketing === true ? 'granted' : 'denied',
                'analytics_storage': consentDetails.analytics === true ? 'granted' : 'denied',
                'functionality_storage': consentDetails.necessary === true ? 'granted' : 'denied',
                'personalization_storage': consentDetails.social === true ? 'granted' : 'denied',
                'security_storage': 'granted' // Always granted for security
            });
        } catch (e) {
            console.error('Error updating Google Consent Mode:', e);
        }
    }
    
    /**
     * Send consent data to server via AJAX
     */
    function sendConsentToServer(accepted, details) {
        // Check if AJAX settings are available
        if (!simpleCookieConsent || !simpleCookieConsent.ajaxUrl) {
            console.warn('AJAX URL not available, skipping server-side consent storage');
            return;
        }
        
        // Get WordPress admin-ajax URL
        const ajaxUrl = simpleCookieConsent.ajaxUrl;
        
        // Clone the details to avoid modifying the original object
        const safeDetails = {};
        
        // Sanitize details - convert to explicit booleans
        Object.keys(details || {}).forEach(key => {
            // Force explicit boolean values (true or false)
            safeDetails[key] = details[key] === true;
        });
        
        // Debug log
        if (simpleCookieConsent.debugMode) {
            console.log('Sending to server:', safeDetails);
        }
        
        // Use WordPress admin-ajax.php endpoint
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'simple_cookie_set_consent', // WordPress action hook
                nonce: simpleCookieConsent.nonce || '',
                accepted: accepted ? 1 : 0,
                details: safeDetails
            },
            success: function(response) {
                if (response && response.success) {
                    console.log('Consent saved to server');
                } else {
                    console.warn('Server response indicates error:', response);
                }
            },
            error: function(xhr, status, error) {
                // Log detailed error information for debugging
                console.error('AJAX error saving consent:', error);
                console.log('Status code:', xhr.status);
                console.log('Response text:', xhr.responseText);
                
                // Continue without server-side storage
                console.warn('Unable to save consent to server, continuing with client-side consent only');
            }
        });
    }
    
})(jQuery);