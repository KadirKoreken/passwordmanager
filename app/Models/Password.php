<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Password extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'url',
        'username',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Şifreyi multi-layer encryption ile kaydet
     */
    public function setPasswordAttribute($value)
    {
        if ($value) {
            // İlk katman: User-specific salt ile hash
            $userSalt = $this->getUserSalt();
            $saltedPassword = $value . $userSalt;

            // İkinci katman: Laravel Crypt ile encrypt
            $encryptedPassword = Crypt::encryptString($saltedPassword);

            // Üçüncü katman: Ekstra obfuscation
            $this->attributes['password'] = base64_encode($encryptedPassword);
        }
    }

    /**
     * Şifreyi multi-layer decryption ile al
     */
    public function getPasswordAttribute($value)
    {
        try {
            if (!$value) {
                return '';
            }

            // Eğer kullanıcı giriş yapmamışsa şifreyi döndürme
            if (!auth()->check()) {
                return '[ENCRYPTED]';
            }

            // Kullanıcı kendi şifresine erişmeye çalışıyor mu kontrol et
            if (auth()->id() !== $this->user_id) {
                return '[UNAUTHORIZED]';
            }

            // Üçüncü katman: Base64 decode
            $encryptedPassword = base64_decode($value);

            // İkinci katman: Laravel Crypt ile decrypt
            $saltedPassword = Crypt::decryptString($encryptedPassword);

            // İlk katman: User salt'ını çıkar
            $userSalt = $this->getUserSalt();
            if (str_ends_with($saltedPassword, $userSalt)) {
                $originalPassword = substr($saltedPassword, 0, -strlen($userSalt));
            } else {
                $originalPassword = $saltedPassword;
            }

            return $originalPassword;
        } catch (\Exception $e) {
            // Decrypt hatası durumunda boş döndür
            return '';
        }
    }

    /**
     * Kullanıcıya özel salt al
     */
    private function getUserSalt()
    {
        if (!$this->user) {
            return 'default_salt_' . $this->user_id;
        }

        // User'ın password_salt'ı varsa onu kullan, yoksa ID bazlı salt oluştur
        return $this->user->password_salt ?: ('user_salt_' . $this->user_id . '_' . substr(md5($this->user->email), 0, 8));
    }

    /**
     * Şifrelenmiş şifreyi döndüren accessor
     */
    public function getDecryptedPasswordAttribute()
    {
        return $this->getPasswordAttribute($this->attributes['password'] ?? '');
    }

    /**
     * User ile ilişki
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
