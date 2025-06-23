/**
 * Şifre Yöneticisi SDK
 * Mobile App ve Browser Extension için kullanım
 */

class PasswordManagerSDK {
    constructor(baseUrl = 'http://127.0.0.1:8000/api') {
        this.baseUrl = baseUrl;
        this.token = null;
    }

    /**
     * HTTP isteği gönder
     */
    async request(endpoint, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;
        const config = {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...options.headers
            },
            ...options
        };

        if (this.token) {
            config.headers['Authorization'] = `Bearer ${this.token}`;
        }

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || `HTTP ${response.status}`);
            }

            return data;
        } catch (error) {
            console.error('API Request Error:', error);
            throw error;
        }
    }

    /**
     * Kullanıcı Kaydı
     */
    async register(name, email, password) {
        const response = await this.request('/auth/register', {
            method: 'POST',
            body: JSON.stringify({
                name,
                email,
                password,
                password_confirmation: password
            })
        });

        if (response.success) {
            this.token = response.data.token;
            this.saveToken(this.token);
        }

        return response;
    }

    /**
     * Kullanıcı Girişi
     */
    async login(email, password) {
        const response = await this.request('/auth/login', {
            method: 'POST',
            body: JSON.stringify({ email, password })
        });

        if (response.success) {
            this.token = response.data.token;
            this.saveToken(this.token);
        }

        return response;
    }

    /**
     * Kullanıcı Çıkışı
     */
    async logout() {
        if (!this.token) return;

        try {
            await this.request('/auth/logout', { method: 'POST' });
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            this.token = null;
            this.removeToken();
        }
    }

    /**
     * Kullanıcı Bilgileri
     */
    async getUserInfo() {
        return await this.request('/auth/user');
    }

    /**
     * Şifreleri Listele
     */
    async getPasswords(page = 1, perPage = 15, search = '') {
        const params = new URLSearchParams({
            page: page.toString(),
            per_page: perPage.toString()
        });

        if (search) {
            params.append('search', search);
        }

        return await this.request(`/passwords?${params}`);
    }

    /**
     * Şifre Oluştur
     */
    async createPassword(title, username, password, url = '') {
        return await this.request('/passwords', {
            method: 'POST',
            body: JSON.stringify({ title, username, password, url })
        });
    }

    /**
     * Şifre Güncelle
     */
    async updatePassword(id, title, username, password, url = '') {
        return await this.request(`/passwords/${id}`, {
            method: 'PUT',
            body: JSON.stringify({ title, username, password, url })
        });
    }

    /**
     * Şifre Sil
     */
    async deletePassword(id) {
        return await this.request(`/passwords/${id}`, { method: 'DELETE' });
    }

    /**
     * Şifre Ara
     */
    async searchPasswords(query) {
        return await this.request('/passwords/search', {
            method: 'POST',
            body: JSON.stringify({ query })
        });
    }

    /**
     * Şifre Çöz
     */
    async decryptPassword(id) {
        return await this.request(`/passwords/${id}/decrypt`, { method: 'POST' });
    }

    /**
     * Rastgele Şifre Üret
     */
    async generatePassword(options = {}) {
        const defaultOptions = {
            length: 12,
            include_symbols: true,
            include_numbers: true,
            include_uppercase: true,
            include_lowercase: true
        };

        return await this.request('/passwords/generate', {
            method: 'POST',
            body: JSON.stringify({ ...defaultOptions, ...options })
        });
    }

    /**
     * Token'ı kaydet (platform-specific implementation)
     */
    saveToken(token) {
        if (typeof localStorage !== 'undefined') {
            // Browser
            localStorage.setItem('password_manager_token', token);
        } else if (typeof require !== 'undefined') {
            // React Native - AsyncStorage kullanın
            // import AsyncStorage from '@react-native-async-storage/async-storage';
            // AsyncStorage.setItem('password_manager_token', token);
        }
    }

    /**
     * Token'ı yükle (platform-specific implementation)
     */
    loadToken() {
        if (typeof localStorage !== 'undefined') {
            // Browser
            return localStorage.getItem('password_manager_token');
        } else if (typeof require !== 'undefined') {
            // React Native - AsyncStorage kullanın
            // return AsyncStorage.getItem('password_manager_token');
        }
        return null;
    }

    /**
     * Token'ı sil
     */
    removeToken() {
        if (typeof localStorage !== 'undefined') {
            localStorage.removeItem('password_manager_token');
        }
    }

    /**
     * SDK'yi başlat
     */
    async initialize() {
        const savedToken = this.loadToken();
        if (savedToken) {
            this.token = savedToken;
            try {
                // Token'ın geçerli olup olmadığını kontrol et
                await this.getUserInfo();
            } catch (error) {
                // Token geçersiz, temizle
                this.removeToken();
                this.token = null;
            }
        }
    }

    /**
     * Domain'e göre şifre bul (Browser Extension için)
     */
    async findPasswordsByDomain(domain) {
        const searchResults = await this.searchPasswords(domain);
        return searchResults.data.filter(password =>
            password.url && password.url.includes(domain)
        );
    }

    /**
     * Health check
     */
    async healthCheck() {
        return await this.request('/health');
    }
}

// Export for different environments
if (typeof module !== 'undefined' && module.exports) {
    // Node.js / React Native
    module.exports = PasswordManagerSDK;
} else if (typeof window !== 'undefined') {
    // Browser
    window.PasswordManagerSDK = PasswordManagerSDK;
}

/**
 * Kullanım Örnekleri:
 *
 * // SDK'yi başlat
 * const sdk = new PasswordManagerSDK('https://your-api-domain.com/api');
 * await sdk.initialize();
 *
 * // Giriş yap
 * await sdk.login('user@example.com', 'password');
 *
 * // Şifreleri listele
 * const passwords = await sdk.getPasswords();
 *
 * // Yeni şifre ekle
 * await sdk.createPassword('Gmail', 'user@gmail.com', 'secret123', 'https://gmail.com');
 *
 * // Şifre ara
 * const results = await sdk.searchPasswords('gmail');
 *
 * // Domain'e göre şifre bul (Browser Extension için)
 * const domainPasswords = await sdk.findPasswordsByDomain('gmail.com');
 */
