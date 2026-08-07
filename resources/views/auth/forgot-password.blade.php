<x-layouts.guest title="Forgot Password">
    <h2 class="mb-2 text-xl font-bold text-secondary-800 dark:text-white">Reset your password</h2>
    <p class="mb-6 text-sm text-text-muted dark:text-slate-400">
        Enter your email address and we'll send you a password reset link.
    </p>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5" @submit="$dispatch('form-submitting')">
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

        <x-button type="submit" variant="primary" class="w-full">
            Send reset link
        </x-button>

        <div class="text-center">
            <a href="{{ route('login') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">
                Back to login
            </a>
        </div>
    </form>
</x-layouts.guest>
