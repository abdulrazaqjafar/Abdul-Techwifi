@php
    $unusedVouchers = \App\Models\Voucher::query()
        ->with('package')
        ->where('status', \App\Models\Voucher::STATUS_UNUSED)
        ->latest()
        ->get();
    $customers = \App\Models\Customer::query()->orderBy('name')->get();
    $paymentMethods = ['cash' => 'Cash', 'mobile_money' => 'Mobile Money', 'card' => 'Card', 'bank_transfer' => 'Bank Transfer', 'other' => 'Other'];
@endphp

<x-layouts.app title="Record Sale" subtitle="Record a new voucher sale">
    <div class="mx-auto max-w-2xl">
        <div class="card p-6">
            <form method="POST" action="{{ route('sales.store') }}" class="space-y-6" @submit="$dispatch('form-submitting')">
                @csrf

                <div>
                    <label for="voucher_id" class="label">Voucher <span class="text-red-500">*</span></label>
                    <select id="voucher_id" name="voucher_id" required class="input-field">
                        <option value="">Select an unused voucher</option>
                        @foreach ($unusedVouchers as $voucher)
                            <option value="{{ $voucher->id }}" data-price="{{ $voucher->package?->price }}" @selected(old('voucher_id') == $voucher->id)>
                                {{ $voucher->code }} — {{ $voucher->package?->name }} ({{ number_format($voucher->package?->price ?? 0, 2) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="customer_id" class="label">Customer (Optional)</label>
                    <select id="customer_id" name="customer_id" class="input-field">
                        <option value="">Walk-in customer</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="amount" class="label">Amount (Optional — defaults to package price)</label>
                    <input type="number" id="amount" name="amount" value="{{ old('amount') }}" min="0" step="0.01" class="input-field" placeholder="Leave blank for package price">
                </div>

                <div>
                    <label for="payment_method" class="label">Payment Method <span class="text-red-500">*</span></label>
                    <select id="payment_method" name="payment_method" required class="input-field">
                        @foreach ($paymentMethods as $value => $label)
                            <option value="{{ $value }}" @selected(old('payment_method') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="sold_at" class="label">Sale Date (Optional)</label>
                    <input type="datetime-local" id="sold_at" name="sold_at" value="{{ old('sold_at') }}" class="input-field">
                </div>

                <div>
                    <label for="notes" class="label">Notes</label>
                    <textarea id="notes" name="notes" rows="3" class="input-field" placeholder="Optional sale notes">{{ old('notes') }}</textarea>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-border pt-6 dark:border-slate-700">
                    <x-button href="{{ route('sales.index') }}" variant="outline">Cancel</x-button>
                    <x-button type="submit" variant="primary">Record Sale</x-button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
