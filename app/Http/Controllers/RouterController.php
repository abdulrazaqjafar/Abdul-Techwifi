<?php

namespace App\Http\Controllers;

use App\Models\RouterSetting;
use App\Services\ActivityLogService;
use App\Services\MikrotikRouterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class RouterController extends Controller
{
    public function __construct(
        protected MikrotikRouterService $routerService,
        protected ActivityLogService $activityLogService
    ) {}

    public function index(): View
    {
        return view('router.index', [
            'router' => RouterSetting::resolve(),
        ]);
    }

    public function connect(): RedirectResponse|JsonResponse
    {
        try {
            $this->routerService->connect();

            $this->activityLogService->log(
                'router.connected',
                'Connected to MikroTik router.'
            );

            if (request()->expectsJson()) {
                return response()->json(['message' => 'Connected to router successfully.']);
            }

            return back()->with('success', 'Connected to router successfully.');
        } catch (RuntimeException $exception) {
            if (request()->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()->with('error', $exception->getMessage());
        }
    }

    public function disconnect(): RedirectResponse|JsonResponse
    {
        $this->routerService->disconnect();

        $this->activityLogService->log(
            'router.disconnected',
            'Disconnected from MikroTik router.'
        );

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Disconnected from router successfully.']);
        }

        return back()->with('success', 'Disconnected from router successfully.');
    }

    public function test(): RedirectResponse|JsonResponse
    {
        $result = $this->routerService->testConnection();

        if (request()->expectsJson()) {
            return response()->json([
                'connected' => $result['ok'],
                'message' => $result['message'],
            ], $result['ok'] ? 200 : 422);
        }

        return back()->with(
            $result['ok'] ? 'success' : 'error',
            $result['message']
        );
    }

    public function onlineUsers(): View|JsonResponse
    {
        if (! $this->routerService->isConfigured()) {
            $message = 'Router credentials are not configured. Go to Settings first.';

            if (request()->expectsJson()) {
                return response()->json(['message' => $message, 'users' => []], 422);
            }

            return back()->with('error', $message);
        }

        try {
            $users = $this->routerService->getOnlineUsers();

            if (request()->expectsJson()) {
                return response()->json(['users' => $users]);
            }

            return view('router.online-users', compact('users'));
        } catch (RuntimeException $exception) {
            if (request()->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()->with('error', $exception->getMessage());
        }
    }

    public function offlineUsers(): View|JsonResponse
    {
        if (! $this->routerService->isConfigured()) {
            $message = 'Router credentials are not configured. Go to Settings first.';

            if (request()->expectsJson()) {
                return response()->json(['message' => $message, 'users' => []], 422);
            }

            return back()->with('error', $message);
        }

        try {
            $users = $this->routerService->getOfflineUsers();

            if (request()->expectsJson()) {
                return response()->json(['users' => $users]);
            }

            return view('router.offline-users', compact('users'));
        } catch (RuntimeException $exception) {
            if (request()->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()->with('error', $exception->getMessage());
        }
    }

    public function disconnectUser(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255'],
        ]);

        try {
            $disconnected = $this->routerService->disconnectUser($validated['username']);

            if (! $disconnected) {
                $message = 'No active session found for the specified user.';

                if (request()->expectsJson()) {
                    return response()->json(['message' => $message], 404);
                }

                return back()->with('error', $message);
            }

            $this->activityLogService->log(
                'router.user_disconnected',
                "Disconnected hotspot user {$validated['username']}."
            );

            if (request()->expectsJson()) {
                return response()->json(['message' => 'User disconnected successfully.']);
            }

            return back()->with('success', 'User disconnected successfully.');
        } catch (RuntimeException $exception) {
            if (request()->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()->with('error', $exception->getMessage());
        }
    }

    public function sync(): RedirectResponse|JsonResponse
    {
        try {
            $activeSynced = $this->routerService->syncActiveUsers();
            $expiredSynced = $this->routerService->syncExpiredUsers();

            $this->activityLogService->log(
                'router.synced',
                "Synced router users ({$activeSynced} activated, {$expiredSynced} expired)."
            );

            $message = "Sync completed. {$activeSynced} activated, {$expiredSynced} expired.";

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'active_synced' => $activeSynced,
                    'expired_synced' => $expiredSynced,
                ]);
            }

            return back()->with('success', $message);
        } catch (RuntimeException $exception) {
            if (request()->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()->with('error', $exception->getMessage());
        }
    }
}
