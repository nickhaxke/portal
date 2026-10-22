<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    /**
     * Record an administrative mutation audit log.
     * Supports both ($action, $model, $oldValues, $newValues) and ($action, $model, $desc, $newValues).
     */
    public static function log(
        string $action,
        ?Model $model = null,
        string|array|null $oldValuesOrDesc = null,
        ?array $newValues = null,
        ?string $description = null
    ): AuditLog {
        $old = null;
        $desc = $description;

        if (is_string($oldValuesOrDesc)) {
            $desc = $oldValuesOrDesc;
        } elseif (is_array($oldValuesOrDesc)) {
            $old = $oldValuesOrDesc;
        }

        return AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => $model ? get_class($model) : 'System',
            'auditable_id' => $model ? $model->getKey() : null,
            'description' => $desc,
            'old_values' => $old,
            'new_values' => $newValues,
            'ip_address' => Request::ip() ?? '127.0.0.1',
            'user_agent' => Request::userAgent() ?? 'Internal',
            'created_at' => now(),
        ]);
    }
}
