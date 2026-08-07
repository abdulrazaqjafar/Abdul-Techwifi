<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $this->activityLogService->log(
            'auth.login',
            'Admin logged in successfully.',
            Auth::id()
        );

        return redirect()->intended(route(Auth::user()->homeRoute()));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $adminId = Auth::id();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($adminId) {
            $this->activityLogService->log(
                'auth.logout',
                'Admin logged out.',
                $adminId
            );
        }

        return redirect()->route('login');
    }

    public function createForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function storeForgotPassword(ForgotPasswordRequest $request): RedirectResponse
    {
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        return back()->withErrors(['email' => __($status)]);
    }
}
