<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SecurityService
{
    /**
     * IP adresinden ülke/şehir bilgisi al (basit implementasyon)
     */
    public function getLocationFromIP($ip)
    {
        // Production'da gerçek IP geolocation service kullanın
        // Bu örnek implementasyon
        if ($ip === '127.0.0.1' || $ip === 'localhost') {
            return ['country' => 'Local', 'city' => 'Local'];
        }

        // Gerçek uygulamada bu kısmı bir IP geolocation service ile değiştirin
        return ['country' => 'Unknown', 'city' => 'Unknown'];
    }

    /**
     * Şüpheli giriş kontrolü
     */
    public function detectSuspiciousLogin(User $user, $currentIP, $userAgent)
    {
        $isSuspicious = false;
        $reasons = [];

        // Son giriş IP'si ile farklılık kontrolü
        if ($user->last_login_ip && $user->last_login_ip !== $currentIP) {
            $reasons[] = 'Different IP address';
            $isSuspicious = true;
        }

        // Çok sayıda başarısız giriş denemesi
        if ($user->failed_login_attempts >= 3) {
            $reasons[] = 'Multiple failed login attempts';
            $isSuspicious = true;
        }

        // Hesap kilitli mi kontrolü
        if ($user->locked_until && Carbon::parse($user->locked_until)->isFuture()) {
            $reasons[] = 'Account is locked';
            $isSuspicious = true;
        }

        // Son 24 saat içinde farklı IP'lerden çok fazla giriş
        $recentIPs = AuditLog::where('user_id', $user->id)
            ->where('action', 'login')
            ->where('created_at', '>=', now()->subDay())
            ->distinct('ip_address')
            ->pluck('ip_address')
            ->toArray();

        if (count($recentIPs) > 5) {
            $reasons[] = 'Too many different IPs in 24h';
            $isSuspicious = true;
        }

        return [
            'is_suspicious' => $isSuspicious,
            'reasons' => $reasons
        ];
    }

    /**
     * Kullanıcı giriş bilgilerini güncelle
     */
    public function updateLoginInfo(User $user, $ipAddress, $userAgent)
    {
        $location = $this->getLocationFromIP($ipAddress);

        // Login history'yi güncelle
        $loginHistory = $user->login_history ?? [];

        $newEntry = [
            'ip' => $ipAddress,
            'user_agent' => $userAgent,
            'timestamp' => now()->toISOString(),
            'country' => $location['country'],
            'city' => $location['city']
        ];

        // Son 10 giriş kaydını tut
        array_unshift($loginHistory, $newEntry);
        $loginHistory = array_slice($loginHistory, 0, 10);

        $user->update([
            'last_login_ip' => $ipAddress,
            'last_login_at' => now(),
            'failed_login_attempts' => 0, // Başarılı girişte sıfırla
            'locked_until' => null, // Kilidi kaldır
            'login_history' => $loginHistory
        ]);

        // Audit log
        AuditLog::logAction(
            'login',
            $user->id,
            $ipAddress,
            $userAgent,
            [
                'country' => $location['country'],
                'city' => $location['city'],
                'login_method' => 'password'
            ],
            'low'
        );
    }

    /**
     * Başarısız giriş denemesini kaydet
     */
    public function recordFailedLogin($email, $ipAddress, $userAgent)
    {
        $user = User::where('email', $email)->first();

        if ($user) {
            $user->increment('failed_login_attempts');

            // 5 başarısız denemeden sonra hesabı 15 dakika kilitle
            if ($user->failed_login_attempts >= 5) {
                $user->update(['locked_until' => now()->addMinutes(15)]);
            }
        }

        // Audit log
        AuditLog::logAction(
            'failed_login',
            $user?->id,
            $ipAddress,
            $userAgent,
            [
                'email' => $email,
                'attempt_count' => $user?->failed_login_attempts ?? 0
            ],
            'medium',
            true
        );
    }

    /**
     * Kayıt IP'sini kaydet
     */
    public function recordRegistrationIP(User $user, $ipAddress)
    {
        $location = $this->getLocationFromIP($ipAddress);

        $user->update([
            'registration_ip' => $ipAddress,
            'last_login_ip' => $ipAddress,
            'last_login_at' => now()
        ]);

        // Audit log
        AuditLog::logAction(
            'register',
            $user->id,
            $ipAddress,
            request()->userAgent(),
            [
                'country' => $location['country'],
                'city' => $location['city']
            ],
            'low'
        );
    }

        /**
     * Şifre erişim kontrolü ve loglama
     */
    public function logPasswordAccess($passwordId, $action = 'view', $details = [], $severity = null, $isSuspicious = false)
    {
        $user = auth()->user();

        AuditLog::logAction(
            $action === 'logout' ? 'logout' : 'password_' . $action,
            $user ? $user->id : null,
            request()->ip(),
            request()->userAgent(),
            array_merge([
                'password_id' => $passwordId,
                'action' => $action
            ], $details),
            $severity ?: ($action === 'decrypt' ? 'medium' : 'low'),
            $isSuspicious
        );
    }

    /**
     * Şüpheli aktivite kontrolü
     */
    public function checkSuspiciousPasswordActivity($userId)
    {
        // Son 5 dakikada çok fazla şifre çözme işlemi
        $recentDecrypts = AuditLog::where('user_id', $userId)
            ->where('action', 'password_decrypt')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();

        if ($recentDecrypts > 10) {
            AuditLog::logAction(
                'suspicious_activity',
                $userId,
                request()->ip(),
                request()->userAgent(),
                [
                    'type' => 'excessive_password_decryption',
                    'count' => $recentDecrypts
                ],
                'high',
                true
            );

            return true;
        }

        return false;
    }

    /**
     * Güvenli şifre hash'i oluştur (ekstra salt ile)
     */
    public function createSecurePasswordHash($password, $user = null)
    {
        $salt = Str::random(32);

        if ($user) {
            $user->update(['password_salt' => $salt]);
        }

        // Laravel'in varsayılan hash'ine ek olarak custom salt ekle
        return Hash::make($password . $salt);
    }

    /**
     * Güvenli şifre doğrulama (salt ile)
     */
    public function verifySecurePassword($password, $hashedPassword, $salt)
    {
        return Hash::check($password . $salt, $hashedPassword);
    }

    /**
     * API isteği için güvenlik kontrolü
     */
    public function validateAPIRequest(Request $request)
    {
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $user = $request->user();

        // Rate limiting kontrolü (dakikada çok fazla istek)
        $recentRequests = AuditLog::where('ip_address', $ip)
            ->where('created_at', '>=', now()->subMinute())
            ->count();

        if ($recentRequests > 60) {
            AuditLog::logAction(
                'rate_limit_exceeded',
                $user?->id,
                $ip,
                $userAgent,
                ['request_count' => $recentRequests],
                'high',
                true
            );

            return false;
        }

        return true;
    }

    /**
     * Hesap kilidi kontrolü
     */
    public function isAccountLocked(User $user)
    {
        if ($user->locked_until && Carbon::parse($user->locked_until)->isFuture()) {
            return true;
        }

        return false;
    }
}
