<x-layouts.app title="Add Customer" subtitle="Register a new customer">
    <div class="mx-auto max-w-2xl">
        <div class="card p-6">
            <form method="POST" action="{{ route('customers.store') }}" class="space-y-6" @submit="$dispatch('form-submitting')">
                @csrf

                <div>
                    <label for="name" class="label">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required class="input-field" placeholder="Customer full name">
                </div>

                <div>
                    <label for="phone" class="label">Phone Number</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone') }}" class="input-field" placeholder="+1234567890">
                </div>

                <div>
                    <label for="email" class="label">Email Address</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" class="input-field" placeholder="customer@email.com">
                </div>

                <div>
                    <label for="notes" class="label">Notes</label>
                    <textarea id="notes" name="notes" rows="3" class="input-field" placeholder="Optional notes about this customer">{{ old('notes') }}</textarea>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-border pt-6 dark:border-slate-700">
                    <x-button href="{{ route('customers.index') }}" variant="outline">Cancel</x-button>
                    <x-button type="submit" variant="primary">Create Customer</x-button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
