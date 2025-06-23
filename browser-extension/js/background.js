// Background service worker for Password Manager Extension

// Constants
const API_BASE_URL = 'http://127.0.0.1:8000/api';

// Install event
chrome.runtime.onInstalled.addListener((details) => {
    console.log('Password Manager Extension installed');

    // Create context menu for password autofill
    chrome.contextMenus.create({
        id: 'password-manager-autofill',
        title: 'Şifre Yöneticisi ile Doldur',
        contexts: ['editable']
    });

    // Set default badge
    chrome.action.setBadgeText({ text: '?' });
    chrome.action.setBadgeBackgroundColor({ color: '#667eea' });
});

// Context menu click handler
chrome.contextMenus.onClicked.addListener((info, tab) => {
    if (info.menuItemId === 'password-manager-autofill') {
        // Send message to content script to show password selection
        chrome.tabs.sendMessage(tab.id, {
            action: 'showPasswordSelection',
            url: tab.url
        });
    }
});

// Message handler for communication between popup and content scripts
chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
    switch (message.action) {
        case 'getCurrentTab':
            handleGetCurrentTab(sendResponse);
            return true; // Keep message channel open for async response

        case 'updateBadge':
            updateBadge(message.count);
            break;

        case 'checkAPIHealth':
            checkAPIHealth().then(sendResponse);
            return true;

        case 'getPasswordsForSite':
            getPasswordsForSite(message.url).then(sendResponse);
            return true;

        default:
            console.log('Unknown message action:', message.action);
    }
});

// Tab update listener to check for password-enabled sites
chrome.tabs.onUpdated.addListener((tabId, changeInfo, tab) => {
    if (changeInfo.status === 'complete' && tab.url) {
        // Check if current site has saved passwords
        checkSitePasswords(tab.url, tabId);
    }
});

// Helper Functions
async function handleGetCurrentTab(sendResponse) {
    try {
        const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
        sendResponse({
            success: true,
            url: tab?.url || '',
            hostname: tab?.url ? new URL(tab.url).hostname : ''
        });
    } catch (error) {
        sendResponse({ success: false, error: error.message });
    }
}

async function checkAPIHealth() {
    try {
        const response = await fetch(`${API_BASE_URL}/health`);
        const isHealthy = response.ok;

        // Update extension badge based on API health
        chrome.action.setBadgeText({ text: isHealthy ? '✓' : '✗' });
        chrome.action.setBadgeBackgroundColor({
            color: isHealthy ? '#10b981' : '#ef4444'
        });

        return { success: true, healthy: isHealthy };
    } catch (error) {
        chrome.action.setBadgeText({ text: '✗' });
        chrome.action.setBadgeBackgroundColor({ color: '#ef4444' });
        return { success: false, error: error.message };
    }
}

async function getPasswordsForSite(url) {
    try {
        // Get auth token from storage
        const { authToken } = await chrome.storage.local.get(['authToken']);

        if (!authToken) {
            return { success: false, error: 'Not authenticated' };
        }

        const hostname = new URL(url).hostname;

        // Fetch passwords from API
        const response = await fetch(`${API_BASE_URL}/passwords?search=${encodeURIComponent(hostname)}`, {
            headers: {
                'Authorization': `Bearer ${authToken}`,
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            const passwords = data.data.data || [];
            const sitePasswords = passwords.filter(password => {
                if (!password.url) return false;
                try {
                    const passwordHost = new URL(password.url).hostname;
                    return passwordHost.includes(hostname) || hostname.includes(passwordHost);
                } catch {
                    return password.url.includes(hostname);
                }
            });

            return { success: true, passwords: sitePasswords };
        } else {
            return { success: false, error: data.message };
        }

    } catch (error) {
        return { success: false, error: error.message };
    }
}

async function checkSitePasswords(url, tabId) {
    try {
        const result = await getPasswordsForSite(url);

        if (result.success && result.passwords) {
            const count = result.passwords.length;

            // Update badge with password count for this site
            chrome.action.setBadgeText({
                text: count > 0 ? count.toString() : '',
                tabId: tabId
            });

            if (count > 0) {
                chrome.action.setBadgeBackgroundColor({
                    color: '#667eea',
                    tabId: tabId
                });

                // Notify content script about available passwords
                chrome.tabs.sendMessage(tabId, {
                    action: 'passwordsAvailable',
                    count: count,
                    passwords: result.passwords
                });
            }
        }
    } catch (error) {
        console.error('Error checking site passwords:', error);
    }
}

function updateBadge(count) {
    const text = count > 0 ? count.toString() : '';
    chrome.action.setBadgeText({ text });
    chrome.action.setBadgeBackgroundColor({
        color: count > 0 ? '#667eea' : '#9ca3af'
    });
}

// Alarm listener for periodic API health checks
chrome.alarms.onAlarm.addListener((alarm) => {
    if (alarm.name === 'apiHealthCheck') {
        checkAPIHealth();
    }
});

// Set up periodic health checks (every 5 minutes)
chrome.alarms.create('apiHealthCheck', {
    delayInMinutes: 1,
    periodInMinutes: 5
});

// Storage change listener to handle auth state changes
chrome.storage.onChanged.addListener((changes, namespace) => {
    if (namespace === 'local') {
        if (changes.authToken) {
            // Auth state changed, update badge accordingly
            if (changes.authToken.newValue) {
                chrome.action.setBadgeText({ text: '✓' });
                chrome.action.setBadgeBackgroundColor({ color: '#10b981' });
            } else {
                chrome.action.setBadgeText({ text: '?' });
                chrome.action.setBadgeBackgroundColor({ color: '#9ca3af' });
            }
        }
    }
});

// Handle extension startup
chrome.runtime.onStartup.addListener(() => {
    console.log('Password Manager Extension started');
    checkAPIHealth();
});

// Export for testing (if needed)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        checkAPIHealth,
        getPasswordsForSite,
        updateBadge
    };
}
