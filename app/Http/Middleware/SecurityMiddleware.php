<?php

namespace App\Http\Middleware;

use App\Services\SecurityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityMiddleware
{
    protected $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // API isteği güvenlik kontrolü
        if (!$this->securityService->validateAPIRequest($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Rate limit aşıldı. Lütfen daha sonra tekrar deneyin.',
                'error_code' => 'RATE_LIMIT_EXCEEDED'
            ], 429);
        }

        // Kullanıcı giriş yapmışsa ek kontroller
        if ($request->user()) {
            $user = $request->user();

            // Hesap kilitli mi kontrol et
            if ($this->securityService->isAccountLocked($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hesabınız güvenlik nedeniyle geçici olarak kilitlenmiştir.',
                    'error_code' => 'ACCOUNT_LOCKED'
                ], 423);
            }

            // IP değişikliği kontrolü (şüpheli aktivite)
            if ($user->last_login_ip && $user->last_login_ip !== $request->ip()) {
                // Farklı IP'den gelen istekleri logla
                \App\Models\AuditLog::logAction(
                    'ip_change_detected',
                    $user->id,
                    $request->ip(),
                    $request->userAgent(),
                    [
                        'old_ip' => $user->last_login_ip,
                        'new_ip' => $request->ip(),
                        'endpoint' => $request->path()
                    ],
                    'medium',
                    true
                );
            }
        }

        // Şüpheli user agent kontrolü
        $userAgent = $request->userAgent();
        if ($this->isSuspiciousUserAgent($userAgent)) {
            // AuditLog::logAction kullan
            \App\Models\AuditLog::logAction(
                'suspicious_user_agent',
                $request->user()?->id,
                $request->ip(),
                $userAgent,
                ['user_agent' => $userAgent],
                'medium',
                true
            );
        }

        return $next($request);
    }

    /**
     * Şüpheli user agent kontrolü
     */
    private function isSuspiciousUserAgent($userAgent)
    {
        if (empty($userAgent)) {
            return true;
        }

        // Yaygın bot/scraper patternleri
        $suspiciousPatterns = [
            'bot',
            'crawler',
            'spider',
            'scraper',
            'curl',
            'wget',
            'python',
            'postman',
            'insomnia'
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }
}
