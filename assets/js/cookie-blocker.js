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
                // Use try-catch to handle potential JSON parsing errors
                try {
                    return JSON.parse(decodeURIComponent(consentCookie.split('=')[1]));
                } catch (parseError) {
                    console.error('Error parsing consent details JSON', parseError);
                    return null;
                }
            }
        } catch (e) {
            console.error('Error accessing consent details', e);
        }
        
        return null;
    }
    
    // Only proceed with blocking if no consent yet
    if (!hasConsent()) {
        console.log('Cookie Consent: Blocking cookies and storage until consent is given');
        
        // Store original methods
        let originalDocumentCookie;
        
        try {
            originalDocumentCookie = Object.getOwnPropertyDescriptor(Document.prototype, 'cookie');
        } catch (e) {
            console.error('Error capturing original cookie API:', e);
        }
        
        // Instead of trying to override localStorage/sessionStorage (which may be protected),
        // create proxy methods that intercept calls to them
        
        // Create storage intercepts for localStorage and sessionStorage
        function createStorageIntercept(storageType) {
            if (!window[storageType]) return;
            
            // Store original methods from storage
            const originalSetItem = window[storageType].setItem;
            const originalRemoveItem = window[storageType].removeItem;
            const originalClear = window[storageType].clear;
            
            // Override setItem to block storage
            window[storageType].setItem = function(key, value) {
                console.warn(`${storageType} blocked by consent manager:`, key);
                return undefined;
            };
            
            // Override removeItem to block storage
            window[storageType].removeItem = function(key) {
                console.warn(`${storageType} removeItem blocked by consent manager:`, key);
                return undefined;
            };
            
            // Override clear to block storage
            window[storageType].clear = function() {
                console.warn(`${storageType} clear blocked by consent manager`);
                return undefined;
            };
            
            // Return restore function for later
            return function() {
                window[storageType].setItem = originalSetItem;
                window[storageType].removeItem = originalRemoveItem;
                window[storageType].clear = originalClear;
                console.log(`${storageType} original methods restored`);
            };
        }
        
        // Set up intercepts
        const restoreLocalStorage = createStorageIntercept('localStorage');
        const restoreSessionStorage = createStorageIntercept('sessionStorage');
        
        // Override document.cookie with proper error handling
        try {
            Object.defineProperty(Document.prototype, 'cookie', {
                get: function() {
                    return originalDocumentCookie.get.call(this);
                },
                set: function(value) {
                    // Allow setting the consent cookie itself
                    if (value && typeof value === 'string' && value.indexOf('simple_cookie_consent_') === 0) {
                        originalDocumentCookie.set.call(this, value);
                        return value;
                    }
                    
                    // Block all other cookies
                    console.warn('Cookie blocked by consent manager:', value);
                    return '';
                },
                configurable: true
            });
        } catch (e) {
            console.error('Error overriding cookie API:', e);
        }
        
        // Add function to restore original behavior with proper error handling
        window.enableCookiesAndStorage = function(consentDetails) {
            try {
                // Restore original document.cookie
                if (originalDocumentCookie) {
                    Object.defineProperty(Document.prototype, 'cookie', originalDocumentCookie);
                }
                
                // Restore localStorage and sessionStorage methods if respective consent is given
                if (consentDetails && typeof consentDetails === 'object') {
                    if (consentDetails.preferences === true) {
                        if (restoreLocalStorage) restoreLocalStorage();
                        if (restoreSessionStorage) restoreSessionStorage();
                    }
                    
                    // Initialize Google Consent Mode v2 if enabled
                    if (window.gtag && consentDetails.googleConsentMode) {
                        updateGoogleConsent(consentDetails);
                    }
                } else {
                    // If no details provided, restore everything (full consent)
                    if (restoreLocalStorage) restoreLocalStorage();
                    if (restoreSessionStorage) restoreSessionStorage();
                }
                
                console.log('Cookie Consent: Storage APIs restored according to preferences');
            } catch (e) {
                console.error('Error restoring storage APIs:', e);
            }
        };
        
        // Helper function for updating Google Consent
        function updateGoogleConsent(details) {
            try {
                window.gtag('consent', 'update', {
                    'ad_storage': details.marketing ? 'granted' : 'denied',
                    'analytics_storage': details.analytics ? 'granted' : 'denied',
                    'functionality_storage': details.necessary ? 'granted' : 'denied',
                    'personalization_storage': details.social ? 'granted' : 'denied',
                    'security_storage': 'granted' // Always granted for security
                });
            } catch (e) {
                console.error('Error updating Google consent:', e);
            }
        }
    } else {
        // If consent exists, enable storage with saved preferences
        const consentDetails = getConsentDetails();
        if (typeof window.enableCookiesAndStorage === 'function') {
            window.enableCookiesAndStorage(consentDetails);
        }
    }
    
    // Setup a global function to check cookie consent status - but safely
    window.hasSimpleCookieConsent = function() {
        try {
            return hasConsent();
        } catch (e) {
            console.error('Error checking consent status:', e);
            return false;
        }
    };
    
    window.getSimpleCookieConsentDetails = function() {
        try {
            return getConsentDetails();
        } catch (e) {
            console.error('Error getting consent details:', e);
            return null;
        }
    };
    
    // Setup a global function to manually open the cookie modal
    window.openCookieModal = function() {
        try {
            const modal = document.querySelector('.scc-modal');
            if (modal) {
                modal.style.display = 'flex';
            }
        } catch (e) {
            console.error('Error opening cookie modal:', e);
        }
    };
})();