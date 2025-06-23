<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\SecurityService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SecurityController extends Controller
{
    protected $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    /**
     * Kullanıcının audit loglarını getir
     */
    public function auditLogs(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->get('per_page', 20);
        $action = $request->get('action'); // Belirli bir action filtrelemek için

        $query = AuditLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        if ($action) {
            $query->where('action', $action);
        }

        $auditLogs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $auditLogs
        ]);
    }

    /**
     * Şüpheli aktiviteleri getir (sadece kendi aktiviteleri)
     */
    public function suspiciousActivities(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->get('per_page', 10);

        $suspiciousLogs = AuditLog::where('user_id', $user->id)
            ->where(function($query) {
                $query->where('is_suspicious', true)
                      ->orWhere('severity', 'high')
                      ->orWhere('severity', 'critical');
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $suspiciousLogs,
            'message' => $suspiciousLogs->total() > 0 ?
                'Şüpheli aktiviteler tespit edildi. Hesabınızın güvenliğini kontrol edin.' :
                'Şüpheli aktivite tespit edilmedi.'
        ]);
    }

    /**
     * Kullanıcının giriş geçmişini getir
     */
    public function loginHistory(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->get('per_page', 15);

        $loginHistory = AuditLog::where('user_id', $user->id)
            ->whereIn('action', ['login', 'logout'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        // Kullanıcının stored login history'sini de ekle
        $userLoginHistory = $user->login_history ?? [];

        return response()->json([
            'success' => true,
            'data' => [
                'audit_logs' => $loginHistory,
                'recent_logins' => $userLoginHistory,
                'current_session' => [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'login_time' => $user->last_login_at,
                ]
            ]
        ]);
    }

    /**
     * Güvenlik olayı rapor et
     */
    public function reportIncident(Request $request): JsonResponse
    {
        $request->validate([
            'incident_type' => 'required|string|in:unauthorized_access,suspicious_activity,data_breach,phishing,other',
            'description' => 'required|string|max:1000',
            'severity' => 'sometimes|string|in:low,medium,high,critical'
        ]);

        $user = $request->user();

        // Olay raporunu audit log olarak kaydet
        AuditLog::logAction(
            'incident_reported',
            $user->id,
            $request->ip(),
            $request->userAgent(),
            [
                'incident_type' => $request->incident_type,
                'description' => $request->description,
                'reported_by_user' => true
            ],
            $request->get('severity', 'medium'),
            true
        );

        return response()->json([
            'success' => true,
            'message' => 'Güvenlik olayı başarıyla raporlandı. Ekibimiz inceleme yapacaktır.'
        ]);
    }

    /**
     * Güvenlik istatistikleri
     */
    public function securityStats(Request $request): JsonResponse
    {
        $user = $request->user();

        // Son 30 gün içindeki istatistikler
        $thirtyDaysAgo = now()->subDays(30);

        $stats = [
            'login_count' => AuditLog::where('user_id', $user->id)
                ->where('action', 'login')
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->count(),

            'password_access_count' => AuditLog::where('user_id', $user->id)
                ->where('action', 'like', 'password_%')
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->count(),

            'suspicious_activities' => AuditLog::where('user_id', $user->id)
                ->where('is_suspicious', true)
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->count(),

            'unique_ips' => AuditLog::where('user_id', $user->id)
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->distinct('ip_address')
                ->count(),

            'last_login' => $user->last_login_at,
            'last_login_ip' => $user->last_login_ip,
            'failed_attempts' => $user->failed_login_attempts,
            'account_locked' => $user->isLocked(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
