# Şifre Yöneticisi - API Dönüşüm Süreci

## Proje Hakkında
Bu Laravel tabanlı şifre yöneticisi projesi, kullanıcıların şifrelerini güvenli bir şekilde saklamalarını sağlar. Bu README, projeyi mobile app ve tarayıcı eklentisi geliştirmek için API uyumlu hale getirme sürecini detaylandırır.

## Mevcut Durum
- ✅ Laravel 12 framework
- ✅ Livewire 3 kullanılıyor
- ✅ **Enhanced Multi-Layer Encryption** aktif
- ✅ **Complete API endpoints** - RESTful API hazır
- ✅ **Laravel Sanctum** authentication sistemi
- ✅ **IP Tracking ve Audit Logging** sistemi
- ✅ **Advanced Security Middleware** aktif
- ✅ **Rate Limiting** ve şüpheli aktivite tespiti
- ✅ **Mobile/Browser extension** için hazır
- ✅ **Güvenlik monitoring** ve incident reporting

## API Dönüşüm Süreci

### Adım 1: Laravel Sanctum Kurulumu
Laravel Sanctum'u yükleyip konfigüre edeceğiz (API token authentication için).

**Yapılacaklar:**
- Sanctum paketini yükle
- Migration'ları çalıştır
- Sanctum konfigürasyonunu yap
- CORS ayarlarını düzenle

### Adım 2: API Route Yapısı
RESTful API endpoints oluşturacağız.

**Endpoint'ler:**
```
POST   /api/auth/login         - Giriş yapma
POST   /api/auth/logout        - Çıkış yapma
POST   /api/auth/register      - Kayıt olma
GET    /api/user              - Kullanıcı bilgileri

GET    /api/passwords         - Tüm şifreleri listele
POST   /api/passwords         - Yeni şifre ekle
GET    /api/passwords/{id}    - Belirli şifreyi getir
PUT    /api/passwords/{id}    - Şifreyi güncelle
DELETE /api/passwords/{id}    - Şifreyi sil
POST   /api/passwords/search  - Şifre arama
POST   /api/passwords/{id}/decrypt - Şifreyi çöz
```

### Adım 3: API Controller'ları
Mevcut Livewire logic'ini API controller'larına dönüştüreceğiz.

**Controller'lar:**
- AuthController
- PasswordController
- UserController

### Adım 4: API Resource Classes
JSON response'ları standardize etmek için Resource classes oluşturacağız.

### Adım 5: Request Validation
API request'leri için validation classes oluşturacağız.

### Adım 6: Error Handling
API için unified error handling sistemi kuracağız.

### Adım 7: Rate Limiting
API endpoint'leri için rate limiting ekleyeceğiz.

### Adım 8: API Documentation
API dokümantasyonu hazırlayacağız (Postman collection + OpenAPI).

### Adım 9: Testing
API endpoints için test yazacağız.

### Adım 10: Security Enhancements
- CORS konfigürasyonu
- API key validation
- Request throttling
- Input sanitization

## Teknik Detaylar

### Authentication Flow
1. Kullanıcı email/password ile login olur
2. Sanctum token alır
3. Her API request'inde Bearer token kullanır
4. Token expire olduğunda yeniden login gerekir

### Data Security
- Şifreler encrypt edilmiş şekilde saklanır
- API token'ları secure şekilde yönetilir
- HTTPS zorunlu
- Input validation katmanları

### Mobile App Entegrasyonu
- React Native / Flutter için ready
- Secure token storage
- Offline data caching capability
- Biometric authentication support

### Browser Extension Entegrasyonu
- Chrome/Firefox extension ready
- Auto-fill functionality
- Domain-based password matching
- Secure communication with API

## Geliştirme Ortamı Gereksinimleri
- PHP 8.2+
- Laravel 12
- MySQL/PostgreSQL
- Composer
- NPM/Node.js

## Kurulum Adımları (API dönüşümü sonrası)
```bash
# 1. Repository'yi klonla
git clone [repo-url]
cd passwordmanager

# 2. Bağımlılıkları yükle
composer install
npm install

# 3. Environment dosyasını hazırla
cp .env.example .env
php artisan key:generate

# 4. Veritabanını konfigüre et
php artisan migrate

# 5. Sanctum setup
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate

# 6. Development server'ı başlat
php artisan serve
```

## API Kullanım Örnekleri

### Authentication
```bash
# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'

# Response
{
  "token": "1|abc123...",
  "user": {...}
}
```

### Password Management
```bash
# Şifre ekleme
curl -X POST http://localhost:8000/api/passwords \
  -H "Authorization: Bearer 1|abc123..." \
  -H "Content-Type: application/json" \
  -d '{"title":"Gmail","username":"user@gmail.com","password":"secret123","url":"https://gmail.com"}'
```

## Mobile App Geliştirme Notları
- API base URL'i konfigüre edilebilir olmalı
- Token'ları secure storage'da sakla (Keychain/Android Keystore)
- Network timeouts'u handle et
- Offline mode için local database kullan

## Browser Extension Geliştirme Notları
- Manifest V3 uyumlu
- Content Security Policy kurallarına uy
- Cross-origin requests için proper CORS
- User data privacy'sini koru

## İleriki Adımlar
1. ✅ API dönüşümü tamamla
2. 📱 Mobile app geliştir (React Native/Flutter)
3. 🔗 Browser extension geliştir (Chrome/Firefox)
4. 🔒 Advanced security features (2FA, biometric)
5. 📊 Analytics ve monitoring
6. 🚀 Production deployment

## Güvenlik Önerileri
- API rate limiting aktif
- HTTPS zorunlu
- Token expiration süreleri kısa
- Input validation katmanları
- SQL injection koruması
- XSS koruması
- CSRF koruması (SPA için)

## 🔐 Gelişmiş Güvenlik Özellikleri

### Enhanced Multi-Layer Encryption
- **3 Katmanlı Şifreleme**: User-specific salt + Laravel Crypt + Base64 obfuscation
- **Unauthorized Access Prevention**: Giriş yapmadan şifrelere erişim tamamen engellenir
- **User Isolation**: Kullanıcılar sadece kendi şifrelerine erişebilir

### IP Tracking ve Audit System
- **Real-time IP Tracking**: Her giriş, kayıt ve şifre erişimi IP ile izlenir
- **Comprehensive Audit Logs**: Tüm sistem aktiviteleri detaylı olarak kaydedilir
- **Suspicious Activity Detection**: Şüpheli davranışlar otomatik tespit edilir
- **Login History**: Kullanıcıların giriş geçmişi JSON formatında saklanır

### Advanced Security Controls
- **Account Locking**: Başarısız denemeler sonrası otomatik hesap kilitleme (15 dakika)
- **Rate Limiting**: IP bazlı ve endpoint bazlı istek sınırlaması
- **Security Middleware**: API istekleri için kapsamlı güvenlik kontrolü
- **Incident Reporting**: Kullanıcıların güvenlik olaylarını rapor edebilmesi

### Real-time Monitoring
```bash
# Güvenlik API Endpoints
GET /api/security/audit-logs           # Kullanıcının audit logları
GET /api/security/suspicious-activities # Şüpheli aktiviteler
GET /api/security/login-history        # Giriş geçmişi
GET /api/security/stats               # Güvenlik istatistikleri
POST /api/security/report-incident     # Güvenlik olayı raporu
```

### Güvenlik Test Sonuçları
```
✅ Multi-layer encryption aktif
✅ IP tracking çalışıyor  
✅ Audit logging aktif
✅ Rate limiting çalışıyor
✅ Yetkisiz erişim koruması aktif
✅ Şifre güvenliği sağlandı
✅ Güvenlik monitoring sistemi aktif
```

---

Bu README, projenin API dönüşüm sürecini ve gelişmiş güvenlik özelliklerini detaylandırır. Sistem şimdi **enterprise-grade güvenlik** ile mobil uygulamalar ve tarayıcı eklentileri için hazırdır.
