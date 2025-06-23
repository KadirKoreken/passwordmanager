<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'ip_address',
        'user_agent',
        'details',
        'severity',
        'is_suspicious',
        'country',
        'city',
    ];

    protected $casts = [
        'details' => 'array',
        'is_suspicious' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * User ile ilişki
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log kaydı oluştur
     */
    public static function logAction(
        $action,
        $userId = null,
        $ipAddress = null,
        $userAgent = null,
        array $details = [],
        $severity = 'low',
        $isSuspicious = false
    ) {
        return self::create([
            'user_id' => $userId,
            'action' => $action,
            'ip_address' => $ipAddress ?: request()->ip(),
            'user_agent' => $userAgent ?: request()->userAgent(),
            'details' => $details,
            'severity' => $severity,
            'is_suspicious' => $isSuspicious,
        ]);
    }

    /**
     * Şüpheli aktiviteleri getir
     */
    public static function getSuspiciousActivities($limit = 50)
    {
        return self::where('is_suspicious', true)
            ->orWhere('severity', 'high')
            ->orWhere('severity', 'critical')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Kullanıcının son aktivitelerini getir
     */
    public static function getUserRecentActivity($userId, $limit = 10)
    {
        return self::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
