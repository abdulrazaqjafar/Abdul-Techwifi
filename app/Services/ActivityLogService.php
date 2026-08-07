<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    public function log(string $action, string $description, ?int $adminId = null): ActivityLog
    {
        $adminId ??= Auth::id();

        return ActivityLog::query()->create([
            'admin_id' => $adminId,
            'action' => $action,
            'description' => $description,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
