// API Configuration
const API_BASE_URL = 'http://127.0.0.1:8000/api';

// DOM Elements
const loadingDiv = document.getElementById('loading');
const loginSection = document.getElementById('login-section');
const mainContent = document.getElementById('main-content');
const statusDiv = document.getElementById('status');
const statusText = document.querySelector('.status-text');

// User Info Elements
const userNameSpan = document.getElementById('user-name');
const userEmailSpan = document.getElementById('user-email');

// Password Lists
const recentPasswordsList = document.getElementById('recent-passwords');
const currentSitePasswordsList = document.getElementById('current-site-passwords');
const allPasswordsList = document.getElementById('all-passwords');

// Forms and Inputs
const loginForm = document.getElementById('login-form');
const emailInput = document.getElementById('email');
const passwordInput = document.getElementById('password');
const loginError = document.getElementById('login-error');
const searchInput = document.getElementById('search-input');

// Buttons
const logoutBtn = document.getElementById('logout-btn');
const addPasswordBtn = document.getElementById('add-password-btn');

// Modal
const addPasswordModal = document.getElementById('add-password-modal');
const addPasswordForm = document.getElementById('add-password-form');
const closeModalBtn = document.getElementById('close-modal');
const cancelAddBtn = document.getElementById('cancel-add');
const generatePasswordBtn = document.getElementById('generate-password');

// Tabs
const tabBtns = document.querySelectorAll('.tab-btn');
const tabPanes = document.querySelectorAll('.tab-pane');
const currentSiteTab = document.getElementById('current-site-tab-btn');

// Global State
let authToken = null;
let currentUser = null;
let allPasswords = [];
let currentSiteUrl = '';

// Utility Functions
const showError = (message, element = loginError) => {
    element.textContent = message;
    element.style.display = 'block';
    setTimeout(() => {
        element.style.display = 'none';
    }, 5000);
};

const showSuccess = (message) => {
    // Create and show success toast
    const toast = document.createElement('div');
    toast.className = 'success-toast';
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #10b981;
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 10000;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        font-size: 14px;
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.3s ease;
    `;
    toast.textContent = message;

    document.body.appendChild(toast);

    // Trigger animation
    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
    }, 100);

    // Remove after 3 seconds
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(20px)';
        setTimeout(() => {
            if (document.body.contains(toast)) {
                document.body.removeChild(toast);
            }
        }, 300);
    }, 3000);
};

const updateStatus = (status, text) => {
    statusDiv.className = `status ${status}`;
    statusText.textContent = text;
};

const getCurrentTab = async () => {
    try {
        console.log('🌐 Getting current tab...');
        const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
        console.log('🌐 Current tab:', tab);

        if (tab?.url) {
            const url = new URL(tab.url);
            const hostname = url.hostname;
            console.log('🌐 Current hostname:', hostname);
            return hostname;
        }

        console.log('🌐 No URL found in tab');
    } catch (error) {
        console.error('❌ Error getting current tab:', error);
    }
    return '';
};

// API Functions
const apiRequest = async (endpoint, options = {}) => {
    const url = `${API_BASE_URL}${endpoint}`;
    const headers = {
        'Content-Type': 'application/json',
        ...options.headers
    };

    if (authToken) {
        headers['Authorization'] = `Bearer ${authToken}`;
    }

    try {
        const response = await fetch(url, {
            ...options,
            headers
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || `HTTP ${response.status}`);
        }

        return data;
    } catch (error) {
        console.error('API Request failed:', error);
        throw error;
    }
};

const checkAPIHealth = async () => {
    try {
        await apiRequest('/health');
        updateStatus('online', 'Bağlı');
        return true;
    } catch (error) {
        updateStatus('offline', 'Çevrimdışı');
        return false;
    }
};

const login = async (email, password) => {
    const response = await apiRequest('/auth/login', {
        method: 'POST',
        body: JSON.stringify({ email, password })
    });

    if (response.success) {
        authToken = response.data.token;
        currentUser = response.data.user;

        // Save token to storage
        await chrome.storage.local.set({ authToken, currentUser });

        return response;
    }

    throw new Error(response.message || 'Giriş başarısız');
};

const logout = async () => {
    try {
        if (authToken) {
            await apiRequest('/auth/logout', { method: 'POST' });
        }
    } catch (error) {
        console.error('Logout error:', error);
    }

    // Clear local storage
    authToken = null;
    currentUser = null;
    await chrome.storage.local.remove(['authToken', 'currentUser']);

    showLoginForm();
};

const getUserInfo = async () => {
    const response = await apiRequest('/auth/user');
    if (response.success) {
        currentUser = response.data.user;
        return response.data.user;
    }
    throw new Error('Kullanıcı bilgileri alınamadı');
};

const getPasswords = async (search = '') => {
    const url = search ? `/passwords?search=${encodeURIComponent(search)}` : '/passwords';
    const response = await apiRequest(url);

    if (response.success) {
        allPasswords = response.data.data || [];
        return allPasswords;
    }

    throw new Error('Şifreler alınamadı');
};

const decryptPassword = async (passwordId) => {
    const response = await apiRequest(`/passwords/${passwordId}/decrypt`, {
        method: 'POST'
    });

    if (response.success) {
        return response.data.password;
    }

    throw new Error('Şifre çözülemedi');
};

const addPassword = async (passwordData) => {
    const response = await apiRequest('/passwords', {
        method: 'POST',
        body: JSON.stringify(passwordData)
    });

    if (response.success) {
        return response.data;
    }

    throw new Error('Şifre eklenemedi');
};

const generateSecurePassword = async () => {
    try {
        const response = await apiRequest('/passwords/generate', { method: 'POST' });
        if (response.success) {
            return response.data.password;
        }
    } catch (error) {
        console.error('Password generation failed:', error);
    }

    // Fallback local generation
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
    let password = '';
    for (let i = 0; i < 16; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return password;
};

// UI Functions
const showLoading = () => {
    loadingDiv.style.display = 'block';
    loginSection.style.display = 'none';
    mainContent.style.display = 'none';
};

const showLoginForm = () => {
    loadingDiv.style.display = 'none';
    loginSection.style.display = 'block';
    mainContent.style.display = 'none';
    loginError.style.display = 'none';
};

const showMainContent = () => {
    loadingDiv.style.display = 'none';
    loginSection.style.display = 'none';
    mainContent.style.display = 'block';

    // Update user info
    if (currentUser) {
        userNameSpan.textContent = currentUser.name;
        userEmailSpan.textContent = currentUser.email;
    }
};

const getPasswordIcon = (title) => {
    return title.charAt(0).toUpperCase();
};

const formatUrl = (url) => {
    try {
        return new URL(url).hostname;
    } catch {
        return url;
    }
};

const createPasswordItem = (password) => {
    const div = document.createElement('div');
    div.className = 'password-item';
    div.innerHTML = `
        <div class="password-icon">${getPasswordIcon(password.title)}</div>
        <div class="password-details">
            <div class="password-title">${password.title}</div>
            <div class="password-username">${password.username || 'Kullanıcı adı yok'}</div>
            ${password.url ? `<div class="password-url">${formatUrl(password.url)}</div>` : ''}
        </div>
        <div class="password-actions">
            <button class="action-btn copy" data-action="copy" data-id="${password.id}" title="Şifreyi Kopyala">
                📋
            </button>
            <button class="action-btn" data-action="view" data-id="${password.id}" title="Detayları Gör">
                👁️
            </button>
        </div>
    `;

    return div;
};

const renderPasswords = (passwords, container) => {
    console.log('🎨 Rendering passwords:', passwords?.length || 0, 'to container:', container?.id || 'unknown');

    if (!container) {
        console.error('❌ Container not found!');
        return;
    }

    if (!passwords || passwords.length === 0) {
        console.log('📭 No passwords to show');
        container.innerHTML = `
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 9v6m0 0v6m0-6h6m-6 0H6" stroke="currentColor" stroke-width="2"/>
                </svg>
                <p>Henüz şifre yok</p>
            </div>
        `;
        return;
    }

    container.innerHTML = '';
    passwords.forEach((password, index) => {
        console.log(`🔐 Rendering password ${index + 1}:`, password.title);
        const item = createPasswordItem(password);
        container.appendChild(item);
    });
    console.log('✅ Rendered', passwords.length, 'passwords');
};

const filterPasswordsForCurrentSite = (passwords, siteUrl) => {
    console.log('🔍 Filtering passwords for site:', siteUrl);
    console.log('🔍 All passwords to filter:', passwords);

    if (!siteUrl) {
        console.log('🔍 No site URL provided');
        return [];
    }

    const filtered = passwords.filter(password => {
        if (!password.url) {
            console.log('🔍 Password has no URL:', password.title);
            return false;
        }

        try {
            const passwordHost = new URL(password.url).hostname;
            const match = passwordHost.includes(siteUrl) || siteUrl.includes(passwordHost);
            console.log(`🔍 ${password.title}: ${passwordHost} vs ${siteUrl} = ${match}`);
            return match;
        } catch {
            const match = password.url.includes(siteUrl);
            console.log(`🔍 ${password.title}: ${password.url} vs ${siteUrl} = ${match} (fallback)`);
            return match;
        }
    });

    console.log('🔍 Filtered passwords:', filtered);
    return filtered;
};

const refreshPasswordLists = async () => {
    try {
        console.log('🔄 Refreshing password lists...');
        const passwords = await getPasswords();
        console.log('📋 Got passwords:', passwords);

        // Recent passwords (last 5)
        const recentPasswords = passwords.slice(0, 5);
        console.log('🕒 Recent passwords:', recentPasswords);
        renderPasswords(recentPasswords, recentPasswordsList);

        // All passwords
        console.log('📂 All passwords:', passwords);
        renderPasswords(passwords, allPasswordsList);

        // Current site passwords
        const currentSitePasswords = filterPasswordsForCurrentSite(passwords, currentSiteUrl);
        console.log('🌐 Current site passwords for', currentSiteUrl, ':', currentSitePasswords);
        renderPasswords(currentSitePasswords, currentSitePasswordsList);

        // Update current site tab title
        currentSiteTab.textContent = currentSiteUrl ? `${currentSiteUrl} (${currentSitePasswords.length})` : 'Bu Site';

    } catch (error) {
        console.error('❌ Error refreshing passwords:', error);
        showError('Şifreler yüklenirken hata oluştu');
    }
};

const copyToClipboard = async (text) => {
    console.log('📋 Attempting to copy:', text);

    try {
        // Primary method: Clipboard API
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
            console.log('✅ Copied with Clipboard API');
            showSuccess('Panoya kopyalandı');
            return;
        }

        // Fallback method: Create temporary textarea
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        const successful = document.execCommand('copy');
        document.body.removeChild(textArea);

        if (successful) {
            console.log('✅ Copied with execCommand');
            showSuccess('Panoya kopyalandı');
        } else {
            throw new Error('execCommand failed');
        }

    } catch (error) {
        console.error('❌ Copy failed:', error);
        showError('Kopyalama başarısız: ' + error.message);
    }
};

// Event Listeners
loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const email = emailInput.value.trim();
    const password = passwordInput.value;

    if (!email || !password) {
        showError('Lütfen tüm alanları doldurun');
        return;
    }

    try {
        showLoading();
        await login(email, password);
        await init();
    } catch (error) {
        showLoginForm();
        showError(error.message);
    }
});

logoutBtn.addEventListener('click', logout);

// Tab switching
tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        const targetTab = btn.dataset.tab;

        // Update tab buttons
        tabBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        // Update tab panes
        tabPanes.forEach(pane => pane.classList.remove('active'));
        document.getElementById(`${targetTab}-tab`).classList.add('active');
    });
});

// Search functionality
searchInput.addEventListener('input', async (e) => {
    const searchTerm = e.target.value.trim();

    try {
        const passwords = await getPasswords(searchTerm);
        renderPasswords(passwords, allPasswordsList);
    } catch (error) {
        console.error('Search error:', error);
    }
});

// Password actions
document.addEventListener('click', async (e) => {
    if (e.target.classList.contains('action-btn')) {
        const action = e.target.dataset.action;
        const passwordId = e.target.dataset.id;

        console.log('🔘 Button clicked:', action, 'for password ID:', passwordId);

        if (action === 'copy') {
            try {
                console.log('🔓 Decrypting password...');
                const decryptedPassword = await decryptPassword(passwordId);
                console.log('🔓 Password decrypted successfully:', decryptedPassword ? 'Yes' : 'No');

                if (!decryptedPassword) {
                    throw new Error('Decrypt returned empty password');
                }

                await copyToClipboard(decryptedPassword);
            } catch (error) {
                console.error('❌ Copy password failed:', error);
                showError('Şifre kopyalanamadı: ' + error.message);
            }
        } else if (action === 'view') {
            // TODO: Show password details modal
            console.log('View password:', passwordId);
        }
    }
});

// Modal functions
addPasswordBtn.addEventListener('click', () => {
    addPasswordModal.classList.add('show');
    // Pre-fill URL if we're on a website
    if (currentSiteUrl) {
        document.getElementById('new-url').value = `https://${currentSiteUrl}`;
    }
});

closeModalBtn.addEventListener('click', () => {
    addPasswordModal.classList.remove('show');
});

cancelAddBtn.addEventListener('click', () => {
    addPasswordModal.classList.remove('show');
});

generatePasswordBtn.addEventListener('click', async () => {
    try {
        const generatedPassword = await generateSecurePassword();
        document.getElementById('new-password').value = generatedPassword;
    } catch (error) {
        console.error('Password generation failed:', error);
    }
});

addPasswordForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = new FormData(e.target);
    const passwordData = {
        title: formData.get('title') || document.getElementById('new-title').value,
        url: formData.get('url') || document.getElementById('new-url').value,
        username: formData.get('username') || document.getElementById('new-username').value,
        password: formData.get('password') || document.getElementById('new-password').value
    };

    try {
        await addPassword(passwordData);
        addPasswordModal.classList.remove('show');
        addPasswordForm.reset();
        await refreshPasswordLists();
        showSuccess('Şifre başarıyla eklendi');
    } catch (error) {
        showError('Şifre eklenirken hata oluştu');
    }
});

// Initialize the extension
const init = async () => {
    try {
        // Debug: DOM elementlerini kontrol et
        console.log('🔧 DOM Elements Check:');
        console.log('Recent passwords list:', recentPasswordsList);
        console.log('Current site passwords list:', currentSitePasswordsList);
        console.log('All passwords list:', allPasswordsList);

        // Check API health
        const isAPIOnline = await checkAPIHealth();
        if (!isAPIOnline) {
            showError('API sunucusuna erişilemiyor');
            return;
        }

        // Get current site URL
        currentSiteUrl = await getCurrentTab();

        // Check stored authentication
        const stored = await chrome.storage.local.get(['authToken', 'currentUser']);
        if (stored.authToken && stored.currentUser) {
            authToken = stored.authToken;
            currentUser = stored.currentUser;

            try {
                // Verify token is still valid
                await getUserInfo();
                showMainContent();
                await refreshPasswordLists();
            } catch (error) {
                // Token is invalid, show login
                await logout();
            }
        } else {
            showLoginForm();
        }
    } catch (error) {
        console.error('Initialization error:', error);
        showError('Eklenti başlatılırken hata oluştu');
    }
};

// Start the extension when popup opens
document.addEventListener('DOMContentLoaded', init);
