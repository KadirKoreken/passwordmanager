// Content script for Password Manager Extension
// This script runs on all web pages and handles password autofill

(function() {
    'use strict';

    // Prevent multiple injections
    if (window.passwordManagerInjected) {
        return;
    }
    window.passwordManagerInjected = true;

    // Configuration
    const CONFIG = {
        autoShowDelay: 1000,
        selectorTimeout: 5000,
        passwordSelectors: [
            'input[type="password"]',
            'input[name*="password"]',
            'input[id*="password"]',
            'input[placeholder*="password"]',
            'input[autocomplete="current-password"]',
            'input[autocomplete="new-password"]'
        ],
        usernameSelectors: [
            'input[type="email"]',
            'input[name*="email"]',
            'input[name*="username"]',
            'input[id*="email"]',
            'input[id*="username"]',
            'input[placeholder*="email"]',
            'input[placeholder*="username"]',
            'input[autocomplete="email"]',
            'input[autocomplete="username"]'
        ]
    };

    // State
    let availablePasswords = [];
    let isAutofillEnabled = true;
    let passwordSuggestionModal = null;

    // CSS for the password suggestion modal
    const modalCSS = `
        .pm-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999999;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .pm-modal-content {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            width: 90%;
            max-width: 400px;
            max-height: 80vh;
            overflow: hidden;
        }

        .pm-modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px 20px;
            font-weight: 600;
            font-size: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pm-close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            width: 28px;
            height: 28px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pm-close-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .pm-password-list {
            max-height: 300px;
            overflow-y: auto;
            padding: 12px;
        }

        .pm-password-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid #e5e7eb;
            margin-bottom: 8px;
        }

        .pm-password-item:hover {
            background: #f9fafb;
            border-color: #667eea;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .pm-password-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
        }

        .pm-password-details {
            flex: 1;
        }

        .pm-password-title {
            font-weight: 600;
            color: #111827;
            font-size: 14px;
            margin-bottom: 2px;
        }

        .pm-password-username {
            color: #6b7280;
            font-size: 12px;
        }

        .pm-password-actions {
            display: flex;
            gap: 4px;
        }

        .pm-copy-btn,
        .pm-fill-btn {
            width: 28px;
            height: 28px;
            border: none;
            border-radius: 6px;
            background: #f3f4f6;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            font-size: 12px;
        }

        .pm-copy-btn:hover,
        .pm-fill-btn:hover {
            background: #e5e7eb;
            transform: scale(1.05);
        }

        .pm-copy-btn {
            background: #dbeafe;
        }

        .pm-copy-btn:hover {
            background: #bfdbfe;
        }

        .pm-fill-btn {
            background: #dcfce7;
        }

        .pm-fill-btn:hover {
            background: #bbf7d0;
        }

        .pm-empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6b7280;
        }

        .pm-toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #10b981;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1000000;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 14px;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease;
        }

        .pm-toast.show {
            opacity: 1;
            transform: translateY(0);
        }
    `;

    // Inject CSS
    function injectCSS() {
        if (document.getElementById('pm-styles')) return;

        const style = document.createElement('style');
        style.id = 'pm-styles';
        style.textContent = modalCSS;
        document.head.appendChild(style);
    }

    // Find form fields
    function findPasswordFields() {
        return Array.from(document.querySelectorAll(CONFIG.passwordSelectors.join(',')))
            .filter(field => field.offsetParent !== null); // Only visible fields
    }

    function findUsernameFields() {
        return Array.from(document.querySelectorAll(CONFIG.usernameSelectors.join(',')))
            .filter(field => field.offsetParent !== null); // Only visible fields
    }

    // Get current page hostname
    function getCurrentHostname() {
        return window.location.hostname;
    }

    // Show toast notification
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = 'pm-toast';
        toast.textContent = message;

        if (type === 'error') {
            toast.style.background = '#ef4444';
        } else if (type === 'info') {
            toast.style.background = '#3b82f6';
        }

        document.body.appendChild(toast);

        // Trigger animation
        setTimeout(() => toast.classList.add('show'), 100);

        // Remove after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                if (document.body.contains(toast)) {
                    document.body.removeChild(toast);
                }
            }, 300);
        }, 3000);
    }

    // API Functions
    async function getAuthToken() {
        return new Promise((resolve) => {
            chrome.storage.local.get(['authToken'], (result) => {
                resolve(result.authToken || null);
            });
        });
    }

    async function apiRequest(endpoint, options = {}) {
        const token = await getAuthToken();
        if (!token) {
            throw new Error('Authentication required');
        }

        const url = `http://127.0.0.1:8000/api${endpoint}`;
        const headers = {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`,
            ...options.headers
        };

        const response = await fetch(url, {
            ...options,
            headers
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || `HTTP ${response.status}`);
        }

        return data;
    }

    async function decryptPassword(passwordId) {
        console.log('🔓 Content script decrypting password:', passwordId);
        try {
            const response = await apiRequest(`/passwords/${passwordId}/decrypt`, {
                method: 'POST'
            });

            if (response.success) {
                console.log('✅ Password decrypted successfully');
                return response.data.password;
            }

            throw new Error('Decrypt failed');
        } catch (error) {
            console.error('❌ Decrypt error:', error);
            throw error;
        }
    }

    async function copyPasswordToClipboard(passwordId) {
        try {
            showToast('Şifre çözülüyor...', 'info');

            const decryptedPassword = await decryptPassword(passwordId);

            if (!decryptedPassword) {
                throw new Error('Boş şifre döndü');
            }

            // Copy to clipboard
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(decryptedPassword);
                showToast('Şifre panoya kopyalandı');
            } else {
                // Fallback method
                const textArea = document.createElement('textarea');
                textArea.value = decryptedPassword;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                textArea.style.top = '-999999px';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();

                const successful = document.execCommand('copy');
                document.body.removeChild(textArea);

                if (successful) {
                    showToast('Şifre panoya kopyalandı');
                } else {
                    throw new Error('Kopyalama başarısız');
                }
            }

        } catch (error) {
            console.error('❌ Copy password error:', error);
            showToast('Şifre kopyalanamadı: ' + error.message, 'error');
        }
    }

    // Auto-fill credentials
    async function autofillCredentials(password) {
        try {
            const usernameFields = findUsernameFields();
            const passwordFields = findPasswordFields();

            // Fill username
            if (password.username && usernameFields.length > 0) {
                const usernameField = usernameFields[0];
                usernameField.value = password.username;
                usernameField.dispatchEvent(new Event('input', { bubbles: true }));
                usernameField.dispatchEvent(new Event('change', { bubbles: true }));
            }

            // Fill password
            if (passwordFields.length > 0) {
                const passwordField = passwordFields[0];

                // Show loading state
                const originalPlaceholder = passwordField.placeholder;
                passwordField.placeholder = 'Şifre çözülüyor...';

                try {
                    const decryptedPassword = await decryptPassword(password.id);
                    passwordField.value = decryptedPassword;
                    passwordField.placeholder = originalPlaceholder;
                    passwordField.dispatchEvent(new Event('input', { bubbles: true }));
                    passwordField.dispatchEvent(new Event('change', { bubbles: true }));

                    showToast('Şifre otomatik dolduruldu');
                } catch (error) {
                    passwordField.placeholder = originalPlaceholder;
                    throw error;
                }
            }

        } catch (error) {
            console.error('Auto-fill error:', error);
            showToast('Otomatik doldurma başarısız: ' + error.message, 'error');
        }
    }

    // Create password selection modal
    function createPasswordModal(passwords) {
        const modal = document.createElement('div');
        modal.className = 'pm-modal';
        modal.innerHTML = `
            <div class="pm-modal-content">
                <div class="pm-modal-header">
                    <span>Kayıtlı Şifreler</span>
                    <button class="pm-close-btn">&times;</button>
                </div>
                <div class="pm-password-list">
                    ${passwords.length > 0 ?
                        passwords.map(password => `
                            <div class="pm-password-item" data-id="${password.id}">
                                <div class="pm-password-icon">${password.title.charAt(0).toUpperCase()}</div>
                                <div class="pm-password-details">
                                    <div class="pm-password-title">${password.title}</div>
                                    <div class="pm-password-username">${password.username || 'Kullanıcı adı yok'}</div>
                                </div>
                                <div class="pm-password-actions">
                                    <button class="pm-copy-btn" data-id="${password.id}" title="Şifreyi Kopyala">📋</button>
                                    <button class="pm-fill-btn" data-id="${password.id}" title="Otomatik Doldur">🔄</button>
                                </div>
                            </div>
                        `).join('') :
                        '<div class="pm-empty-state">Bu site için kayıtlı şifre bulunamadı</div>'
                    }
                </div>
            </div>
        `;

        // Event listeners
        modal.querySelector('.pm-close-btn').addEventListener('click', () => {
            document.body.removeChild(modal);
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                document.body.removeChild(modal);
            }
        });

        // Button click handlers
        modal.querySelectorAll('.pm-copy-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.stopPropagation();
                const passwordId = btn.dataset.id;
                await copyPasswordToClipboard(passwordId);
            });
        });

        modal.querySelectorAll('.pm-fill-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.stopPropagation();
                const passwordId = btn.dataset.id;
                const selectedPassword = passwords.find(p => p.id == passwordId);

                if (selectedPassword) {
                    await autofillCredentials(selectedPassword);
                    document.body.removeChild(modal);
                }
            });
        });

        // Password item click handlers (for backward compatibility)
        modal.querySelectorAll('.pm-password-item').forEach(item => {
            item.addEventListener('click', async (e) => {
                // Only trigger if click didn't come from buttons
                if (e.target.classList.contains('pm-copy-btn') || e.target.classList.contains('pm-fill-btn')) {
                    return;
                }

                const passwordId = item.dataset.id;
                const selectedPassword = passwords.find(p => p.id == passwordId);

                if (selectedPassword) {
                    await autofillCredentials(selectedPassword);
                    document.body.removeChild(modal);
                }
            });
        });

        return modal;
    }

    // Show password selection modal
    function showPasswordSelection() {
        if (availablePasswords.length === 0) {
            showToast('Bu site için kayıtlı şifre bulunamadı', 'error');
            return;
        }

        // Remove existing modal if any
        if (passwordSuggestionModal) {
            document.body.removeChild(passwordSuggestionModal);
        }

        passwordSuggestionModal = createPasswordModal(availablePasswords);
        document.body.appendChild(passwordSuggestionModal);
    }

    // Auto-detect login forms and show suggestions
    function autoDetectAndSuggest() {
        const passwordFields = findPasswordFields();

        if (passwordFields.length > 0 && availablePasswords.length > 0) {
            // Add visual indicator to password fields
            passwordFields.forEach(field => {
                if (field.dataset.pmIndicator) return;

                field.dataset.pmIndicator = 'true';
                field.style.boxShadow = '0 0 0 2px rgba(102, 126, 234, 0.3)';
                field.addEventListener('focus', showPasswordSelection);
            });
        }
    }

    // Message listener from background script
    chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
        switch (message.action) {
            case 'showPasswordSelection':
                showPasswordSelection();
                break;

            case 'passwordsAvailable':
                availablePasswords = message.passwords || [];
                setTimeout(autoDetectAndSuggest, CONFIG.autoShowDelay);
                break;

            case 'toggleAutofill':
                isAutofillEnabled = message.enabled;
                break;

            case 'copyPassword':
                copyPasswordToClipboard(message.passwordId);
                break;

            default:
                console.log('Unknown content script message:', message.action);
        }
    });

    // Initialize when DOM is ready
    function initialize() {
        injectCSS();

        // Set up keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Ctrl+Shift+P to show password selection
            if (e.ctrlKey && e.shiftKey && e.key === 'P') {
                e.preventDefault();
                showPasswordSelection();
            }
        });

        // Watch for dynamically added forms
        const observer = new MutationObserver((mutations) => {
            let hasNewForms = false;
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        const hasPasswordFields = node.querySelectorAll(CONFIG.passwordSelectors.join(',')).length > 0;
                        if (hasPasswordFields) {
                            hasNewForms = true;
                        }
                    }
                });
            });

            if (hasNewForms) {
                setTimeout(autoDetectAndSuggest, 500);
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        setTimeout(autoDetectAndSuggest, CONFIG.autoShowDelay);
    }

    // Start initialization when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }

})();
