/**
 * Enhanced Cookie Consent Handler - Vanilla JavaScript version (no jQuery)
 */
(function() {
    'use strict';

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        // Check if consent was already given
        if (!hasCookieConsent()) {
            // Show the banner
            const banner = document.querySelector('.scc-banner');
            if (banner) {
                banner.style.display = 'block';
                fadeIn(banner, 300);
            }
        } else {
            // Apply existing consent to any embed placeholders
            applyExistingConsent();
        }
        
        // Setup event handlers
        setupEventHandlers();
        
        // Initialize Google Consent Mode if enabled
        initGoogleConsentMode();
    });
    
    /**
     * Fade in helper function
     */
    function fadeIn(element, duration) {
        element.style.opacity = 0;
        element.style.display = 'block';
        
        let start = null;
        function step(timestamp) {
            if (!start) start = timestamp;
            const progress = timestamp - start;
            
            element.style.opacity = Math.min(progress / duration, 1);
            if (progress < duration) {
                window.requestAnimationFrame(step);
            }
        }
        window.requestAnimationFrame(step);
    }
    
    /**
     * Fade out helper function
     */
    function fadeOut(element, duration, callback) {
        element.style.opacity = 1;
        
        let start = null;
        function step(timestamp) {
            if (!start) start = timestamp;
            const progress = timestamp - start;
            
            element.style.opacity = 1 - Math.min(progress / duration, 1);
            if (progress < duration) {
                window.requestAnimationFrame(step);
            } else {
                element.style.display = 'none';
                if (typeof callback === 'function') callback();
            }
        }
        window.requestAnimationFrame(step);
    }
    
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
            const cookies = document.cookie.split('; ');
            const consentCookie = cookies.find(row => row.startsWith('simple_cookie_consent_details='));
            
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
        document.querySelectorAll('.scc-accept-all').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                acceptAllCookies();
            });
        });
        
        // Accept only essential cookies
        document.querySelectorAll('.scc-accept-essential').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                acceptEssentialCookies();
            });
        });
        
        // Open customization modal
        document.querySelectorAll('.scc-customize').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                openCustomizeModal();
            });
        });
        
        // Save preferences from modal
        document.querySelectorAll('.scc-save-preferences').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                savePreferences();
            });
        });
        
        // Tab navigation
        document.querySelectorAll('.scc-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const tabId = this.dataset.tab;
                if (!tabId) return;
                
                // Update active tab
                document.querySelectorAll('.scc-tab').forEach(t => {
                    t.classList.remove('scc-active');
                });
                this.classList.add('scc-active');
                
                // Update tab content
                document.querySelectorAll('.scc-tab-content').forEach(content => {
                    content.classList.remove('scc-active');
                });
                
                const activeContent = document.getElementById(tabId);
                if (activeContent) {
                    activeContent.classList.add('scc-active');
                }
            });
        });
        
        // Close modal when clicking outside
        document.querySelectorAll('.scc-modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeCustomizeModal();
                }
            });
        });
        
        // Global function to open modal (for use with shortcode)
        window.openCookieModal = function() {
            openCustomizeModal();
        };
    }
    
    /**
     * Apply existing consent to embed placeholders
     */
    function applyExistingConsent() {
        const details = getConsentDetails();
        if (!details) return;
        
        // Find all placeholder elements
        document.querySelectorAll('.scc-embed-placeholder').forEach(placeholder => {
            const consentType = placeholder.dataset.consentType;
            
            // Check if this consent type is granted
            if (details[consentType] === true) {
                // Get original embed if available
                const originalEmbed = placeholder.dataset.originalEmbed;
                if (originalEmbed) {
                    // Decode and replace placeholder with original embed
                    try {
                        const decodedEmbed = atob(originalEmbed);
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = decodedEmbed;
                        
                        // Replace placeholder with content
                        const parent = placeholder.parentNode;
                        while (tempDiv.firstChild) {
                            parent.insertBefore(tempDiv.firstChild, placeholder);
                        }
                        parent.removeChild(placeholder);
                    } catch (e) {
                        console.error('Error decoding original embed:', e);
                    }
                }
            }
        });
    }
    
    /**
     * Initialize Google Consent Mode v2 if enabled
     */
    function initGoogleConsentMode() {
        if (!window.simpleCookieConsent || !window.simpleCookieConsent.googleConsentMode) {
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
                if (window.simpleCookieConsent.googleConsentRegion) {
                    gtag('consent', 'default', {
                        'region': [window.simpleCookieConsent.googleConsentRegion]
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
            
            // Load Google Tag if ID is provided and consent is given
            const details = getConsentDetails();
            if (window.simpleCookieConsent.googleTagId && details && details.analytics === true) {
                loadGoogleTag(window.simpleCookieConsent.googleTagId);
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
        if (window.simpleCookieConsent && window.simpleCookieConsent.consentTypes) {
            Object.values(window.simpleCookieConsent.consentTypes).forEach(type => {
                consentDetails[type.id] = true;
            });
        } else {
            // Fallback if consentTypes not available
            consentDetails.necessary = true;
            consentDetails.preferences = true;
            consentDetails.analytics = true;
            consentDetails.marketing = true;
            consentDetails.social = true;
        }
        
        consentDetails.googleConsentMode = window.simpleCookieConsent && window.simpleCookieConsent.googleConsentMode;
        
        // Set cookies
        setCookieConsent(true, consentDetails);
        
        // Hide banner and modal
        const banner = document.querySelector('.scc-banner');
        if (banner) {
            fadeOut(banner, 300);
        }
        closeCustomizeModal();
        
        // Update Google Consent Mode if enabled
        updateGoogleConsentMode(consentDetails);
        
        // Replace placeholders with actual content
        replaceEmbedPlaceholders(consentDetails);
        
        // Re-enable any blocked scripts or iframes
        enableBlockedContent(consentDetails);
        
        // AJAX call to notify server and store in database
        sendConsentToServer(true, consentDetails);
    }
    
    /**
     * Accept only essential cookies
     */
    function acceptEssentialCookies() {
        const consentDetails = {};
        
        // Mark only essential cookies as accepted
        if (window.simpleCookieConsent && window.simpleCookieConsent.consentTypes) {
            Object.values(window.simpleCookieConsent.consentTypes).forEach(type => {
                consentDetails[type.id] = type.required ? true : false;
            });
        } else {
            // Fallback if consentTypes not available
            consentDetails.necessary = true;
            consentDetails.preferences = false;
            consentDetails.analytics = false;
            consentDetails.marketing = false;
            consentDetails.social = false;
        }
        
        consentDetails.googleConsentMode = window.simpleCookieConsent && window.simpleCookieConsent.googleConsentMode;
        
        // Set cookies
        setCookieConsent(true, consentDetails);
        
        // Hide banner and modal
        const banner = document.querySelector('.scc-banner');
        if (banner) {
            fadeOut(banner, 300);
        }
        closeCustomizeModal();
        
        // Update Google Consent Mode if enabled
        updateGoogleConsentMode(consentDetails);
        
        // Replace only placeholders that have consent
        replaceEmbedPlaceholders(consentDetails);
        
        // Re-enable any essential content
        enableBlockedContent(consentDetails);
        
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
        if (window.simpleCookieConsent && window.simpleCookieConsent.consentTypes) {
            Object.values(window.simpleCookieConsent.consentTypes).forEach(type => {
                const checkbox = document.getElementById('scc-consent-' + type.id);
                if (!checkbox) return;
                
                if (type.required) {
                    checkbox.checked = true;
                    checkbox.disabled = true;
                } else if (details && typeof details[type.id] !== 'undefined') {
                    // Set checkbox state from saved preferences
                    checkbox.checked = !!details[type.id];
                } else {
                    // Default to unchecked for non-required types with no saved preference
                    checkbox.checked = false;
                }
            });
        }
        
        // Show modal
        const modal = document.querySelector('.scc-modal');
        if (modal) {
            modal.style.display = 'flex';
        }
    }
    
    /**
     * Close the customization modal
     */
    function closeCustomizeModal() {
        const modal = document.querySelector('.scc-modal');
        if (modal) {
            fadeOut(modal, 300);
        }
    }
    
    /**
     * Save user preferences from modal
     */
    function savePreferences() {
        const consentDetails = {};
        
        // Get all toggle states
        if (window.simpleCookieConsent && window.simpleCookieConsent.consentTypes) {
            Object.values(window.simpleCookieConsent.consentTypes).forEach(type => {
                const checkbox = document.getElementById('scc-consent-' + type.id);
                // Explicitly set as true or false
                consentDetails[type.id] = checkbox ? checkbox.checked : false;
            });
        }
        
        consentDetails.googleConsentMode = window.simpleCookieConsent && window.simpleCookieConsent.googleConsentMode;
        
        // Log for debugging
        if (window.simpleCookieConsent && window.simpleCookieConsent.debugMode) {
            console.log('Saving preferences:', consentDetails);
        }
        
        // Set cookies
        setCookieConsent(true, consentDetails);
        
        // Hide banner and modal
        const banner = document.querySelector('.scc-banner');
        if (banner) {
            fadeOut(banner, 300);
        }
        closeCustomizeModal();
        
        // Update Google Consent Mode if enabled
        updateGoogleConsentMode(consentDetails);
        
        // Replace placeholders with actual content based on preferences
        replaceEmbedPlaceholders(consentDetails);
        
        // Re-enable any blocked scripts or iframes based on preferences
        enableBlockedContent(consentDetails);
        
        // AJAX call to notify server and store in database
        sendConsentToServer(true, consentDetails);
    }
    
    /**
     * Replace embed placeholders based on consent details
     */
    function replaceEmbedPlaceholders(consentDetails) {
        document.querySelectorAll('.scc-embed-placeholder').forEach(placeholder => {
            const consentType = placeholder.dataset.consentType;
            
            // Check if this consent type is granted
            if (consentDetails[consentType] === true) {
                // Get original embed if available
                const originalEmbed = placeholder.dataset.originalEmbed;
                if (originalEmbed) {
                    // Decode and replace placeholder with original embed
                    try {
                        const decodedEmbed = atob(originalEmbed);
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = decodedEmbed;
                        
                        // Replace placeholder with content
                        const parent = placeholder.parentNode;
                        while (tempDiv.firstChild) {
                            parent.insertBefore(tempDiv.firstChild, placeholder);
                        }
                        parent.removeChild(placeholder);
                    } catch (e) {
                        console.error('Error decoding original embed:', e);
                    }
                }
            }
        });
    }
    
    /**
     * Re-enable any blocked content based on preferences
     */
    function enableBlockedContent(consentDetails) {
        // If window.enableCookiesAndStorage is available (from cookie-blocker.js)
        if (typeof window.enableCookiesAndStorage === 'function') {
            window.enableCookiesAndStorage(consentDetails);
        }
        
        // If analytics is accepted, load Google Tag
        if (consentDetails.analytics === true && 
            window.simpleCookieConsent && window.simpleCookieConsent.googleTagId) {
            loadGoogleTag(window.simpleCookieConsent.googleTagId);
        }
        
        // Find and activate any blocked YouTube or other iframes
        document.querySelectorAll('iframe[data-src]').forEach(iframe => {
            const src = iframe.dataset.src.toLowerCase();
            let consentType = null;
            
            // Determine which consent type this iframe needs
            if (src.includes('youtube.com') || src.includes('youtu.be') || 
                src.includes('vimeo.com') || src.includes('facebook.com')) {
                consentType = 'social';
            } else if (src.includes('google.com/maps')) {
                consentType = 'preferences';
            } else if (src.includes('google-analytics.com') || src.includes('googletagmanager.com')) {
                consentType = 'analytics';
            } else if (src.includes('doubleclick.net') || src.includes('googlesyndication.com')) {
                consentType = 'marketing';
            }
            
            // If consent type is identified and granted, restore the iframe
            if (consentType && consentDetails[consentType] === true) {
                iframe.src = iframe.dataset.src;
                iframe.removeAttribute('data-src');
            }
        });
        
        // Find and activate any blocked scripts
        document.querySelectorAll('script[type="text/plain"][data-src]').forEach(script => {
            const src = script.dataset.src.toLowerCase();
            let consentType = null;
            
            // Determine which consent type this script needs
            if (src.includes('google-analytics.com') || src.includes('googletagmanager.com')) {
                consentType = 'analytics';
            } else if (src.includes('doubleclick.net') || src.includes('googlesyndication.com')) {
                consentType = 'marketing';
            } else if (src.includes('facebook.') || src.includes('twitter.') || 
                      src.includes('youtube.') || src.includes('linkedin.')) {
                consentType = 'social';
            }
            
            // If consent type is identified and granted, load the script
            if (consentType && consentDetails[consentType] === true) {
                const newScript = document.createElement('script');
                newScript.src = src;
                
                // Copy attributes except type and data-src
                Array.from(script.attributes).forEach(attr => {
                    if (attr.name !== 'type' && attr.name !== 'data-src') {
                        newScript.setAttribute(attr.name, attr.value);
                    }
                });
                
                // Replace the old script with the new one
                script.parentNode.replaceChild(newScript, script);
            }
        });
    }
    
    /**
     * Set cookie consent status and details
     */
    function setCookieConsent(accepted, details = {}) {
        const expiryDays = (window.simpleCookieConsent && window.simpleCookieConsent.cookieExpiry) 
            ? parseInt(window.simpleCookieConsent.cookieExpiry) : 180;
        const date = new Date();
        date.setTime(date.getTime() + (expiryDays * 24 * 60 * 60 * 1000));
        const expires = 'expires=' + date.toUTCString();
        
        // Set the main acceptance cookie
        document.cookie = 'simple_cookie_consent_accepted=' + (accepted ? '1' : '0') + 
            '; ' + expires + '; path=/; SameSite=Lax' + 
            (location.protocol === 'https:' ? '; Secure' : '');
        
        // Store details if provided
        if (Object.keys(details).length > 0) {
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
        if (!(window.simpleCookieConsent && window.simpleCookieConsent.googleConsentMode) || 
            typeof window.gtag !== 'function') {
            return;
        }
        
        try {
            window.gtag('consent', 'update', {
                'ad_storage': consentDetails.marketing === true ? 'granted' : 'denied',
                'analytics_storage': consentDetails.analytics === true ? 'granted' : 'denied',
                'functionality_storage': consentDetails.necessary === true ? 'granted' : 'denied',
                'personalization_storage': consentDetails.social === true ? 'granted' : 'denied',
                'security_storage': 'granted' // Always granted for security
            });
        } catch (e) {
            console.error('Error updating Google consent:', e);
        }
    }
    /**
     * Send consent data to server via AJAX
     */
    function sendConsentToServer(accepted, details) {
        // Check if AJAX settings are available
        if (!window.simpleCookieConsent || !window.simpleCookieConsent.ajaxUrl) {
            console.warn('AJAX URL not available, skipping server-side consent storage');
            return;
        }
        
        // Get WordPress admin-ajax URL
        const ajaxUrl = window.simpleCookieConsent.ajaxUrl;
        
        // Clone the details to avoid modifying the original object
        const safeDetails = {};
        
        // Sanitize details - convert to explicit booleans
        Object.keys(details || {}).forEach(key => {
            // Force explicit boolean values (true or false)
            safeDetails[key] = details[key] === true;
        });
        
        // Debug log
        if (window.simpleCookieConsent.debugMode) {
            console.log('Sending to server:', safeDetails);
        }
        
        // Create form data
        const formData = new FormData();
        formData.append('action', 'simple_cookie_set_consent');
        formData.append('nonce', window.simpleCookieConsent.nonce || '');
        formData.append('accepted', accepted ? 1 : 0);
        
        // THIS IS THE FIX:
        // Instead of putting the entire object, we need to stringify it first
        // and then create individual form data entries for each consent type
        for (const [key, value] of Object.entries(safeDetails)) {
            formData.append(`details[${key}]`, value ? '1' : '0');
        }
        
        // Use fetch API for AJAX request
        fetch(ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data && data.success) {
                console.log('Consent saved to server');
            } else {
                console.warn('Server response indicates error:', data);
            }
        })
        .catch(error => {
            console.error('AJAX error saving consent:', error);
            console.warn('Unable to save consent to server, continuing with client-side consent only');
        });
    }
})();