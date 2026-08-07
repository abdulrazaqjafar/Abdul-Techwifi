<x-layouts.app title="Sajili Mfanyakazi" subtitle="Mpe mtu access ya kuuza voucher kwa cash">
    <div class="mx-auto max-w-2xl">
        <div class="mb-6 rounded-xl border border-primary-200 bg-primary-50 p-4 dark:border-primary-800 dark:bg-primary-900/20">
            <p class="text-sm text-primary-800 dark:text-primary-200">
                Mfanyakazi atapata panel maalum ya cashier pekee — hawezi kufikia router, settings, au reports.
            </p>
        </div>

        <div class="card p-6">
            <form method="POST" action="{{ route('staff.store') }}" class="space-y-6" @submit="$dispatch('form-submitting')">
                @csrf

                <div>
                    <label for="name" class="label">Jina Kamili <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required class="input-field" placeholder="Mfano: Asha Mohamed">
                </div>

                <div>
                    <label for="email" class="label">Email (Login) <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required class="input-field" placeholder="cashier@techsphere.com">
                </div>

                <div>
                    <label for="password" class="label">Nenosiri <span class="text-red-500">*</span></label>
                    <input type="password" id="password" name="password" required class="input-field" placeholder="Angalau herufi 8">
                </div>

                <div>
                    <label for="password_confirmation" class="label">Thibitisha Nenosiri <span class="text-red-500">*</span></label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required class="input-field">
                </div>

                <div class="flex items-center gap-3">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', true)) class="rounded border-border text-primary-600 focus:ring-primary-500">
                    <label for="is_active" class="text-sm dark:text-slate-300">Akaunti iwe hai (aweze kuingia mara moja)</label>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-border pt-6 dark:border-slate-700">
                    <x-button href="{{ route('staff.index') }}" variant="outline">Ghairi</x-button>
                    <x-button type="submit" variant="primary">Sajili Mfanyakazi</x-button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
