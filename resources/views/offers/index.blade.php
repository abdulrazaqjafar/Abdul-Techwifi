<x-layouts.app title="Monthly Offers" subtitle="Offers za mwezi na SMS kwa wateja">
    <x-slot:header>
        <div class="flex w-full flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Monthly Offers</h1>
                <p class="page-subtitle">Hutumwa kiotomatiki siku ya pili ya mwisho wa kila mwezi (mfano: {{ $nextRunDay }})</p>
            </div>
            <form method="POST" action="{{ route('offers.dispatch') }}">
                @csrf
                <x-button type="submit" variant="primary" size="sm">Tuma Offers Sasa (Test)</x-button>
            </form>
        </div>
    </x-slot:header>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-border bg-surface-muted text-left text-xs uppercase text-text-muted dark:border-slate-700 dark:bg-slate-800">
                        <th class="px-4 py-3">Mteja</th>
                        <th class="px-4 py-3">Simu</th>
                        <th class="px-4 py-3">Offer Code</th>
                        <th class="px-4 py-3">Punguzo</th>
                        <th class="px-4 py-3">Mwezi</th>
                        <th class="px-4 py-3">SMS</th>
                        <th class="px-4 py-3">Hali</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($offers as $offer)
                        <tr class="border-b border-border/60 dark:border-slate-800">
                            <td class="px-4 py-3 dark:text-white">{{ $offer->customer?->name ?? '—' }}</td>
                            <td class="px-4 py-3 dark:text-white">{{ $offer->customer?->phone ?? '—' }}</td>
                            <td class="px-4 py-3 font-mono font-semibold text-primary-600">{{ $offer->offer_code }}</td>
                            <td class="px-4 py-3 dark:text-white">{{ $offer->discount_percent }}%</td>
                            <td class="px-4 py-3 dark:text-white">{{ $offer->month_key }}</td>
                            <td class="px-4 py-3 text-text-muted">{{ $offer->sms_sent_at?->format('M d, H:i') ?? '—' }}</td>
                            <td class="px-4 py-3"><span class="badge badge-neutral">{{ ucfirst($offer->status) }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-text-muted">Hakuna offers bado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($offers->hasPages())
            <div class="border-t border-border px-4 py-3 dark:border-slate-700">{{ $offers->links() }}</div>
        @endif
    </div>
</x-layouts.app>
