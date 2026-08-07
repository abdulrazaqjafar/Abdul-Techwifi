<x-layouts.app title="Edit Package" subtitle="Update package details for {{ $package->name }}">
    <div class="mx-auto max-w-2xl">
        <div class="card p-6">
            <form method="POST" action="{{ route('packages.update', $package) }}" class="space-y-6" @submit="$dispatch('form-submitting')">
                @csrf
                @method('PUT')

                <div>
                    <label for="name" class="label">Package Name <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $package->name) }}" required class="input-field">
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="duration_hours" class="label">Duration (Hours)</label>
                        <input type="number" id="duration_hours" name="duration_hours" value="{{ old('duration_hours', $package->duration_hours) }}" min="0" max="8760" class="input-field">
                    </div>
                    <div>
                        <label for="duration_days" class="label">Duration (Days)</label>
                        <input type="number" id="duration_days" name="duration_days" value="{{ old('duration_days', $package->duration_days) }}" min="0" max="365" class="input-field">
                    </div>
                </div>

                <div>
                    <label for="price" class="label">Price <span class="text-red-500">*</span></label>
                    <input type="number" id="price" name="price" value="{{ old('price', $package->price) }}" required min="0" step="0.01" class="input-field">
                </div>

                <div>
                    <label for="profile_name" class="label">MikroTik Profile Name <span class="text-red-500">*</span></label>
                    <input type="text" id="profile_name" name="profile_name" value="{{ old('profile_name', $package->profile_name) }}" required class="input-field">
                </div>

                <div>
                    <label for="description" class="label">Description</label>
                    <textarea id="description" name="description" rows="3" class="input-field">{{ old('description', $package->description) }}</textarea>
                </div>

                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $package->is_active) ? 'checked' : '' }} class="h-4 w-4 rounded border-border text-primary-600 focus:ring-primary-500">
                        <span class="text-sm font-medium text-text dark:text-slate-200">Active</span>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-border pt-6 dark:border-slate-700">
                    <x-button href="{{ route('packages.index') }}" variant="outline">Cancel</x-button>
                    <x-button type="submit" variant="primary">Update Package</x-button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
