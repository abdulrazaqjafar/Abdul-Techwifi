<x-layouts.app title="Hariri Mfanyakazi" subtitle="{{ $staff->name }}">
    <div class="mx-auto max-w-2xl">
        <div class="card p-6">
            <form method="POST" action="{{ route('staff.update', $staff) }}" class="space-y-6" @submit="$dispatch('form-submitting')">
                @csrf
                @method('PUT')

                <div>
                    <label for="name" class="label">Jina Kamili <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $staff->name) }}" required class="input-field">
                </div>

                <div>
                    <label for="email" class="label">Email (Login) <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" value="{{ old('email', $staff->email) }}" required class="input-field">
                </div>

                <div>
                    <label for="password" class="label">Nenosiri Jipya</label>
                    <input type="password" id="password" name="password" class="input-field" placeholder="Acha tupu usipotaka kubadilisha">
                </div>

                <div>
                    <label for="password_confirmation" class="label">Thibitisha Nenosiri</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="input-field">
                </div>

                <div class="flex items-center gap-3">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $staff->is_active)) class="rounded border-border text-primary-600 focus:ring-primary-500">
                    <label for="is_active" class="text-sm dark:text-slate-300">Akaunti hai (ruhusu kuingia)</label>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-border pt-6 dark:border-slate-700">
                    <x-button href="{{ route('staff.index') }}" variant="outline">Ghairi</x-button>
                    <x-button type="submit" variant="primary">Hifadhi Mabadiliko</x-button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
