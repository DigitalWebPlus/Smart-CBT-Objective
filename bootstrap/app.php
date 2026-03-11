<?php

use App\Http\Middleware\Authenticate as AppAuthenticate;
use App\Http\Middleware\EnsureCandidateIsActive;
use App\Http\Middleware\LogAdminActivity;
use App\Http\Middleware\LogCandidateActivity;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth' => AppAuthenticate::class,
            'candidate.active' => EnsureCandidateIsActive::class,
            'log.admin.activity' => LogAdminActivity::class,
            'log.candidate.activity' => LogCandidateActivity::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
