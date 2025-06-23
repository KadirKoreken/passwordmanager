<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SecurityService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }
    /**
     * Kullanıcı girişi
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $email = $request->email;
        $password = $request->password;
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        // Kullanıcıyı bul
        $user = User::where('email', $email)->first();

        // Kullanıcı yoksa hata kaydet
        if (!$user) {
            $this->securityService->recordFailedLogin($email, $ipAddress, $userAgent);
            return response()->json([
                'success' => false,
                'message' => 'E-posta veya şifre hatalı'
            ], 401);
        }

        // Hesap kilitli mi kontrol et
        if ($this->securityService->isAccountLocked($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Hesabınız geçici olarak kilitlenmiştir. Lütfen daha sonra tekrar deneyin.',
                'locked_until' => $user->locked_until
            ], 423);
        }

        // Şüpheli giriş kontrolü
        $suspiciousCheck = $this->securityService->detectSuspiciousLogin($user, $ipAddress, $userAgent);

        // Şifre kontrolü
        if (!Auth::attempt(['email' => $email, 'password' => $password])) {
            $this->securityService->recordFailedLogin($email, $ipAddress, $userAgent);
            return response()->json([
                'success' => false,
                'message' => 'E-posta veya şifre hatalı'
            ], 401);
        }

        // Başarılı giriş - güvenlik bilgilerini güncelle
        $this->securityService->updateLoginInfo($user, $ipAddress, $userAgent);

        // Token oluştur
        $token = $user->createToken('API Token', ['*'], now()->addHours(24))->plainTextToken;

        $response = [
            'success' => true,
            'message' => 'Giriş başarılı',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'last_login_at' => $user->last_login_at,
                    'last_login_ip' => $user->last_login_ip,
                ]
            ]
        ];

        // Şüpheli giriş uyarısı ekle
        if ($suspiciousCheck['is_suspicious']) {
            $response['warning'] = [
                'message' => 'Şüpheli giriş aktivitesi tespit edildi',
                'reasons' => $suspiciousCheck['reasons']
            ];
        }

        return response()->json($response);
    }

    /**
     * Kullanıcı kaydı
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $ipAddress = $request->ip();

        // Güvenli şifre hash'i oluştur
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Kayıt IP'sini kaydet
        $this->securityService->recordRegistrationIP($user, $ipAddress);

        $token = $user->createToken('API Token', ['*'], now()->addHours(24))->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Kayıt başarılı',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'registration_ip' => $user->registration_ip,
                ]
            ]
        ], 201);
    }

    /**
     * Kullanıcı çıkışı
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        // Logout audit log
        $this->securityService->logPasswordAccess(null, 'logout', [
            'user_id' => $user->id,
            'ip_address' => $request->ip()
        ]);

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Çıkış başarılı'
        ]);
    }

    /**
     * Kullanıcı bilgileri
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'passwords_count' => $user->passwords()->count(),
                    'last_login_at' => $user->last_login_at,
                    'last_login_ip' => $user->last_login_ip,
                    'registration_ip' => $user->registration_ip,
                    'failed_login_attempts' => $user->failed_login_attempts,
                    'last_login_info' => $user->last_login_info,
                    'two_factor_enabled' => $user->two_factor_enabled,
                ]
            ]
        ]);
    }
}
