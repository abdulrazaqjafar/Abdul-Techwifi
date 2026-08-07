<?php

use App\Http\Middleware\EnsureAdminRole;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            SetLocale::class,
        ]);

        $middleware->alias([
            'admin' => EnsureAdminRole::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('offers:send-monthly')->dailyAt('09:00');
        $schedule->command('customers:sync-service-status')->everyFiveMinutes();
        // Catches up on vouchers redeemed on the router while this server was
        // off, and pushes any that were created while the router was down.
        $schedule->command('vouchers:sync-router')->everyTenMinutes()->withoutOverlapping();
    })->create();
