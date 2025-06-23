<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Password;
use App\Services\SecurityService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PasswordController extends Controller
{
    protected $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }
    /**
     * Tüm şifreleri listele
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Güvenlik kontrolü - şüpheli aktivite
        if ($this->securityService->checkSuspiciousPasswordActivity($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Şüpheli aktivite tespit edildi. Lütfen daha sonra tekrar deneyin.'
            ], 429);
        }

        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');

        $query = $user->passwords()->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('url', 'like', "%{$search}%");
            });
        }

        $passwords = $query->paginate($perPage);

        // Şifreleri gizle, sadece encrypted halini gönder
        $passwords->getCollection()->transform(function ($password) {
            // Her şifre görüntüleme işlemini logla
            $this->securityService->logPasswordAccess($password->id, 'view');

            return [
                'id' => $password->id,
                'title' => $password->title,
                'url' => $password->url,
                'username' => $password->username,
                'created_at' => $password->created_at,
                'updated_at' => $password->updated_at,
                'has_password' => !empty($password->getRawOriginal('password')),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $passwords
        ]);
    }

    /**
     * Yeni şifre oluştur
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'url' => 'nullable|string|max:255',
            'username' => 'required|string|max:255',
            'password' => 'required|string|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $password = $request->user()->passwords()->create([
            'title' => $request->title,
            'url' => $request->url,
            'username' => $request->username,
            'password' => $request->password,
        ]);

        // Şifre oluşturma işlemini logla
        $this->securityService->logPasswordAccess($password->id, 'create', [
            'title' => $request->title,
            'url' => $request->url
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Şifre başarıyla oluşturuldu',
            'data' => [
                'id' => $password->id,
                'title' => $password->title,
                'url' => $password->url,
                'username' => $password->username,
                'created_at' => $password->created_at,
                'updated_at' => $password->updated_at,
            ]
        ], 201);
    }

    /**
     * Belirli şifreyi göster
     */
    public function show(Request $request, Password $password): JsonResponse
    {
        // Yetki kontrolü
        if ($password->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bu işlem için yetkiniz yok'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $password->id,
                'title' => $password->title,
                'url' => $password->url,
                'username' => $password->username,
                'created_at' => $password->created_at,
                'updated_at' => $password->updated_at,
                'has_password' => !empty($password->getRawOriginal('password')),
            ]
        ]);
    }

    /**
     * Şifreyi güncelle
     */
    public function update(Request $request, Password $password): JsonResponse
    {
        // Yetki kontrolü
        if ($password->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bu işlem için yetkiniz yok'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'url' => 'nullable|string|max:255',
            'username' => 'required|string|max:255',
            'password' => 'required|string|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $oldTitle = $password->title;

        $password->update([
            'title' => $request->title,
            'url' => $request->url,
            'username' => $request->username,
            'password' => $request->password,
        ]);

        // Şifre güncelleme işlemini logla
        $this->securityService->logPasswordAccess($password->id, 'update', [
            'old_title' => $oldTitle,
            'new_title' => $request->title,
            'url' => $request->url
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Şifre başarıyla güncellendi',
            'data' => [
                'id' => $password->id,
                'title' => $password->title,
                'url' => $password->url,
                'username' => $password->username,
                'created_at' => $password->created_at,
                'updated_at' => $password->updated_at,
            ]
        ]);
    }

    /**
     * Şifreyi sil
     */
    public function destroy(Request $request, Password $password): JsonResponse
    {
        // Yetki kontrolü
        if ($password->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bu işlem için yetkiniz yok'
            ], 403);
        }

        // Silme işlemini logla (silmeden önce)
        $this->securityService->logPasswordAccess($password->id, 'delete', [
            'title' => $password->title,
            'url' => $password->url
        ]);

        $password->delete();

        return response()->json([
            'success' => true,
            'message' => 'Şifre başarıyla silindi'
        ]);
    }

    /**
     * Şifre arama
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = $request->query;
        $passwords = $request->user()->passwords()
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('username', 'like', "%{$query}%")
                  ->orWhere('url', 'like', "%{$query}%");
            })
            ->latest()
            ->get()
            ->map(function ($password) {
                return [
                    'id' => $password->id,
                    'title' => $password->title,
                    'url' => $password->url,
                    'username' => $password->username,
                    'created_at' => $password->created_at,
                    'updated_at' => $password->updated_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $passwords
        ]);
    }

    /**
     * Şifreyi çöz ve gönder (güvenlik için ayrı endpoint)
     */
    public function decrypt(Request $request, Password $password): JsonResponse
    {
        $user = $request->user();

        // Yetki kontrolü
        if ($password->user_id !== $user->id) {
            $this->securityService->logPasswordAccess($password->id, 'unauthorized_decrypt_attempt', [
                'attempted_by_user' => $user->id,
                'actual_owner' => $password->user_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Bu işlem için yetkiniz yok'
            ], 403);
        }

        // Şüpheli aktivite kontrolü
        if ($this->securityService->checkSuspiciousPasswordActivity($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Şüpheli aktivite tespit edildi. Şifre çözme işlemi geçici olarak engellendi.'
            ], 429);
        }

        // Decrypt işlemini logla
        $this->securityService->logPasswordAccess($password->id, 'decrypt', [
            'password_title' => $password->title,
            'severity' => 'medium'
        ]);

                // Geçici olarak Password model'inin accessor'ını kullan
        // Auth user'ı set et ki accessor çalışsın
        auth()->setUser($user);

                try {
            // Şimdilik her zaman manuel decrypt kullan (accessor güvenilir değil)
            // $decryptedPassword = $password->password;

            // Her zaman manuel decrypt dene
            if (true) {
                // Fallback: Direkt database'den ham şifreyi al ve decode et
                $encryptedValue = $password->getRawOriginal('password');

                if (empty($encryptedValue)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Şifre bulunamadı'
                    ], 404);
                }

                // Base64 decode
                $decodedValue = base64_decode($encryptedValue);

                // Laravel Crypt ile decrypt
                $saltedPassword = \Illuminate\Support\Facades\Crypt::decryptString($decodedValue);

                 // Regex ile salt temizleme (hem başta hem sonda olabilir)
                 $decryptedPassword = $saltedPassword;

                 // Salt pattern'lerini regex ile temizle
                 $patterns = [
                     '/default_salt_\d*$/',  // Sonda default_salt_
                     '/^default_salt_\d*/',  // Başta default_salt_
                     '/user_salt_\d+_[a-f0-9]{8}$/', // Sonda user_salt_
                     '/^user_salt_\d+_[a-f0-9]{8}/', // Başta user_salt_
                     '/default_salt_$/',     // Sadece default_salt_
                     '/^default_salt_/',     // Başta default_salt_
                 ];

                 foreach ($patterns as $pattern) {
                     $decryptedPassword = preg_replace($pattern, '', $decryptedPassword);
                 }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'password' => $decryptedPassword,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Şifre çözülemedi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Kullanıcıya özel salt al (Password model'den alınan method)
     */
    private function getUserSalt(Password $password)
    {
        $user = $password->user;

        if (!$user) {
            return 'default_salt_' . $password->user_id;
        }

        // User'ın password_salt'ı varsa onu kullan, yoksa ID bazlı salt oluştur
        return $user->password_salt ?: ('user_salt_' . $password->user_id . '_' . substr(md5($user->email), 0, 8));
    }

    /**
     * Rastgele şifre üret
     */
    public function generatePassword(Request $request): JsonResponse
    {
        $length = $request->get('length', 12);
        $includeSymbols = $request->get('include_symbols', true);
        $includeNumbers = $request->get('include_numbers', true);
        $includeUppercase = $request->get('include_uppercase', true);
        $includeLowercase = $request->get('include_lowercase', true);

        $characters = '';
        if ($includeLowercase) $characters .= 'abcdefghijklmnopqrstuvwxyz';
        if ($includeUppercase) $characters .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        if ($includeNumbers) $characters .= '0123456789';
        if ($includeSymbols) $characters .= '!@#$%^&*()_+-=[]{}|;:,.<>?';

        if (empty($characters)) {
            return response()->json([
                'success' => false,
                'message' => 'En az bir karakter türü seçilmelidir'
            ], 422);
        }

        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'password' => $password
            ]
        ]);
    }
}
