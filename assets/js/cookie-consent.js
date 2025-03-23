/**
 * Cookie Consent Handler
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
     * Get consent details from cookie
     */
    function getConsentDetails() {
        try {
            const consentCookie = document.cookie
                .split('; ')
                .find(row => row.startsWith('simple_cookie_consent_details='));
            
            if (consentCookie) {
                return JSON.parse(decodeURIComponent(consentCookie.split('=')[1]));
            }
        } catch (e) {
            console.error('Error parsing consent details', e);
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
        if (simpleCookieConsent.googleConsentMode) {
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
        }
    }
    
    /**
     * Load Google Tag script
     */
    function loadGoogleTag(tagId) {
        // Only load if not already loaded
        if (!document.querySelector('script[src*="googletagmanager.com/gtag/js?id=' + tagId + '"]')) {
            const script = document.createElement('script');
            script.async = true;
            script.src = 'https://www.googletagmanager.com/gtag/js?id=' + tagId;
            document.head.appendChild(script);
            
            gtag('js', new Date());
            gtag('config', tagId);
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
        
        // AJAX call to notify server
        sendConsentToServer(true, consentDetails);
    }
    
    /**
     * Accept only essential cookies
     */
    function acceptEssentialCookies() {
        const consentDetails = {};
        
        // Mark only essential cookies as accepted
        $.each(simpleCookieConsent.consentTypes, function(index, type) {
            consentDetails[type.id] = type.required;
        });
        
        consentDetails.googleConsentMode = simpleCookieConsent.googleConsentMode;
        
        // Set cookies
        setCookieConsent(true, consentDetails);
        
        // Hide banner and modal
        $('.scc-banner').fadeOut(300);
        closeCustomizeModal();
        
        // Update Google Consent Mode if enabled
        updateGoogleConsentMode(consentDetails);
        
        // AJAX call to notify server
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
            
            if (type.required) {
                $checkbox.prop('checked', true).prop('disabled', true);
            } else if (details && typeof details[type.id] !== 'undefined') {
                // Set checkbox state from saved preferences
                $checkbox.prop('checked', details[type.id]);
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
            consentDetails[type.id] = $('#scc-consent-' + type.id).is(':checked');
        });
        
        consentDetails.googleConsentMode = simpleCookieConsent.googleConsentMode;
        
        // Set cookies
        setCookieConsent(true, consentDetails);
        
        // Hide banner and modal
        $('.scc-banner').fadeOut(300);
        closeCustomizeModal();
        
        // Update Google Consent Mode if enabled
        updateGoogleConsentMode(consentDetails);
        
        // AJAX call to notify server
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
        document.cookie = 'simple_cookie_consent_accepted=' + (accepted ? '1' : '0') + '; ' + expires + '; path=/; SameSite=Lax';
        
        // Store details if provided
        if (!$.isEmptyObject(details)) {
            document.cookie = 'simple_cookie_consent_details=' + encodeURIComponent(JSON.stringify(details)) + '; ' + expires + '; path=/; SameSite=Lax';
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
        if (simpleCookieConsent.googleConsentMode && typeof gtag === 'function') {
            gtag('consent', 'update', {
                'ad_storage': consentDetails.marketing ? 'granted' : 'denied',
                'analytics_storage': consentDetails.analytics ? 'granted' : 'denied',
                'functionality_storage': consentDetails.necessary ? 'granted' : 'denied',
                'personalization_storage': consentDetails.social ? 'granted' : 'denied',
                'security_storage': 'granted' // Always granted for security
            });
        }
    }
    
    /**
     * Send consent data to server via AJAX
     */
    function sendConsentToServer(accepted, details) {
        $.ajax({
            url: simpleCookieConsent.ajaxUrl,
            type: 'POST',
            data: {
                action: 'simple_cookie_set_consent',
                nonce: simpleCookieConsent.nonce,
                accepted: accepted ? 1 : 0,
                details: details
            },
            success: function(response) {
                console.log('Consent saved to server:', response);
            },
            error: function(xhr, status, error) {
                console.error('Error saving consent:', error);
            }
        });
    }
    
})(jQuery);