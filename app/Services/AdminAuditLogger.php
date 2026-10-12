<?php

namespace App\Services;

use App\Models\AdministrationAuditLog;
use Illuminate\Database\Eloquent\Model;

class AdminAuditLogger
{
    public function record(string $action, Model $subject, array $changes = []): void
    {
        AdministrationAuditLog::create([
            'actor_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'changes' => $changes,
        ]);
    }
}
