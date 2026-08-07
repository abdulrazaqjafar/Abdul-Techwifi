<?php

namespace App\Http\Controllers;

use App\Models\CustomerOffer;
use App\Services\MonthlyOfferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CustomerOfferController extends Controller
{
    public function index(): View
    {
        $offers = CustomerOffer::query()
            ->with('customer')
            ->latest()
            ->paginate(20);

        $nextRunDay = now()->endOfMonth()->subDay()->format('M d, Y');

        return view('offers.index', compact('offers', 'nextRunDay'));
    }

    public function dispatchNow(MonthlyOfferService $monthlyOfferService): RedirectResponse
    {
        $result = $monthlyOfferService->dispatchMonthlyOffers(force: true);

        return back()->with(
            'success',
            "Offers: {$result['created']} mpya, SMS {$result['sent']} zimetumwa, {$result['skipped']} zimerukwa."
        );
    }
}
