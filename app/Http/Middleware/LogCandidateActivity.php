<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\AdminActionLogger;
use App\Services\LogSettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogCandidateActivity
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);
        $response = $next($request);

        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $response;
        }

        if (! app(LogSettingsService::class)->isEnabled()) {
            return $response;
        }

        /** @var User|null $candidate */
        $candidate = $request->user();
        $routeName = optional($request->route())->getName() ?? 'candidate.unknown';

        app(AdminActionLogger::class)->log(
            action: $routeName,
            candidate: $candidate,
            metadata: [
                'actor_type' => 'candidate',
                'status_code' => $response->getStatusCode(),
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'query' => $request->query(),
            ],
            request: $request
        );

        return $response;
    }
}
