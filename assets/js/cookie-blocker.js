/**
 * Enhanced Cookie Blocker Script - Runs before any cookies are set
 * Blocks cookies, localStorage, sessionStorage, and third-party scripts until consent is given
 * Vanilla JavaScript version (no jQuery dependency)
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
            const cookies = document.cookie.split('; ');
            const consentCookie = cookies.find(row => row.startsWith('simple_cookie_consent_details='));
            
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
        console.log('Cookie Consent: Blocking cookies, storage, and third-party scripts until consent is given');
        
        // Store original methods
        let originalDocumentCookie;
        
        try {
            originalDocumentCookie = Object.getOwnPropertyDescriptor(Document.prototype, 'cookie');
        } catch (e) {
            console.error('Error capturing original cookie API:', e);
        }
        
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
        
        // Block third-party scripts including Google and YouTube
        blockThirdPartyScripts();
        
        // Add meta headers to prevent third-party cookies and trackers
        function addMetaHeaders() {
            let meta = document.createElement('meta');
            meta.httpEquiv = 'Content-Security-Policy';
            meta.content = "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; connect-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline';";
            document.head.appendChild(meta);
        }
        
        // Function to monitor and block script elements
        function blockThirdPartyScripts() {
            // Block existing scripts
            const scripts = document.getElementsByTagName('script');
            for (let i = 0; i < scripts.length; i++) {
                const src = scripts[i].src.toLowerCase();
                if (shouldBlockScript(src)) {
                    console.warn('Blocked existing third-party script:', src);
                    scripts[i].type = 'text/plain';
                    scripts[i].dataset.src = scripts[i].src;
                    scripts[i].src = '';
                }
            }
            
            // Monitor DOM for new script elements
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeName === 'SCRIPT') {
                            const src = node.src.toLowerCase();
                            if (shouldBlockScript(src)) {
                                console.warn('Blocked added third-party script:', src);
                                node.type = 'text/plain';
                                node.dataset.src = node.src;
                                node.src = '';
                            }
                        }
                        
                        // Also check for iframe elements containing YouTube, Google Maps, etc.
                        if (node.nodeName === 'IFRAME') {
                            const src = node.src.toLowerCase();
                            if (shouldBlockIframe(src)) {
                                console.warn('Blocked third-party iframe:', src);
                                node.dataset.src = node.src;
                                node.src = 'about:blank';
                            }
                        }
                    });
                });
            });
            
            observer.observe(document.documentElement, {
                childList: true,
                subtree: true
            });
            
            // Override document.createElement to catch script and iframe creation
            const originalCreateElement = document.createElement;
            document.createElement = function(tagName) {
                const element = originalCreateElement.call(document, tagName);
                
                if (tagName.toLowerCase() === 'script') {
                    // Override the src property
                    const originalDescriptor = Object.getOwnPropertyDescriptor(HTMLScriptElement.prototype, 'src');
                    Object.defineProperty(element, 'src', {
                        set: function(value) {
                            if (shouldBlockScript(value)) {
                                console.warn('Blocked dynamic script src:', value);
                                this.type = 'text/plain';
                                this.dataset.src = value;
                                return '';
                            }
                            return originalDescriptor.set.call(this, value);
                        },
                        get: originalDescriptor.get
                    });
                }
                
                if (tagName.toLowerCase() === 'iframe') {
                    // Override the src property
                    const originalDescriptor = Object.getOwnPropertyDescriptor(HTMLIFrameElement.prototype, 'src');
                    Object.defineProperty(element, 'src', {
                        set: function(value) {
                            if (shouldBlockIframe(value)) {
                                console.warn('Blocked dynamic iframe src:', value);
                                this.dataset.src = value;
                                return 'about:blank';
                            }
                            return originalDescriptor.set.call(this, value);
                        },
                        get: originalDescriptor.get
                    });
                }
                
                return element;
            };
        }
        
        // Helper function to determine if a script should be blocked
        function shouldBlockScript(src) {
            if (!src) return false;
            
            const blockedDomains = [
                'google-analytics.com',
                'googletagmanager.com',
                'googlesyndication.com',
                'doubleclick.net',
                'facebook.net',
                'facebook.com/tr',
                'twitter.com/widgets',
                'connect.facebook.net',
                'platform.twitter.com',
                'assets.pinterest.com',
                'static.ads-twitter.com',
                'snap.licdn.com',
                'analytics.tiktok.com',
                'youtube.com/iframe_api',
                'googleadservices.com',
                'google.com/recaptcha',
                'www.gstatic.com/recaptcha',
                'js.hs-scripts.com',
                'adservice.google.com',
                'cdn.ampproject.org'
            ];
            
            return blockedDomains.some(domain => src.indexOf(domain) !== -1);
        }
        
        // Helper function to determine if an iframe should be blocked
        function shouldBlockIframe(src) {
            if (!src) return false;
            
            const blockedDomains = [
                'youtube.com/embed',
                'player.vimeo.com',
                'maps.google.com',
                'www.google.com/maps',
                'platform.twitter.com',
                'www.facebook.com/plugins',
                'instagram.com/embed',
                'tiktok.com/embed',
                'open.spotify.com/embed',
                'soundcloud.com/player'
            ];
            
            return blockedDomains.some(domain => src.indexOf(domain) !== -1);
        }
        
        // Function to restore blocked content based on consent
        function reEnableBlockedContent(consentDetails) {
            try {
                // Process scripts
                const scripts = document.querySelectorAll('script[type="text/plain"][data-src]');
                scripts.forEach(script => {
                    const src = script.dataset.src.toLowerCase();
                    
                    // Determine which category this script belongs to
                    let shouldEnable = false;
                    
                    if (src.includes('google-analytics.com') || src.includes('googletagmanager.com')) {
                        shouldEnable = consentDetails.analytics === true;
                    } else if (src.includes('doubleclick.net') || src.includes('googlesyndication.com') || 
                              src.includes('googleadservices.com')) {
                        shouldEnable = consentDetails.marketing === true;
                    } else if (src.includes('facebook.') || src.includes('twitter.') || 
                              src.includes('instagram.') || src.includes('tiktok.')) {
                        shouldEnable = consentDetails.social === true;
                    } else {
                        // For any other third-party scripts, require marketing consent
                        shouldEnable = consentDetails.marketing === true;
                    }
                    
                    if (shouldEnable) {
                        // Create a new script element instead of changing type to ensure it runs
                        const newScript = document.createElement('script');
                        newScript.src = script.dataset.src;
                        
                        // Copy other attributes except type and data-src
                        Array.from(script.attributes).forEach(attr => {
                            if (attr.name !== 'type' && attr.name !== 'data-src' && attr.name !== 'src') {
                                newScript.setAttribute(attr.name, attr.value);
                            }
                        });
                        
                        // Replace the old script with the new one
                        script.parentNode.replaceChild(newScript, script);
                        console.log('Re-enabled script:', script.dataset.src);
                    }
                });
                
                // Process iframes
                const iframes = document.querySelectorAll('iframe[data-src]');
                iframes.forEach(iframe => {
                    const src = iframe.dataset.src.toLowerCase();
                    
                    // Determine which category this iframe belongs to
                    let shouldEnable = false;
                    
                    if (src.includes('youtube.') || src.includes('vimeo.')) {
                        shouldEnable = consentDetails.social === true;
                    } else if (src.includes('maps.google.') || src.includes('google.com/maps')) {
                        shouldEnable = consentDetails.preferences === true;
                    } else if (src.includes('facebook.') || src.includes('twitter.') || 
                              src.includes('instagram.') || src.includes('tiktok.')) {
                        shouldEnable = consentDetails.social === true;
                    } else {
                        // For any other third-party iframes, require marketing consent
                        shouldEnable = consentDetails.marketing === true;
                    }
                    
                    if (shouldEnable) {
                        iframe.src = iframe.dataset.src;
                        console.log('Re-enabled iframe:', iframe.dataset.src);
                    }
                });
                
                // Process placeholders
                const placeholders = document.querySelectorAll('.scc-embed-placeholder');
                placeholders.forEach(placeholder => {
                    const consentType = placeholder.dataset.consentType;
                    const originalEmbed = placeholder.dataset.originalEmbed;
                    
                    if (consentDetails[consentType] === true && originalEmbed) {
                        try {
                            // Create a temporary container for the HTML
                            const container = document.createElement('div');
                            container.innerHTML = atob(originalEmbed);
                            
                            // Replace the placeholder with the container's children
                            while (container.firstChild) {
                                placeholder.parentNode.insertBefore(container.firstChild, placeholder);
                            }
                            placeholder.parentNode.removeChild(placeholder);
                            console.log('Restored placeholder with original content');
                        } catch (e) {
                            console.error('Error replacing placeholder:', e);
                        }
                    }
                });
            } catch (e) {
                console.error('Error re-enabling blocked content:', e);
            }
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
                    
                    // Re-enable blocked scripts and iframes based on consent
                    reEnableBlockedContent(consentDetails);
                } else {
                    // If no details provided, restore everything (full consent)
                    if (restoreLocalStorage) restoreLocalStorage();
                    if (restoreSessionStorage) restoreSessionStorage();
                    
                    // Re-enable all blocked content
                    reEnableBlockedContent({
                        necessary: true,
                        preferences: true,
                        analytics: true,
                        marketing: true,
                        social: true
                    });
                }
                
                console.log('Cookie Consent: Storage APIs and third-party content restored according to preferences');
            } catch (e) {
                console.error('Error restoring storage APIs and content:', e);
            }
        };
        
        // Expose this function for the cookie-consent.js to use directly
        window.enableBlockedContent = reEnableBlockedContent;
        
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