/**
 * Cookie Blocker Script - Runs before any cookies are set
 * Blocks cookies, localStorage and sessionStorage until consent is given
 */
(function() {
    'use strict';
    
    // Check if consent has already been given
    function hasConsent() {
        return document.cookie.indexOf('simple_cookie_consent_accepted=1') !== -1;
    }
    
    // Get consent details if available
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
    
    // Only proceed with blocking if no consent yet
    if (!hasConsent()) {
        console.log('Cookie Consent: Blocking cookies and storage until consent is given');
        
        // Store original methods
        const originalDocumentCookie = Object.getOwnPropertyDescriptor(Document.prototype, 'cookie');
        const originalLocalStorage = window.localStorage;
        const originalSessionStorage = window.sessionStorage;
        
        // Override document.cookie
        Object.defineProperty(Document.prototype, 'cookie', {
            get: function() {
                return originalDocumentCookie.get.call(this);
            },
            set: function(value) {
                // Allow setting the consent cookie itself
                if (value.indexOf('simple_cookie_consent_') === 0) {
                    originalDocumentCookie.set.call(this, value);
                    return value;
                }
                
                // Block all other cookies
                console.warn('Cookie blocked by consent manager:', value);
                return '';
            },
            configurable: true
        });
        
        // Create blocking storage
        function createBlockingStorage() {
            const items = {};
            return {
                setItem: function(key, value) {
                    console.warn('Storage blocked by consent manager:', key);
                },
                getItem: function(key) {
                    return null;
                },
                removeItem: function(key) {},
                clear: function() {},
                key: function(index) { return null; },
                get length() { return 0; }
            };
        }
        
        // Override localStorage and sessionStorage
        try {
            window.localStorage = createBlockingStorage();
            window.sessionStorage = createBlockingStorage();
        } catch (e) {
            console.error('Error overriding storage APIs:', e);
        }
        
        // Add function to restore original behavior
        window.enableCookiesAndStorage = function(consentDetails) {
            // Restore original document.cookie
            Object.defineProperty(Document.prototype, 'cookie', originalDocumentCookie);
            
            // Restore localStorage and sessionStorage only if respective consent is given
            if (consentDetails) {
                if (consentDetails.preferences === true) {
                    window.localStorage = originalLocalStorage;
                    window.sessionStorage = originalSessionStorage;
                }
                
                // Initialize Google Consent Mode v2 if enabled
                if (window.gtag && consentDetails.googleConsentMode) {
                    window.gtag('consent', 'update', {
                        'ad_storage': consentDetails.marketing ? 'granted' : 'denied',
                        'analytics_storage': consentDetails.analytics ? 'granted' : 'denied',
                        'functionality_storage': consentDetails.necessary ? 'granted' : 'denied',
                        'personalization_storage': consentDetails.social ? 'granted' : 'denied',
                        'security_storage': 'granted' // Always granted for security
                    });
                }
            } else {
                // If no details provided, restore everything (full consent)
                window.localStorage = originalLocalStorage;
                window.sessionStorage = originalSessionStorage;
            }
            
            console.log('Cookie Consent: Storage APIs restored according to preferences');
        };
    } else {
        // If consent exists, enable storage with saved preferences
        const consentDetails = getConsentDetails();
        if (typeof window.enableCookiesAndStorage === 'function') {
            window.enableCookiesAndStorage(consentDetails);
        }
    }
    
    // Setup a global function to check cookie consent status
    window.hasSimpleCookieConsent = hasConsent;
    window.getSimpleCookieConsentDetails = getConsentDetails;
    
    // Setup a global function to manually open the cookie modal
    window.openCookieModal = function() {
        const modal = document.querySelector('.scc-modal');
        if (modal) {
            modal.style.display = 'flex';
        }
    };
})();