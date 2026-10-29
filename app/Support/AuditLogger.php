<?php

namespace App\Support;

use App\Modules\Fondasi\Enums\AuditAction;
use App\Modules\Fondasi\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Satu-satunya penulis sys_audit_logs (docs/21). Jangan menaruh nilai 🔒 di $changes.
 */
class AuditLogger
{
    public function __construct(private readonly Request $request) {}

    /**
     * @param  array<string, mixed>|null  $changes
     */
    public function log(AuditAction $action, Model $auditable, ?string $reason = null, ?array $changes = null, ?int $userId = null): AuditLog
    {
        return AuditLog::query()->create([
            'user_id' => $userId ?? $this->request->user()?->getAuthIdentifier(),
            'action' => $action,
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => $auditable->getKey(),
            'reason' => $reason,
            'changes' => $changes,
            'ip_address' => $this->request->ip() ?? '0.0.0.0',
        ]);
    }
}
