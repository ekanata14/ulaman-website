<?php

namespace App\Actions\Concerns;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait InteractsWithAuditLog
{
    /**
     * @param  array<string, mixed>|null  $lama
     * @param  array<string, mixed>|null  $baru
     */
    protected function recordAudit(?User $actor, string $aksi, Model $auditable, ?array $lama = null, ?array $baru = null): AuditLog
    {
        return AuditLog::create([
            'user_id' => $actor?->getKey(),
            'aksi' => $aksi,
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => $auditable->getKey(),
            'data_lama' => $lama,
            'data_baru' => $baru,
            'created_at' => now(),
        ]);
    }
}
