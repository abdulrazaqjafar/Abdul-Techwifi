<x-layouts.guest title="Login">
    <h2 class="mb-6 text-xl font-bold text-secondary-800 dark:text-white">Sign in to your account</h2>

    <form method="POST" action="{{ route('login') }}" class="space-y-5" @submit="$dispatch('form-submitting')">
        @csrf

        <div>
            <label for="email" class="label">Email address</label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                class="input-field"
                placeholder="admin@techsphere.com"
            >
        </div>

        <div>
            <label for="password" class="label">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                required
                autocomplete="current-password"
                class="input-field"
                placeholder="Enter your password"
            >
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2">
                <input
                    type="checkbox"
                    name="remember"
                    value="1"
                    {{ old('remember') ? 'checked' : '' }}
                    class="h-4 w-4 rounded border-border text-primary-600 focus:ring-primary-500"
                >
                <span class="text-sm text-text-muted dark:text-slate-400">Remember me</span>
            </label>
            <a href="{{ route('password.request') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">
                Forgot password?
            </a>
        </div>

        <x-button type="submit" variant="primary" class="w-full">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3 3-3" />
            </svg>
            Sign in
        </x-button>
    </form>
</x-layouts.guest>
