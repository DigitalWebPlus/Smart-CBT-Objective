<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminActionLog;
use App\Models\User;
use App\Services\LogSettingsService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AdminActionLogController extends Controller
{
    public function index(Request $request): View
    {
        $supportsCandidateLogs = Schema::hasColumn('admin_action_logs', 'user_id');

        $action = trim((string) $request->string('action'));
        $adminId = $request->filled('admin_id') ? (int) $request->integer('admin_id') : null;
        $candidateId = $supportsCandidateLogs && $request->filled('candidate_id')
            ? (int) $request->integer('candidate_id')
            : null;
        $actorType = trim((string) $request->string('actor_type'));
        $from = $request->string('from')->toString();
        $to = $request->string('to')->toString();
        $search = trim((string) $request->string('search'));

        $logsQuery = AdminActionLog::query()
            ->with(['admin:id,name,email'])
            ->latest('created_at');

        if ($supportsCandidateLogs) {
            $logsQuery->with(['candidate:id,name,email,registration_number']);
        }

        if ($action !== '') {
            $logsQuery->where('action', 'like', '%' . $action . '%');
        }

        if ($adminId) {
            $logsQuery->where('admin_id', $adminId);
        }

        if ($supportsCandidateLogs && $candidateId) {
            $logsQuery->where('user_id', $candidateId);
        }

        if ($actorType === 'admin') {
            $logsQuery->whereNotNull('admin_id');
        } elseif ($supportsCandidateLogs && $actorType === 'candidate') {
            $logsQuery->whereNotNull('user_id');
        } elseif ($actorType === 'system') {
            $logsQuery->whereNull('admin_id');

            if ($supportsCandidateLogs) {
                $logsQuery->whereNull('user_id');
            }
        }

        if ($from !== '') {
            $logsQuery->whereDate('created_at', '>=', $from);
        }

        if ($to !== '') {
            $logsQuery->whereDate('created_at', '<=', $to);
        }

        if ($search !== '') {
            $logsQuery->where(function ($query) use ($search): void {
                $query->where('action', 'like', '%' . $search . '%')
                    ->orWhere('metadata', 'like', '%' . $search . '%');
            });
        }

        $logs = $logsQuery->paginate(25)->withQueryString();

        $admins = Admin::query()->orderBy('name')->get(['id', 'name', 'email']);
        $candidates = $supportsCandidateLogs
            ? User::query()->orderBy('name')->limit(300)->get(['id', 'name', 'email', 'registration_number'])
            : collect();

        $stats = [
            'today' => AdminActionLog::query()->whereDate('created_at', today())->count(),
            'last_7_days' => AdminActionLog::query()->where('created_at', '>=', now()->subDays(7))->count(),
            'failed_logins_today' => AdminActionLog::query()
                ->whereIn('action', ['admin.auth.login.failed', 'candidate.auth.login.failed'])
                ->whereDate('created_at', today())
                ->count(),
            'candidate_actions_today' => $supportsCandidateLogs
                ? AdminActionLog::query()
                    ->whereDate('created_at', today())
                    ->whereNotNull('user_id')
                    ->count()
                : 0,
            'critical_actions_today' => AdminActionLog::query()
                ->whereDate('created_at', today())
                ->where(function ($query): void {
                    $query->where('action', 'like', '%.destroy')
                        ->orWhere('action', 'like', '%.delete%')
                        ->orWhere('action', 'like', '%.close')
                        ->orWhere('action', 'like', '%.status%');
                })->count(),
        ];

        return view('admin.logs.index', [
            'logs' => $logs,
            'admins' => $admins,
            'candidates' => $candidates,
            'supportsCandidateLogs' => $supportsCandidateLogs,
            'logsEnabled' => app(LogSettingsService::class)->isEnabled(),
            'stats' => $stats,
            'filters' => [
                'action' => $action,
                'admin_id' => $adminId,
                'candidate_id' => $candidateId,
                'actor_type' => in_array($actorType, ['admin', 'candidate', 'system'], true)
                    ? ($supportsCandidateLogs ? $actorType : ($actorType === 'candidate' ? '' : $actorType))
                    : '',
                'from' => $from,
                'to' => $to,
                'search' => $search,
            ],
        ]);
    }

    public function show(AdminActionLog $log): View
    {
        $supportsCandidateLogs = Schema::hasColumn('admin_action_logs', 'user_id');

        $log->load(['admin:id,name,email']);

        if ($supportsCandidateLogs) {
            $log->load(['candidate:id,name,email,registration_number']);
        }

        return view('admin.logs.show', [
            'log' => $log,
            'metadata' => collect($log->metadata ?? [])->sortKeys()->all(),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $deletedCount = AdminActionLog::query()->count();
        AdminActionLog::query()->delete();

        NotificationService::SUCCESS(sprintf('Logs reset successfully. %d record(s) removed.', $deletedCount));

        return redirect()->route('admin.logs.index');
    }

    public function toggle(Request $request): RedirectResponse
    {
        $enabled = app(LogSettingsService::class)->toggle();

        NotificationService::SUCCESS($enabled ? 'Logging has been enabled.' : 'Logging has been disabled.');

        return redirect()->route('admin.logs.index');
    }
}
