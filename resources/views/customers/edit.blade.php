<x-layouts.app title="Edit Customer" subtitle="Update customer details for {{ $customer->name }}">
    <div class="mx-auto max-w-2xl">
        <div class="card p-6">
            <form method="POST" action="{{ route('customers.update', $customer) }}" class="space-y-6" @submit="$dispatch('form-submitting')">
                @csrf
                @method('PUT')

                <div>
                    <label for="name" class="label">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $customer->name) }}" required class="input-field">
                </div>

                <div>
                    <label for="phone" class="label">Phone Number</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone', $customer->phone) }}" class="input-field">
                </div>

                <div>
                    <label for="email" class="label">Email Address</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $customer->email) }}" class="input-field">
                </div>

                <div>
                    <label for="notes" class="label">Notes</label>
                    <textarea id="notes" name="notes" rows="3" class="input-field">{{ old('notes', $customer->notes) }}</textarea>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-border pt-6 dark:border-slate-700">
                    <x-button href="{{ route('customers.index') }}" variant="outline">Cancel</x-button>
                    <x-button type="submit" variant="primary">Update Customer</x-button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
