<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Admin;
use App\Models\AdminActionLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AdminActionLogger
{
    private ?bool $supportsCandidateActor = null;

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function log(
        string $action,
        ?Admin $admin = null,
        ?User $candidate = null,
        array $metadata = [],
        ?Request $request = null
    ): void
    {
        if (! app(LogSettingsService::class)->isEnabled()) {
            return;
        }

        $request = $request ?? request();

        $baseMeta = [];

        if ($request instanceof Request) {
            $baseMeta = [
                'ip' => $request->ip(),
                'method' => $request->method(),
                'path' => $request->path(),
                'route' => optional($request->route())->getName(),
                'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
            ];
        }

        $payload = [
            'admin_id' => $admin?->id,
            'action' => Str::limit($action, 100, ''),
            'metadata' => array_filter(array_merge($baseMeta, $metadata), static fn ($value) => $value !== null),
            'created_at' => now(),
        ];

        if ($this->supportsCandidateActor()) {
            $payload['user_id'] = $candidate?->id;
        }

        AdminActionLog::query()->create($payload);
    }

    private function supportsCandidateActor(): bool
    {
        if ($this->supportsCandidateActor !== null) {
            return $this->supportsCandidateActor;
        }

        return $this->supportsCandidateActor = Schema::hasColumn('admin_action_logs', 'user_id');
    }
}
