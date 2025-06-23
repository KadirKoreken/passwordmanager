/**
 * Şifre Yöneticisi Browser Extension - Content Script
 * Web sayfalarındaki form alanlarını algılar ve otomatik doldurma özelliği sağlar
 */

class PasswordManagerContentScript {
    constructor() {
        this.sdk = null;
        this.isInjected = false;
        this.currentDomain = this.extractDomain(window.location.href);
        this.init();
    }

    async init() {
        // SDK'yi yükle
        await this.loadSDK();

        // Form alanlarını algıla
        this.detectLoginForms();

        // DOM değişikliklerini izle
        this.observeFormChanges();

        console.log('Şifre Yöneticisi Extension aktif:', this.currentDomain);
    }

    /**
     * SDK'yi yükle
     */
    async loadSDK() {
        return new Promise((resolve) => {
            if (window.PasswordManagerSDK) {
                this.sdk = new window.PasswordManagerSDK();
                this.sdk.initialize();
                resolve();
                return;
            }

            // SDK script'ini inject et
            const script = document.createElement('script');
            script.src = chrome.runtime.getURL('password-manager-sdk.js');
            script.onload = () => {
                this.sdk = new window.PasswordManagerSDK();
                this.sdk.initialize();
                resolve();
            };
            document.head.appendChild(script);
        });
    }

    /**
     * Login formlarını algıla
     */
    detectLoginForms() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => this.processForm(form));
    }

    /**
     * Form'u işle
     */
    processForm(form) {
        const emailFields = form.querySelectorAll('input[type="email"], input[name*="email"], input[name*="username"], input[id*="email"], input[id*="username"]');
        const passwordFields = form.querySelectorAll('input[type="password"]');

        if (emailFields.length > 0 && passwordFields.length > 0) {
            this.addAutoFillButton(form, emailFields[0], passwordFields[0]);
        }
    }

    /**
     * Otomatik doldurma butonu ekle
     */
    addAutoFillButton(form, emailField, passwordField) {
        // Duplicate button kontrolü
        if (form.querySelector('.pm-autofill-btn')) {
            return;
        }

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'pm-autofill-btn';
        button.innerHTML = '🔑 Şifre Doldur';
        button.style.cssText = `
            position: absolute;
            background: #4F46E5;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            z-index: 10000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        `;

        // Butonu password field'ın yanına yerleştir
        const rect = passwordField.getBoundingClientRect();
        button.style.top = (rect.top + window.scrollY) + 'px';
        button.style.left = (rect.right + window.scrollX + 10) + 'px';

        button.addEventListener('click', async (e) => {
            e.preventDefault();
            await this.showPasswordOptions(emailField, passwordField);
        });

        document.body.appendChild(button);
    }

    /**
     * Şifre seçeneklerini göster
     */
    async showPasswordOptions(emailField, passwordField) {
        try {
            // Domain'e ait şifreleri getir
            const passwords = await this.sdk.findPasswordsByDomain(this.currentDomain);

            if (passwords.length === 0) {
                this.showNotification('Bu site için kayıtlı şifre bulunamadı.');
                return;
            }

            // Seçenek modalı oluştur
            this.createPasswordSelectionModal(passwords, emailField, passwordField);
        } catch (error) {
            console.error('Şifre getirme hatası:', error);
            this.showNotification('Şifre yüklenirken hata oluştu.');
        }
    }

    /**
     * Şifre seçim modalı oluştur
     */
    createPasswordSelectionModal(passwords, emailField, passwordField) {
        // Mevcut modal varsa kaldır
        const existingModal = document.querySelector('.pm-modal');
        if (existingModal) {
            existingModal.remove();
        }

        const modal = document.createElement('div');
        modal.className = 'pm-modal';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 10001;
            display: flex;
            align-items: center;
            justify-content: center;
        `;

        const content = document.createElement('div');
        content.style.cssText = `
            background: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 400px;
            width: 90%;
            max-height: 500px;
            overflow-y: auto;
        `;

        content.innerHTML = `
            <h3 style="margin-top: 0;">Şifre Seç</h3>
            <div class="password-list"></div>
            <button class="pm-close-btn" style="margin-top: 15px; padding: 8px 16px; background: #6B7280; color: white; border: none; border-radius: 4px; cursor: pointer;">Kapat</button>
        `;

        const passwordList = content.querySelector('.password-list');
        passwords.forEach(password => {
            const item = document.createElement('div');
            item.style.cssText = `
                padding: 10px;
                border: 1px solid #E5E7EB;
                border-radius: 4px;
                margin-bottom: 8px;
                cursor: pointer;
                transition: background 0.2s;
            `;
            item.innerHTML = `
                <strong>${password.title}</strong><br>
                <small>${password.username}</small>
            `;

            item.addEventListener('click', async () => {
                await this.fillPassword(password, emailField, passwordField);
                modal.remove();
            });

            item.addEventListener('mouseenter', () => {
                item.style.background = '#F3F4F6';
            });

            item.addEventListener('mouseleave', () => {
                item.style.background = '';
            });

            passwordList.appendChild(item);
        });

        // Kapat butonu
        content.querySelector('.pm-close-btn').addEventListener('click', () => {
            modal.remove();
        });

        // Modal dışına tıklayınca kapat
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });

        modal.appendChild(content);
        document.body.appendChild(modal);
    }

    /**
     * Şifreyi form alanlarına doldur
     */
    async fillPassword(passwordData, emailField, passwordField) {
        try {
            // Şifreyi çöz
            const decryptedData = await this.sdk.decryptPassword(passwordData.id);

            // Alanları doldur
            emailField.value = passwordData.username;
            passwordField.value = decryptedData.data.password;

            // Change event'lerini tetikle
            emailField.dispatchEvent(new Event('input', { bubbles: true }));
            emailField.dispatchEvent(new Event('change', { bubbles: true }));
            passwordField.dispatchEvent(new Event('input', { bubbles: true }));
            passwordField.dispatchEvent(new Event('change', { bubbles: true }));

            this.showNotification('Şifre dolduruldu!');
        } catch (error) {
            console.error('Şifre doldurma hatası:', error);
            this.showNotification('Şifre doldurulamadı.');
        }
    }

    /**
     * Bildirim göster
     */
    showNotification(message) {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #10B981;
            color: white;
            padding: 12px 20px;
            border-radius: 6px;
            z-index: 10002;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        notification.textContent = message;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    /**
     * Domain çıkar
     */
    extractDomain(url) {
        try {
            return new URL(url).hostname.replace('www.', '');
        } catch {
            return '';
        }
    }

    /**
     * Form değişikliklerini izle
     */
    observeFormChanges() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        const forms = node.tagName === 'FORM' ? [node] : node.querySelectorAll('form');
                        forms.forEach(form => this.processForm(form));
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
}

// Content script'i başlat
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new PasswordManagerContentScript();
    });
} else {
    new PasswordManagerContentScript();
}
